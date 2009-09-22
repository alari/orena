<?php
/**
 * OneToMany relation workaround.
 *
 * @see O_Dao_Relation_BaseToMany
 * @see O_Dao_FieldInfo
 *
 * @author Dmitry Kurinskiy
 */
class O_Dao_Relation_OneToMany extends O_Dao_Relation_BaseToMany {
	private $targetClass;
	private $targetField;
	private $baseId;
	private $baseClass;
	private $baseField;
	private $orderBy;

	/**
	 * Relation instance for concrete object
	 *
	 * @param string $targetClass
	 * @param string $targetField
	 * @param int $baseId
	 */
	public function __construct( $targetClass, $targetField, $baseId, $baseClass, $baseField, $orderBy )
	{
		parent::__construct( $targetClass );
		$this->targetClass = $targetClass;
		$this->targetField = $targetField;
		$this->baseId = $baseId;
		$this->baseClass = $baseClass;
		$this->baseField = $baseField;
		$this->orderBy = $orderBy;
		
		$tbl = O_Dao_TableInfo::get( $targetClass )->getTableName();
		$this->test( $tbl . "." . $targetField, $baseId );
		if ($orderBy)
			$this->orderBy( $tbl . "." . $orderBy );
	}

	/**
	 * Returns clear query with related objects
	 *
	 * @return O_Dao_Query
	 */
	public function query()
	{
		$q = new O_Dao_Query( $this->targetClass );
		$tbl = O_Dao_TableInfo::get( $this->targetClass )->getTableName();
		$q->test( $tbl . "." . $this->targetField, $this->baseId );
		if ($this->orderBy)
			$q->orderBy( $tbl . "." . $this->orderBy );
		return $q;
	}

	/**
	 * Checks without loading all objects
	 *
	 * @param int|target $objOrId
	 * @return int 0 or 1
	 */
	public function has( $objOrId )
	{
		if ($objOrId instanceof $this->targetClass)
			$objOrId = $objOrId->id;
		if (!is_numeric( $objOrId ))
			return false;
		return O_Dao_Query::get( $this->targetClass )->test( $this->targetField, $this->baseId )->test( 
				"id", $objOrId )->getFunc( "id" );
	}

	/**
	 * Reloads the relation query
	 *
	 */
	public function reload()
	{
		O_Dao_TableInfo::get( $this->baseClass )->getFieldInfo( $this->baseField )->reload( 
				$this->baseId );
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
		
		$object->setField( $this->targetField, 0 );
		
		if ($delete)
			$object->delete();
		else
			$object->save();
		
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
			throw new O_Ex_Logic( "Cannot assign value to an existent dao object." );
		if (!$obj instanceof $this->targetClass)
			throw new O_Ex_WrongArgument( "Wrong object type for assignation." );
		if ($offset !== null)
			throw new O_Ex_Logic( "Can assign new value only with [] operator." );
			/* @var $obj O_Dao_ActiveRecord */
		$obj->setField( $this->targetField, $this->baseId );
		$this->reload();
		return $obj->save();
	}

}