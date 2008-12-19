<?php
class Dao_Relation_ManyToMany extends Dao_Relation_BaseToMany {
	private $targetClass;
	private $targetField;
	private $targetTbl;
	private $baseId;
	private $baseClass;
	private $baseField;
	private $baseTbl;
	
	private $relationTbl;
	
	private static $relationTblLoaded = Array();
	
	/**
	 * Relation instance for concrete object
	 *
	 * @param string $targetClass
	 * @param string $inverseField
	 * @param int $baseId
	 */
	public function __construct( $targetClass, $targetField, $baseId, $baseClass, $baseField )
	{
		$this->targetClass = $targetClass;
		$this->targetField = $targetField;
		$this->targetTbl = Dao_TableInfo::get( $targetClass )->getTableName();
		$this->baseId = $baseId;
		$this->baseClass = $baseClass;
		$this->baseField = $baseField;
		$this->baseTbl = Dao_TableInfo::get( $baseClass )->getTableName();
		
		$this->relationTbl = $this->getRelationTableName();
		
		parent::__construct( $this->targetClass );
		
		$this->join( $this->relationTbl, $this->targetTbl . ".id=" . $this->relationTbl . "." . $this->targetTbl . " AND " . $this->relationTbl . "." . $this->baseTbl . "=" . $baseId, "CROSS" );
		//$this->addFrom($this->relationTbl)->where($this->targetTbl . ".id=" . $this->relationTbl . "." . $this->targetTbl . " AND " . $this->relationTbl . "." . $this->baseTbl . "=" . $baseId);
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
		
		$stmt = Db_Manager::getConnection()->query( "SHOW TABLE STATUS WHERE name = '" . $r . "'" );
		if ($stmt)
			$stmt->execute();
		if (!$stmt || !count( $stmt->fetchAll() )) {
			$q = new Db_Query( $r );
			$q->field( $this->targetTbl, "int NOT NULL" )->field( $this->baseTbl, "int NOT NULL" )->index( $this->targetTbl . ", " . $this->baseTbl, "PRIMARY KEY" )->create();
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
		Dao_TableInfo::get( $this->baseClass )->getFieldInfo( $this->baseField )->reload( $this->baseId );
	}
	
	/**
	 * Returns clear query with related objects
	 *
	 * @return Dao_Query
	 */
	public function query()
	{
		$q = new Dao_Query( $this->targetClass );
		return $q->join( $this->relationTbl, $this->targetTbl . ".id=" . $this->relationTbl . "." . $this->targetTbl . " AND " . $this->relationTbl . "." . $this->baseTbl . "=" . $this->baseId, "CROSS" );
	}
	
	/**
	 * Removes an object from relation (current query state influes)
	 *
	 * @param Dao_Object $object
	 * @param bool $delete If true, not only relation removed, but also an object deleted
	 * @return bool
	 */
	public function remove( Dao_Object $object, $delete = false )
	{
		if (!$object)
			return false;
		if (!$object instanceof $this->targetClass)
			return false;
		if (!$this->offsetExists( $object->id ))
			return false;
		
		$q = new Db_Query( $this->relationTbl );
		$q->test( $this->targetTbl, $object->id )->test( $this->baseTbl, $this->baseId )->delete();
		
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
	 * @param Dao_Object $obj
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
		
		$q = new Db_Query( $this->relationTbl );
		$q->field( $this->targetTbl, $obj->id )->field( $this->baseTbl, $this->baseId )->insert();
		
		$this->reload();
		
		return true;
	}

}