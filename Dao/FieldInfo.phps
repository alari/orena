<?php
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
	 * Classname of current Dao_Object
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
		$q = new Dao_Query( $this->class );
		$this->addFieldTypeToQuery( $q );
		return $q->alter( "ADD" );
	}

	/**
	 * Create relation with other object(s).
	 *
	 * @param int $object_id
	 * @return Dao_Relation_BaseToMany
	 */
	private function getRelation( $object_id )
	{
		if (!isset( $this->relation[ $object_id ] ) || !$this->relation[ $object_id ] instanceof Dao_Relation_BaseToMany) {
			if ($this->getInverse()->relationMany) {
				// Relation with anchors table (many-to-many or one-to-many without inverse)
				$this->relation[ $object_id ] = new Dao_Relation_ManyToMany( $this->relationTarget, $this->relationInverse, $object_id, $this->class, $this->name );
			} else {
				// Has many with inverse
				$this->relation[ $object_id ] = new Dao_Relation_OneToMany( $this->relationTarget, $this->relationInverse, $object_id, $this->class, $this->name );
			}
		}
		return $this->relation[ $object_id ];
	}

	/**
	 * Refreshes relation object
	 *
	 * @param int $object_id
	 */
	public function reload( $object_id )
	{
		$this->relation[ $object_id ] = null;
	}

	/**
	 * FieldInfo of reverse field
	 *
	 * @return Dao_FieldInfo
	 */
	private function getInverse()
	{
		if (!$this->relationInverseField)
			$this->relationInverseField = Dao_TableInfo::get( $this->relationTarget )->getFieldInfo( $this->relationInverse );
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
	 * @param Dao_Object $obj
	 * @param mixed $fieldValue
	 * @throws Exception
	 * @return bool
	 */
	public function setValue( Dao_Object $obj, $fieldValue )
	{
		// TODO: add signal support
		// Value as is
		if ($this->isAtomic) {
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
		if ($fieldValue instanceof $this->relationTarget) {
			$obj->setField( $this->name, $fieldValue );
			// One-to-one is symmetric
			if (!$this->getInverse()->relationMany)
				$fieldValue->setField( $this->getInverse()->name, $obj->id );
		}
		throw new Exception( "Assigment of wrong value type." );
	}

	/**
	 * Returns the field value, even if it's a relation
	 *
	 * @param Dao_Object $object
	 * @param mixed $fieldValue
	 * @return mixed
	 */
	public function getValue( Dao_Object $object, $fieldValue, $fieldExists )
	{
		// Value as is
		if ($this->isAtomic) {
			if (!$fieldExists)
				$this->addFieldToTable();
			return $fieldValue;
		}
		// Alias -- cached query
		if ($this->alias) {
			if (!$this->aliasQuery) {
				list ($name, $subreq) = explode( ".", $this->alias, 2 );
				$this->aliasTestField = Dao_TableInfo::get( $this->class )->getFieldInfo( $name )->prepareMappedQuery( $this->aliasQuery, $subreq );
			}
			if (!$this->aliasQuery instanceof Dao_Query) {
				throw new Exception( "Wrong mapped query produced by $name.$subreq map." );
			}
			$q = clone $this->aliasQuery;
			return $q->test( $this->aliasTestField, $fieldValue ? $fieldValue : $object->id );
		}
		// Many objects
		if ($this->relationMany) {
			return $this->getRelation( $object->id );
		}
		// Base-to-one
		if (!$fieldExists)
			$this->addFieldToTable();
		return Dao_Object::getById( $fieldValue, $this->relationTarget );
	}

	/**
	 * Returns query by object relations mapping
	 *
	 * @param Dao_Object $object
	 * @param int $fieldValue
	 * @param string $subreq
	 * @return Dao_Query
	 */
	public function getMappedQuery( Dao_Object $object, $fieldValue = null, $subreq = "" )
	{
		if ($this->isAtomic())
			throw new Exception( "Cannot create mapped query field by atomic field basis." );
		
		$query = null;
		$joinOnField = $this->prepareMappedQuery( $query, $subreq );
		$query->test( $joinOnField, $fieldValue ? $fieldValue : $object->id );
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
		while ($fieldInfo = current( $fieldInfos )) {
			$nextInfo = next( $fieldInfos );
			if (!$nextInfo)
				$nextInfo = $this;
			$joinOnField = $fieldInfo->modifyMappedQuery( $query, $joinOnField, $nextInfo );
		}
		return $joinOnField;
	}

	/**
	 * Prepares query step for getting mapped query
	 *
	 * @param Dao_Query $query
	 * @param string $joinOnField Given from previous request
	 * @param Dao_FieldInfo $nextInfo
	 * @return string
	 */
	private function modifyMappedQuery( Dao_Query &$query = null, $joinOnField = null, Dao_FieldInfo $nextInfo = null )
	{
		if (!$query) {
			$query = new Dao_Query( $this->relationTarget );
			$joinOnField = Dao_TableInfo::get( $this->relationTarget )->getTableName() . ".id";
		}
		
		$isOneToMany = $nextInfo->relationMany && !$nextInfo->getInverse()->relationMany;
		
		$currTable = Dao_TableInfo::get( $this->class )->getTableName();
		
		//many-to-many: relation is a special table
		if ($this->relationMany && $this->getInverse()->relationMany) {
			$tbl = $this->getRelation( 0 )->getRelationTableName();
			
			$query->join( $tbl, $tbl . "." . Dao_TableInfo::get( $this->relationTarget )->getTableName() . "=" . $joinOnField, "CROSS" );
			
			if ($isOneToMany) {
				$query->join( $currTable, $currTable . ".id=" . $tbl . "." . $currTable, "CROSS" );
				return $currTable . "." . $nextInfo->getInverse()->name;
			}
			
			return $tbl . "." . $currTable;
			//relation is current table itself
		} else {
			$query->join( $currTable, $currTable . "." . $this->name . "=" . $joinOnField, "CROSS" );
			
			if ($isOneToMany) {
				return $currTable . "." . $nextInfo->getInverse()->name;
			}
			return $currTable . ".id";
		}
	}

	/**
	 * Handles deletion of object -- or just of relation
	 *
	 * @param Dao_Object $object
	 * @param mixed $fieldValue
	 */
	public function deleteThis( Dao_Object $object, $fieldValue = null )
	{
		if ($this->isAtomic || $this->alias) {
			// TODO: add signal support for atomic fields
			return;
		}
		unset( $this->relation[ $object->id ] );
		if ($this->relationMany) {
			// Action is always needed
			$this->getRelation( $object->id )->removeAll( $this->relationOwns );
		} else {
			// Action needed if target is set and must be deleted, or if inverse field must be cleaned
			$relative = Dao_Object::getById( $fieldValue, $this->relationTarget );
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