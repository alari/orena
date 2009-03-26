<?php
/**
 * ManyToMany relation workaround.
 *
 * @see O_Dao_Relation_BaseToMany
 * @see O_Dao_FieldInfo
 *
 * @author Dmitry Kourinski
 */
class O_Dao_Relation_ManyToMany extends O_Dao_Relation_BaseToMany {
	private $targetClass;
	private $targetField;
	private $targetTbl;
	private $targetFieldName;
	private $baseId;
	private $baseClass;
	private $baseField;
	private $baseTbl;
	private $baseFieldName;
	private $orderBy;
	
	private $relationTbl;
	
	private static $relationTblLoaded = Array ();

	/**
	 * Relation instance for concrete object
	 *
	 * @param string $targetClass
	 * @param string $inverseField
	 * @param int $baseId
	 * @access private
	 */
	public function __construct( $targetClass, $targetField, $baseId, $baseClass, $baseField, $orderBy )
	{
		$this->targetClass = $targetClass;
		$this->targetField = $targetField;
		$this->targetTbl = O_Dao_TableInfo::get( $targetClass )->getTableName();
		$this->baseId = $baseId;
		$this->baseClass = $baseClass;
		$this->baseField = $baseField;
		$this->baseTbl = O_Dao_TableInfo::get( $baseClass )->getTableName();
		
		$this->getTargetFieldName();
		$this->getBaseFieldName();
		$this->relationTbl = $this->getRelationTableName();
		
		parent::__construct( $this->targetClass );
		
		$this->join( $this->relationTbl, 
				$this->targetTbl . ".id=" . $this->relationTbl . "." . $this->targetFieldName . " AND " . $this->relationTbl .
					 "." . $this->baseFieldName . "=" . $baseId, "CROSS" );
		if ($orderBy)
			$this->orderBy( $this->targetTbl . "." . $orderBy );
	}

	/**
	 * Returns target field name of relation table
	 *
	 * @return string
	 */
	public function getTargetFieldName()
	{
		if (!$this->targetFieldName) {
			$this->targetFieldName = $this->targetTbl . "_" . $this->targetField;
			if ($this->targetTbl == $this->baseTbl && $this->targetField == $this->baseField) {
				$this->targetFieldName = "t_" . $this->targetFieldName;
			}
		}
		return $this->targetFieldName;
	}

	/**
	 * Returns base field name of relation table
	 *
	 * @return string
	 */
	public function getBaseFieldName()
	{
		if (!$this->baseFieldName) {
			$this->baseFieldName = $this->baseTbl . "_" . $this->baseField;
		}
		return $this->baseFieldName;
	}

	/**
	 * Makes the relation table name from classes and fields, and creates it if needed and possible
	 *
	 * @return string
	 */
	public function getRelationTableName()
	{
		$a = $this->targetTbl . "_" . $this->targetField;
		$b = $this->baseTbl . "_" . $this->baseField;
		if ($b > $a) {
			$c = $b;
			$b = $a;
			$a = $c;
		}
		$r = substr( $a . "_to_" . $b, 0, 64 );
		
		if (isset( self::$relationTblLoaded[ $r ] ))
			return $r;
		
		if (!O_Db_Query::get( $r )->tableExists()) {
			$q = O_Db_Query::get( $r );
			$q->field( $this->targetFieldName, "int NOT NULL" )->field( $this->baseFieldName, "int NOT NULL" )->index( 
					$this->targetFieldName . ", " . $this->baseFieldName, "PRIMARY KEY" )->create();
		}
		
		self::$relationTblLoaded[ $r ] = 1;
		
		return $r;
	}

	/**
	 * Reloads the relation query
	 *
	 */
	public function reload()
	{
		O_Dao_TableInfo::get( $this->baseClass )->getFieldInfo( $this->baseField )->reload( $this->baseId );
	}

	/**
	 * Returns clear query with related objects
	 *
	 * @return O_Dao_Query
	 */
	public function query()
	{
		$q = new O_Dao_Query( $this->targetClass );
		$q->join( $this->relationTbl, 
				$this->targetTbl . ".id=" . $this->relationTbl . "." . $this->targetFieldName . " AND " . $this->relationTbl .
					 "." . $this->baseFieldName . "=" . $this->baseId, "CROSS" );
		if ($this->orderBy)
			$q->orderBy( $this->targetTbl . "." . $this->orderBy );
		return $q;
	}

	/**
	 * Removes an object from relation (current query state influes)
	 *
	 * @param O_Dao_ActiveRecord $object
	 * @param bool $delete If true, not only relation removed, but also an object deleted
	 * @return bool
	 */
	public function remove( O_Dao_ActiveRecord $object, $delete = false )
	{
		if (!$object)
			return false;
		if (!$object instanceof $this->targetClass)
			return false;
		if (!$this->offsetExists( $object->id ))
			return false;
		
		$q = new O_Db_Query( $this->relationTbl );
		$q->test( $this->targetFieldName, $object->id )->test( $this->baseFieldName, $this->baseId )->delete();
		
		if ($delete)
			$object->delete();
		
		$this->reload();
		
		return true;
	}

	/**
	 * Removes all objects from current relation state
	 *
	 * @param bool $delete
	 */
	public function removeAll( $delete = false )
	{
		foreach ($this->getAll() as $o)
			$this->remove( $o, $delete );
	}

	/**
	 * Adds support for [] operator
	 *
	 * @param null $offset
	 * @param O_Dao_ActiveRecord $obj
	 * @return bool
	 * @throws Exception
	 */
	public function offsetSet( $offset, $obj )
	{
		if (!$obj)
			return false;
		if ($this->offsetExists( $offset ))
			throw new Exception( "Cannot assign value to an existent dao object." );
		if (!$obj instanceof $this->targetClass)
			throw new Exception( "Wrong object type for assignation." );
		if ($offset !== null)
			throw new Exception( "Can assign only new value with [] operator." );
		
		if ($this->offsetExists( $obj->id )) {
			return true;
		}
		
		$q = new O_Db_Query( $this->relationTbl );
		$q->field( $this->targetFieldName, $obj->id )->field( $this->baseFieldName, $this->baseId )->insert();
		
		$this->reload();
		
		return true;
	}

}