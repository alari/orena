<?php
/**
 * Query builder extension to ease work with O_Dao_ActiveRecord sets.
 *
 * @see O_Db_Query
 *
 * O_Dao_Query could be automatically rendered,
 * @see O_Dao_Renderer
 * @see O_Dao_Query::display()
 *
 * @author Dmitry Kourinski
 */
class O_Dao_Query extends O_Db_Query implements ArrayAccess, Iterator {
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
		parent::__construct( O_Dao_TableInfo::get( $class )->getTableName(), $alias );
	}

	/**
	 * Shortcut for constructor
	 *
	 * @param string $class
	 * @param string $alias
	 * @return O_Dao_Query
	 */
	static public function get( $class, $alias = null )
	{
		return new self( $class, $alias );
	}

	/**
	 * Sets the class and table to select objects
	 *
	 * @param string $class
	 * @param string $alias
	 * @return O_Dao_Query
	 */
	public function from( $class, $alias = null )
	{
		if (is_object( $class ))
			$class = get_class( $class );
		$this->class = $class;
		return parent::from( O_Dao_TableInfo::get( $class )->getTableName(), $alias );
	}

	/**
	 * Returns first O_Dao_ActiveRecord from query
	 *
	 * @return O_Dao_ActiveRecord
	 */
	public function getOne()
	{
		$o = $this->select()->fetch();
		if ($o) {
			return O_Dao_ActiveRecord::getById( $o[ "id" ], $this->class, $o );
		}
		return null;
	}

	/**
	 * Returns (cached) array of query results as O_Dao_ActiveRecords
	 *
	 * @param bool $forceCacheReload If true, objects are regenerated
	 * @return O_Dao_ActiveRecord[]
	 */
	public function getAll( $forceCacheReload = false )
	{
		// TODO: add whatever-to-one relations preloading
		if (!$forceCacheReload && count( $this->objects ))
			return $this->objects;
		$r = $this->select()->fetchAll();
		$this->objects = Array ();
		foreach ($r as $o)
			$this->objects[ $o[ "id" ] ] = O_Dao_ActiveRecord::getById( $o[ "id" ], $this->class, $o );
		return $this->objects;
	}

	/**
	 * Displays all objects from getAll() in a loop
	 *
	 * @param O_Html_Layout $layout
	 * @see O_Dao_Renderer::showLoop()
	 */
	public function show( O_Html_Layout $layout = null, $type = O_Dao_Renderer::TYPE_LOOP )
	{
		O_Dao_Renderer::showLoop( $this, $layout, $type );
	}

	/**
	 * Disables statements preparing for particular class table use
	 *
	 * @param string $class
	 */
	static public function disablePreparing( $class )
	{
		parent::disablePreparing( O_Dao_TableInfo::get( $class )->getTableName() );
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
	 * @return O_Dao_ActiveRecord
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
	 * @return O_Dao_ActiveRecord
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