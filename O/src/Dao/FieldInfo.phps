<?php
/**
 * Handles one persistent field of Dao_ActiveRecord.
 *
 * Supported configuration.
 *
 * Atomic field:
 * @field fieldname sql type
 *
 * Base to one or base to many relation:
 * @field fieldname -(has|owns) (one|many) Target_Classname -inverse other_fieldname
 *
 * "owns" key means "on delete cascade". Inverse field must be specified for any base-to-many relation.
 * It will be one to many or many to many due to inverse field description.
 *
 * Base to one relations returns target object as value. Base to many returns instance of
 * @see Dao_Relation_OneToMany
 * or
 * @see Dao_Relation_ManyToMany
 *
 * To add an object, use [] operator, e.g. $record->objs[] = new obj.
 * To remove an object, use remove() or removeAll() method:
 * @example $record->remove($obj) to delete relation with $obj
 * @example $record->removeAll() to delete relations with all objects
 * Those methods gets one additional bool parameter. If it's set to true, relative object will be deleted.
 *
 * Dao_FieldInfo also supports special "alias" fieldtype:
 * @field fieldname -alias relationfieldname.other_relation[.relation[...]]
 * It returns Dao_Query with objects linked with current ActiveRecord with several steps.
 * E.g. A linked with B, B linked with C, so C is linked with A and this relation is available by calling
 * @example $A->{"B.C"} (this is named "mapped query" and handled by "B" fieldinfo object) or with
 * @field BC -alias B.C,
 * @example $A->BC
 *
 * Also FieldInfo provides signals support for fields changes:
 * @see Dao_Signals
 *
 * @author Dmitry Kourinski
 */
class Dao_FieldInfo {
	/**
	 * External (database) name of this field
	 *
	 * @var string
	 */
	private $name;
	/**
	 * Array of field params
	 *
	 * @var Array
	 */
	private $params;

	/**
	 * Database type of field, e.g. int, tinytext, or null if it's not represented in db
	 *
	 * @var string
	 */
	private $type;
	/**
	 * Do the field contain its data as is or showing relation to other objects
	 *
	 * @var bool
	 */
	private $isAtomic = null;

	/**
	 * Relation to one or to many objects
	 *
	 * @var bool
	 */
	private $relationMany = 0;
	/**
	 * Target classname of relation
	 *
	 * @var string
	 */
	private $relationTarget;
	/**
	 * Name of inverse field of related object
	 *
	 * @var string
	 */
	private $relationInverse;
	/**
	 * Object of inverse field
	 *
	 * @var Dao_FieldInfo
	 */
	private $relationInverseField;
	/**
	 * Relation consists of target or just links it
	 *
	 * @var bool
	 */
	private $relationOwns = 0;
	/**
	 * Object of relation -- OneToMany, ManyToMany, etc.
	 *
	 * @var Dao_Relation_BaseToMany
	 */
	private $relationObject;
	/**
	 * Classname of current Dao_ActiveRecord
	 *
	 * @var string
	 */
	private $class;
	/**
	 * Cached relation objects
	 *
	 * @var Dao_Relation_BaseToMany[]
	 */
	private $relation = Array ();
	/**
	 * String map for alias field
	 * e.g. fieldA.fieldB means relation field A which targets object whith relation field B
	 * Query with B will be returned
	 *
	 * @var string
	 */
	private $alias;
	/**
	 * Field to test to get concrete alias instance
	 *
	 * @var string
	 */
	private $aliasTestField;
	/**
	 * Query for alias
	 *
	 * @var Dao_Query
	 */
	private $aliasQuery;

	/**
	 * Creates FieldInfo object
	 *
	 * @param string $class Dao_ActiveRecord classname
	 * @param string $name Persistent field name
	 * @param string $type SQL type of field, if specified
	 * @param array $params
	 */
	public function __construct( $class, $name, $type, Array $params )
	{
		$this->class = $class;
		$this->name = $name;
		$this->params = $params;
		$this->type = $type;

		// check if it's relation
		if (isset( $params[ "has" ] )) {
			$relation = "has";
			$this->relationOwns = 0;
		} elseif (isset( $params[ "owns" ] )) {
			$relation = "owns";
			$this->relationOwns = 1;
		}

		// save info about relation to be ready to sey it to other side
		if (isset( $relation )) {
			$this->isAtomic = false;

			list ($quantity, $this->relationTarget) = explode( " ", $params[ $relation ], 2 );
			$this->relationInverse = isset( $params[ "inverse" ] ) ? $params[ "inverse" ] : null;

			if ($quantity == "many") {
				$this->relationMany = 1;
				if (!$this->relationInverse)
					throw new Exception( "Inverse field must be specified for whatever-to-many relations." );
			}
		} else {
			if (isset( $params[ "alias" ] ) && strpos( $params[ "alias" ], "." )) {
				$this->isAtomic = false;
				$this->alias = $params[ "alias" ];
			} else {
				$this->isAtomic = true;
				if (!$this->type)
					throw new Exception( "Cannot initiate atomic field without type ($this->name)" );
			}
		}
	}

	/**
	 * Sets the class
	 *
	 * @see Dao_TableInfo::__construct()
	 * @access package
	 * @param string $class
	 */
	public function setClass( $class )
	{
		$this->class = $class;
	}

	/**
	 * Returns value of param stored in field config query
	 *
	 * @param string $paramName
	 * @return string
	 */
	public function getParam( $paramName )
	{
		return isset( $this->params[ $paramName ] ) ? $this->params[ $paramName ] : null;
	}

	/**
	 * Adds field to CREATE query
	 *
	 * @param Db_Query $query
	 * @access private
	 */
	public function addFieldTypeToQuery( Db_Query $query )
	{
		if ($this->relationMany || $this->alias)
			return;
		if ($this->isAtomic)
			$query->field( $this->name, $this->type );
		else
			$query->field( $this->name, "int" )->index( $this->name );
	}

	/**
	 * Alters table to add unexistent field
	 *
	 * @return PDOStatement
	 */
	private function addFieldToTable()
	{
		static $is_added = false;
		if ($is_added)
			return null;
		$q = new Dao_Query( $this->class );
		$this->addFieldTypeToQuery( $q );
		$r = $q->alter( "ADD" );
		Dao_ActiveRecord::saveAndReload( $this->class );
		Dao_Query::disablePreparing( $this->class );
		$is_added = true;
		return $r;
	}

	/**
	 * Create relation with other object(s).
	 *
	 * @param int $obj_id
	 * @return Dao_Relation_BaseToMany
	 */
	private function getRelation( $obj_id )
	{
		if (!isset( $this->relation[ $obj_id ] ) || !$this->relation[ $obj_id ] instanceof Dao_Relation_BaseToMany) {
			if ($this->getInverse()->relationMany) {
				// Relation with anchors table (many-to-many or one-to-many without inverse)
				$this->relation[ $obj_id ] = new Dao_Relation_ManyToMany( $this->relationTarget,
						$this->relationInverse, $obj_id, $this->class, $this->name );
			} else {
				// Has many with inverse
				$this->relation[ $obj_id ] = new Dao_Relation_OneToMany( $this->relationTarget,
						$this->relationInverse, $obj_id, $this->class, $this->name );
			}
		}
		return $this->relation[ $obj_id ];
	}

	/**
	 * Refreshes relation object
	 *
	 * @param int $obj_id
	 * @access private
	 */
	public function reload( $obj_id )
	{
		$this->relation[ $obj_id ] = null;
	}

	/**
	 * FieldInfo of reverse field
	 *
	 * @return Dao_FieldInfo
	 */
	private function getInverse()
	{
		if (!$this->relationInverseField)
			$this->relationInverseField = Dao_TableInfo::get( $this->relationTarget )->getFieldInfo(
					$this->relationInverse );
		return $this->relationInverseField;
	}

	/**
	 * Returns true if the field is not a relation
	 *
	 * @return bool
	 */
	public function isAtomic()
	{
		return $this->isAtomic;
	}

	/**
	 * Sets the value with all tests provided
	 *
	 * @param Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @throws Exception
	 * @return bool
	 * @access private
	 */
	public function setValue( Dao_ActiveRecord $obj, $fieldValue, $fieldExists )
	{
		if (isset( $this->params[ "signal" ] )) {
			// Old value removed
			Dao_Signals::fire( Dao_Signals::EVENT_REMOVE, $this->params[ "signal" ], $this->class,
					$obj, $obj->{$this->name} );
			// New value is set
			Dao_Signals::fire( Dao_Signals::EVENT_SET, $this->params[ "signal" ], $this->class, $obj,
					$fieldValue );
			// TODO: maybe signal should be fired when field is saved to database, not just set?
		}
		// Value as is
		if ($this->isAtomic) {
			if (!$fieldExists) {
				$this->addFieldToTable();
			}
			return $obj->setField( $this->name, $fieldValue );
		}
		// Alias -- setting impossible
		if ($this->alias) {
			throw new Exception( "Cannot assign to aliases." );
		}
		// Many objects
		if ($this->relationMany) {
			throw new Exception( "Cannot assign base-to-many relation." );
		}
		// Base-to-one
		if (get_class( $fieldValue ) == $this->relationTarget) {
			if (!$fieldExists) {
				$this->addFieldToTable();
			}
			$obj->setField( $this->name, $fieldValue->id );
			// One-to-one is symmetric
			if (!$this->getInverse()->relationMany) {
				$inverseName = $this->getInverse()->name;
				if (!$fieldValue->$inverseName || $fieldValue->$inverseName->id != $obj->id) {
					$fieldValue->{$this->getInverse()->name} = $obj;
					$fieldValue->save();
				}
			}
			return $fieldValue;
		}
		throw new Exception( "Assigment of wrong value type." );
	}

	/**
	 * Returns the field value, even if it's a relation or aliased query
	 *
	 * @param Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @return mixed
	 * @access private
	 */
	public function getValue( Dao_ActiveRecord $obj, $fieldValue, $fieldExists )
	{
		// Value as is
		if ($this->isAtomic) {
			if (!$fieldExists) {
				$this->addFieldToTable();
			}
			return $fieldValue;
		}
		// Alias -- cached query
		if ($this->alias) {
			if (!$this->aliasQuery) {
				list ($name, $subreq) = explode( ".", $this->alias, 2 );
				$this->aliasTestField = Dao_TableInfo::get( $this->class )->getFieldInfo( $name )->prepareMappedQuery(
						$this->aliasQuery, $subreq );
				// @todo document this -alias feature
				if (isset( $this->params[ "where" ] ) && $this->aliasQuery instanceof Dao_Query) {
					$this->aliasQuery->where( $this->params[ "where" ] );
				}
			}
			if (!$this->aliasQuery instanceof Dao_Query) {
				throw new Exception( "Wrong mapped query produced by $name.$subreq map." );
			}
			$q = clone $this->aliasQuery;
			return $q->test( $this->aliasTestField, $fieldValue ? $fieldValue : $obj->id );
		}
		// Many objects
		if ($this->relationMany) {
			return $this->getRelation( $obj->id );
		}
		// Base-to-one
		if (!$fieldExists) {
			$this->addFieldToTable();
		}
		return Dao_ActiveRecord::getById( $fieldValue, $this->relationTarget );
	}

	/**
	 * Returns query by object relations mapping
	 *
	 * @param Dao_ActiveRecord $obj
	 * @param int $fieldValue
	 * @param string $subreq
	 * @return Dao_Query
	 * @access private
	 */
	public function getMappedQuery( Dao_ActiveRecord $obj, $fieldValue = null, $subreq = "" )
	{
		if ($this->isAtomic())
			throw new Exception( "Cannot create mapped query field by atomic field basis." );

		$query = null;
		$joinOnField = $this->prepareMappedQuery( $query, $subreq );
		if ($this->relationMany && $this->getInverse()->relationMany) {
			$tbl = $this->getRelation( 0 )->getRelationTableName();
			$query->join( $tbl,
					$tbl . "." . Dao_TableInfo::get( $this->relationTarget )->getTableName() . "=" . $joinOnField,
					"CROSS" );
			$joinOnField = $tbl . "." . Dao_TableInfo::get( $this->class )->getTableName();
		}
		$query->test( $joinOnField, $fieldValue ? $fieldValue : $obj->id );
		return $query;
	}

	/**
	 * Prepares query but don't substitute concrete object
	 *
	 * @param Dao_Query $query
	 * @param string $subreq
	 * @return string Field to test
	 */
	protected function prepareMappedQuery( Dao_Query &$query = null, $subreq = "" )
	{
		$fieldInfos = Array ();
		$subreq = explode( ".", $subreq );
		$info = $this;
		foreach ($subreq as $fieldName) {
			$info = Dao_TableInfo::get( $info->relationTarget )->getFieldInfo( $fieldName );
			if ($info->isAtomic())
				throw new Exception( "Cannot use atomic field ($fieldName) as a part of mapped query field." );
			array_unshift( $fieldInfos, $info );
		}

		$joinOnField = null;
		$i = 0;
		while ($fieldInfo = current( $fieldInfos )) {
			$nextInfo = next( $fieldInfos );
			if (!$nextInfo)
				$nextInfo = $this;
			$joinOnField = $fieldInfo->modifyMappedQuery( $query, $joinOnField, $nextInfo, $i++ );
		}
		return $joinOnField;
	}

	/**
	 * Prepares query step for getting mapped query
	 *
	 * @param Dao_Query $query
	 * @param Dao_FieldInfo $nextInfo
	 * @param string $joinOnField From previous request
	 * @param int $i Current iteration
	 * @return string
	 */
	private function modifyMappedQuery( Dao_Query &$query = null, $joinOnField = null, Dao_FieldInfo $nextInfo = null, $i = 0 )
	{
		if (!$query) {
			$query = new Dao_Query( $this->relationTarget );
			$joinOnField = Dao_TableInfo::get( $this->relationTarget )->getTableName() . ".id";
		}

		$isOneToMany = $nextInfo->relationMany && !$nextInfo->getInverse()->relationMany;

		$currTable = Dao_TableInfo::get( $this->class )->getTableName();
		$currAlias = $currTable . ($i ? "_" . $i : "");

		//many-to-many: relation is a special table
		if ($this->relationMany && $this->getInverse()->relationMany) {
			$tbl = $this->getRelation( 0 )->getRelationTableName();
			$als = $tbl . ($i ? "_" . $i : "");

			$query->join( $tbl . " " . $als,
					$als . "." . Dao_TableInfo::get( $this->relationTarget )->getTableName() . "=" . $joinOnField,
					"CROSS" );

			if ($isOneToMany) {
				$query->join( $currTable . " " . $currAlias, $currAlias . ".id=" . $als . "." . $currTable, "CROSS" );
				return $currAlias . "." . $nextInfo->getInverse()->name;
			}

			return $als . "." . $currTable;
			//relation is current table itself
		} else {
			$query->join( $currTable . " " . $currAlias, $currAlias . "." . $this->name . "=" . $joinOnField, "CROSS" );

			if ($isOneToMany) {
				return $currAlias . "." . $nextInfo->getInverse()->name;
			}
			return $currAlias . ".id";
		}
	}

	/**
	 * Handles deletion of object -- or just of relation
	 *
	 * @param Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @access private
	 */
	public function deleteThis( Dao_ActiveRecord $obj, $fieldValue = null )
	{
		if (isset( $this->params[ "signal" ] )) {
			// Old value removed
			Dao_Signals::fire( Dao_Signals::EVENT_REMOVE, $this->params[ "signal" ], $this->class,
					$obj, $obj->{$this->name} );
		}
		if ($this->isAtomic || $this->alias) {
			return;
		}
		unset( $this->relation[ $obj->id ] );
		if ($this->relationMany) {
			// Action is always needed
			$this->getRelation( $obj->id )->removeAll( $this->relationOwns );
		} else {
			// Action needed if target is set and must be deleted, or if inverse field must be cleaned
			$relative = Dao_ActiveRecord::getById( $fieldValue, $this->relationTarget );
			if ($relative) {
				// If has one, the database field exists
				if ($this->getInverse() && !$this->getInverse()->relationMany) {
					$relative->setField( $this->getInverse(), null );
				}
				if ($this->relationOwns) {
					$relative->delete();
				}
			}
		}
	}
}