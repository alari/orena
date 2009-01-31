<?php
/**
 * Query builder extension to ease work with Dao_ActiveRecord sets.
 *
 * @see Db_Query
 *
 * Dao_Query could be automatically rendered,
 * @see Dao_Renderer
 * @see Dao_Query::display()
 *
 * @author Dmitry Kourinski
 */
class Dao_Query extends Db_Query implements ArrayAccess, Iterator {
	/**
	 * Classname we're selecting objects from
	 *
	 * @var string
	 */
	protected $class;
	/**
	 * Array of objects given by getAll() method, used for Iterator and ArrayAccess
	 *
	 * @var array
	 */
	protected $objects = Array ();

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
	 * Returns first Dao_ActiveRecord from query
	 *
	 * @return Dao_ActiveRecord
	 */
	public function getOne()
	{
		$o = $this->select()->fetch();
		if ($o) {
			return Dao_ActiveRecord::getById( $o[ "id" ], $this->class, $o );
		}
		return null;
	}

	/**
	 * Returns (cached) array of query results as Dao_ActiveRecords
	 *
	 * @param bool $forceCacheReload If true, objects are regenerated
	 * @return Dao_ActiveRecord[]
	 */
	public function getAll( $forceCacheReload = false )
	{
		if (!$forceCacheReload && count( $this->objects ))
			return $this->objects;
		$r = $this->select()->fetchAll();
		$this->objects = Array ();
		foreach ($r as $o)
			$this->objects[ $o[ "id" ] ] = Dao_ActiveRecord::getById( $o[ "id" ], $this->class, $o );
		return $this->objects;
	}

	/**
	 * Displays all objects from getAll() in a loop
	 *
	 * @param Html_Layout $layout
	 */
	public function display( Html_Layout $layout = null )
	{
		Dao_Renderer::showLoop( $this, $layout );
	}

	/**
	 * Disables statements preparing for particular class table use
	 *
	 * @param string $class
	 */
	static public function disablePreparing( $class )
	{
		parent::disablePreparing( Dao_TableInfo::get( $class )->getTableName() );
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
	 * @return Dao_ActiveRecord
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
	 * @return Dao_ActiveRecord
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