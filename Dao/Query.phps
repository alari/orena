<?php
class Dao_Query extends Db_Query implements ArrayAccess, Iterator {
	
	protected $class;
	protected $objects = Array();
	
	/**
	 * Creates query instance for a class
	 *
	 * @param string $class
	 * @param string $alias
	 */
	public function __construct( $class, $alias = null )
	{
		if (is_object( $class ))
			$class = get_class( $class );
		$this->class = $class;
		parent::__construct( Dao_TableInfo::get( $class )->getTableName(), $alias );
	}
	
	/**
	 * Sets the class and table to select objects
	 *
	 * @param string $class
	 * @param string $alias
	 * @return Dao_Query
	 */
	public function from( $class, $alias = null )
	{
		if (is_object( $class ))
			$class = get_class( $class );
		$this->class = $class;
		return parent::from( Dao_TableInfo::get( $class )->getTableName(), $alias );
	}
	
	/**
	 * Returns first Dao_Object from query
	 *
	 * @return Dao_Object
	 */
	public function getOne()
	{
		$o = $this->select()->fetch();
		if ($o) {
			return Dao_Object::getById( $o[ "id" ], $this->class, $o );
		}
		return null;
	}
	
	/**
	 * Returns (cached) array of query results as Dao_Objects
	 *
	 * @param bool $forseCacheReload If true, objects are regenerated
	 * @return Dao_Object[]
	 */
	public function getAll( $forse = false )
	{
		if (!$forse && count( $this->objects ))
			return $this->objects;
		$r = $this->select()->fetchAll();
		$this->objects = Array();
		foreach ($r as $o)
			$this->objects[ $o[ "id" ] ] = Dao_Object::getById( $o[ "id" ], $this->class, $o );
		return $this->objects;
	}
	
	/**
	 * For Iterator
	 *
	 * @return bool
	 * @access private
	 */
	public function rewind()
	{
		$this->getAll();
		return reset( $this->objects );
	}
	
	/**
	 * For Iterator
	 *
	 * @return Dao_Object
	 * @access private
	 */
	public function current()
	{
		$this->getAll();
		return current( $this->objects );
	}
	
	/**
	 * For Iterator
	 *
	 * @return int
	 * @access private
	 */
	public function key()
	{
		$this->getAll();
		return key( $this->objects );
	}
	
	/**
	 * For Iterator
	 *
	 * @return Dao_Object
	 * @access private
	 */
	public function next()
	{
		$this->getAll();
		return next( $this->objects );
	}
	
	/**
	 * For Iterator
	 *
	 * @return bool
	 * @access private
	 */
	public function valid()
	{
		$this->getAll();
		return $this->current() !== false;
	}
	
	/**
	 * Checks if object exists
	 *
	 * @param int $offset
	 * @return bool
	 * @access private
	 */
	public function offsetExists( $offset )
	{
		$this->getAll();
		return isset( $this->objects[ $offset ] );
	}
	
	/**
	 * Returns object
	 *
	 * @param int $offset
	 * @return string
	 * @access private
	 */
	public function offsetGet( $offset )
	{
		$this->getAll();
		if (!$this->offsetExists( $offset ))
			return false;
		return $this->objects[ $offset ];
	}
	
	/**
	 * Does nothing
	 *
	 * @param int $offset
	 * @param object $obj
	 * @throws Exception
	 * @access private
	 */
	public function offsetSet( $offset, $obj )
	{
		throw new Exception( "Cannot set an offset to virtual query results array." );
	}
	
	/**
	 * Just removes object from an array
	 *
	 * @param int $offset
	 * @access private
	 */
	public function offsetUnset( $offset )
	{
		unset( $this->objects[ $offset ] );
	}
}