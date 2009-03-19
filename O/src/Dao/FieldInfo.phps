<?php
/**
 * Handles one persistent field of O_Dao_ActiveRecord.
 *
 * Supported configuration.
 *
 * Atomic field:
 * @field fieldname sql type
 *
 * Base to one or base to many relation:
 * @field fieldname -(has|owns) (one|many) Target_Classname -inverse other_fieldname [-order-by field]
 *
 * "owns" key means "on delete cascade". Inverse field must be specified for any base-to-many relation.
 * It will be one to many or many to many due to inverse field description.
 * Target_Classname could be in {some/registry/key} format. If it's so, target classname will be get from
 * "app/some/registry/key" registry.
 * Also Target_Classname will be get from class constant if it's in ":CONST_NAME" format.
 *
 * Base to one relations returns target object as value. Base to many returns instance of
 * @see O_Dao_Relation_OneToMany
 * or
 * @see O_Dao_Relation_ManyToMany
 *
 * To add an object, use [] operator, e.g. $record->objs[] = new obj.
 * To remove an object, use remove() or removeAll() method:
 * @example $record->remove($obj) to delete relation with $obj
 * @example $record->removeAll() to delete relations with all objects
 * Those methods gets one additional bool parameter. If it's set to true, relative object will be deleted.
 *
 * O_Dao_FieldInfo also supports special "alias" fieldtype:
 * @field fieldname -alias relationfieldname.other_relation[.relation[...]] -where condition -order-by field
 * It returns O_Dao_Query with objects linked with current ActiveRecord with several steps.
 * E.g. A linked with B, B linked with C, so C is linked with A and this relation is available by calling
 * @example $A->{"B.C"} (this is named "mapped query" and handled by "B" fieldinfo object) or with
 * @field BC -alias B.C,
 * @example $A->BC
 * "-where" key adds an additional condition to result SQL string by using
 * @see O_Db_Query::where()
 *
 * Also FieldInfo provides signals support for fields changes:
 * @see O_Dao_Signals
 *
 * @author Dmitry Kourinski
 */
class O_Dao_FieldInfo {
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
	 * Unparsed target
	 *
	 * @var string
	 */
	private $relationTargetBase;
	/**
	 * Name of inverse field of related object
	 *
	 * @var string
	 */
	private $relationInverse;
	/**
	 * Object of inverse field
	 *
	 * @var O_Dao_FieldInfo
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
	 * @var O_Dao_Relation_BaseToMany
	 */
	private $relationObject;
	/**
	 * Classname of current O_Dao_ActiveRecord
	 *
	 * @var string
	 */
	private $class;
	/**
	 * Cached relation objects
	 *
	 * @var O_Dao_Relation_BaseToMany[]
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
	 * @var O_Dao_Query
	 */
	private $aliasQuery;

	/**
	 * Creates FieldInfo object
	 *
	 * @param string $class O_Dao_ActiveRecord classname
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
			
			list ($quantity, $this->relationTargetBase) = explode( " ", $params[ $relation ], 2 );
			// Get relation classname from registry, if needed!
			if ($this->relationTargetBase[ 0 ] == "{" && $this->relationTargetBase[ strlen( 
					$this->relationTargetBase ) - 1 ] == "}") {
				$this->relationTarget = O_Registry::get( "app/" . substr( $this->relationTargetBase, 1, -1 ) );
			} else {
				$this->relationTarget = $this->relationTargetBase;
			}
			// Get inverse fieldname
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
	 * @see O_Dao_TableInfo::__construct()
	 * @access package
	 * @param string $class
	 */
	public function setClass( $class )
	{
		$this->class = $class;
		if ($this->relationTargetBase && $this->relationTargetBase[ 0 ] == ":") {
			$const = $this->class . ":" . $this->relationTargetBase;
			$this->relationTarget = defined( $const ) ? constant( $const ) : null;
			$this->relationInverseField = null;
		}
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
	 * @param O_Db_Query $query
	 * @access private
	 */
	public function addFieldTypeToQuery( O_Db_Query $query )
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
		$q = new O_Dao_Query( $this->class );
		$this->addFieldTypeToQuery( $q );
		$r = $q->alter( "ADD" );
		O_Dao_ActiveRecord::saveAndReload( $this->class );
		O_Dao_Query::disablePreparing( $this->class );
		$is_added = true;
		return $r;
	}

	/**
	 * Create relation with other objects.
	 *
	 * @param int $obj_id
	 * @return O_Dao_Relation_BaseToMany
	 */
	private function getRelation( $obj_id )
	{
		if (!isset( $this->relation[ $obj_id ] ) || !$this->relation[ $obj_id ] instanceof O_Dao_Relation_BaseToMany) {
			if ($this->getInverse()->relationMany) {
				// Relation with anchors table (many-to-many or one-to-many without inverse)
				$this->relation[ $obj_id ] = new O_Dao_Relation_ManyToMany( 
						$this->relationTarget, $this->relationInverse, $obj_id, $this->class, $this->name, 
						$this->getParam( "order-by" ) );
			} else {
				// Has many with inverse
				$this->relation[ $obj_id ] = new O_Dao_Relation_OneToMany( $this->relationTarget, 
						$this->relationInverse, $obj_id, $this->class, $this->name, $this->getParam( "order-by" ) );
			}
		}
		return clone $this->relation[ $obj_id ];
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
	 * @return O_Dao_FieldInfo
	 */
	private function getInverse()
	{
		if (!$this->relationInverseField)
			$this->relationInverseField = O_Dao_TableInfo::get( $this->relationTarget )->getFieldInfo( 
					$this->relationInverse );
		return $this->relationInverseField;
	}

	/**
	 * Returns true if the field is not a relation or alias
	 *
	 * @return bool
	 */
	public function isAtomic()
	{
		return $this->isAtomic;
	}

	/**
	 * Returns true if the field is an alias
	 *
	 * @return bool
	 */
	public function isAlias()
	{
		return (bool)$this->alias;
	}

	/**
	 * Returns true if it's a *-to-one relation
	 *
	 * @return bool
	 */
	public function isRelationOne()
	{
		return $this->relationTarget && !$this->relationMany;
	}

	/**
	 * Returns true if it's a *-to-many relation
	 *
	 * @return bool
	 */
	public function isRelationMany()
	{
		return $this->relationTarget && $this->relationMany;
	}

	/**
	 * Returns true if it's a "on delete cascade" relation
	 *
	 * @return bool
	 */
	public function isRelationOwns()
	{
		return $this->relationTarget && $this->relationOwns;
	}

	/**
	 * Returns classname of relation target
	 *
	 * @return string
	 */
	public function getRelationTarget()
	{
		return $this->relationTarget;
	}

	/**
	 * Sets the value with all tests provided
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @throws Exception
	 * @return bool
	 * @access private
	 */
	public function setValue( O_Dao_ActiveRecord $obj, $fieldValue, $fieldExists )
	{
		if (isset( $this->params[ "signal" ] )) {
			// Old value removed
			O_Dao_Signals::fire( O_Dao_Signals::EVENT_REMOVE, $this->params[ "signal" ], $this->class, 
					$obj, $obj->{$this->name} );
			// New value is set
			O_Dao_Signals::fire( O_Dao_Signals::EVENT_SET, $this->params[ "signal" ], $this->class, 
					$obj, $fieldValue );
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
		if (get_class( $fieldValue ) == $this->relationTarget || is_null( $fieldValue )) {
			if (!$fieldExists) {
				$this->addFieldToTable();
			}
			$oldValue = $obj->{$this->name};
			$obj->setField( $this->name, $fieldValue ? $fieldValue->id : 0 );
			// One-to-one is symmetric
			if (!$this->getInverse()->relationMany) {
				
				$inverseName = $this->getInverse()->name;
				if ($oldValue) {
					$oldValue->$inverseName = null;
					$oldValue->save();
				}
				if ($fieldValue && (!$fieldValue->$inverseName || $fieldValue->$inverseName->id != $obj->id)) {
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
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @return mixed
	 * @access private
	 */
	public function getValue( O_Dao_ActiveRecord $obj, $fieldValue, $fieldExists )
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
				$this->aliasTestField = O_Dao_TableInfo::get( $this->class )->getFieldInfo( $name )->prepareMappedQuery( 
						$this->aliasQuery, $subreq );
				
				if (isset( $this->params[ "where" ] ) && $this->aliasQuery instanceof O_Dao_Query) {
					$this->aliasQuery->where( $this->params[ "where" ] );
				}
				if (isset( $this->params[ "order-by" ] ) && $this->aliasQuery instanceof O_Dao_Query) {
					$this->aliasQuery->orderBy( $this->params[ "order-by" ] );
				}
			}
			if (!$this->aliasQuery instanceof O_Dao_Query) {
				throw new Exception( "Wrong mapped query is produced by $name.$subreq map." );
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
		return O_Dao_ActiveRecord::getById( $fieldValue, $this->relationTarget );
	}

	/**
	 * Returns query by object relations mapping
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param int $fieldValue
	 * @param string $subreq
	 * @return O_Dao_Query
	 * @access private
	 */
	public function getMappedQuery( O_Dao_ActiveRecord $obj, $fieldValue = null, $subreq = "" )
	{
		if ($this->isAtomic())
			throw new Exception( "Cannot create mapped query field by atomic field basis." );
		
		$query = null;
		$joinOnField = $this->prepareMappedQuery( $query, $subreq );
		if ($this->relationMany && $this->getInverse()->relationMany) {
			$tbl = $this->getRelation( 0 )->getRelationTableName();
			$query->join( $tbl, 
					$tbl . "." . O_Dao_TableInfo::get( $this->relationTarget )->getTableName() . "=" . $joinOnField, 
					"CROSS" );
			$joinOnField = $tbl . "." . O_Dao_TableInfo::get( $this->class )->getTableName();
		}
		$query->test( $joinOnField, $fieldValue ? $fieldValue : $obj->id );
		return $query;
	}

	/**
	 * Prepares query but don't substitute concrete object
	 *
	 * @param O_Dao_Query $query
	 * @param string $subreq
	 * @return string Field to test
	 */
	protected function prepareMappedQuery( O_Dao_Query &$query = null, $subreq = "" )
	{
		$fieldInfos = Array ();
		$subreq = explode( ".", $subreq );
		$info = $this;
		foreach ($subreq as $fieldName) {
			$info = O_Dao_TableInfo::get( $info->relationTarget )->getFieldInfo( $fieldName );
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
	 * @param O_Dao_Query $query
	 * @param O_Dao_FieldInfo $nextInfo
	 * @param string $joinOnField From previous request
	 * @param int $i Current iteration
	 * @return string
	 */
	private function modifyMappedQuery( O_Dao_Query &$query = null, $joinOnField = null, O_Dao_FieldInfo $nextInfo = null, $i = 0 )
	{
		if (!$query) {
			$query = new O_Dao_Query( $this->relationTarget );
			$joinOnField = O_Dao_TableInfo::get( $this->relationTarget )->getTableName() . ".id";
		}
		
		$isOneToMany = $nextInfo->relationMany && !$nextInfo->getInverse()->relationMany;
		
		$currTable = O_Dao_TableInfo::get( $this->class )->getTableName();
		$currAlias = $currTable . ($i ? "_" . $i : "");
		
		//many-to-many: relation is a special table
		if ($this->relationMany && $this->getInverse()->relationMany) {
			$tbl = $this->getRelation( 0 )->getRelationTableName();
			$als = $tbl . ($i ? "_" . $i : "");
			
			$query->join( $tbl . " " . $als, 
					$als . "." . O_Dao_TableInfo::get( $this->relationTarget )->getTableName() . "=" . $joinOnField, 
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
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @access private
	 */
	public function deleteThis( O_Dao_ActiveRecord $obj, $fieldValue = null )
	{
		if (isset( $this->params[ "signal" ] )) {
			// Old value removed
			O_Dao_Signals::fire( O_Dao_Signals::EVENT_REMOVE, $this->params[ "signal" ], $this->class, 
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
			$relative = O_Dao_ActiveRecord::getById( $fieldValue, $this->relationTarget );
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