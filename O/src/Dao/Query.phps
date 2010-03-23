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
 * @author Dmitry Kurinskiy
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
		try {
			$o = $this->select()->fetch();
		}
		catch (PDOException $e) {
			if (!O_Dao_TableInfo::get( $this->class )->tableExists()) {
				O_Dao_TableInfo::get( $this->class )->createTable();
				return null;
			}
			throw $e;
		}
		if ($o) {
			return O_Dao_ActiveRecord::getById( $o[ "id" ], $this->class, $o );
		}
		return null;
	}

	/**
	 * Returns (cached) array of query results as O_Dao_ActiveRecords
	 *
	 * @param bool $forceCacheReload If true, objects are regenerated
	 * @param bool $processPreload
	 * @return O_Dao_ActiveRecord[]
	 */
	public function getAll( $forceCacheReload = false, $processPreload = true )
	{
		if (!$forceCacheReload && count( $this->objects ))
			return $this->objects;
		try {
			$r = $this->select()->fetchAll();
		}
		catch (PDOException $e) {
			if (!O_Dao_TableInfo::get( $this->class )->tableExists()) {
				O_Dao_TableInfo::get( $this->class )->createTable();
				return null;
			}
			throw $e;
		}

		$this->objects = Array ();
		foreach ($r as $o) {
			$this->objects[ $o[ "id" ] ] = O_Dao_ActiveRecord::getById( $o[ "id" ], $this->class,
					$o );
		}
		// Process fields preload
		if ($processPreload) {
			$preloadFields = Array ();
			foreach (O_Dao_TableInfo::get( $this->class )->getFields() as $name => $fieldInfo) {
				/* @var $fieldInfo O_Dao_FieldInfo */
				if ($fieldInfo->isRelationOne() && $fieldInfo->getParam( "preload" )) {
					$preloadFields[] = $name;
				}
			}
			if (count( $preloadFields ))
				$this->preload( $preloadFields );
		}

		return $this->objects;
	}

	/**
	 * Preloads a number of fields relative to one target
	 *
	 * @param array $fieldsToPreload
	 */
	public function preload( $fields )
	{
		$preloadClasses = Array ();
		foreach ($fields as $fieldName) {
			$fieldInfo = O_Dao_TableInfo::get( $this->class )->getFieldInfo( $fieldName );
			if ($fieldInfo->isRelationOne() && $fieldInfo->getRelationTarget()) {
				$preloadClasses[ $fieldName ] = $fieldInfo->getRelationTarget();
			}
		}
		$objects = count( $this->objects ) ? $this->objects : $this->getAll( 0, 0 );
		$preloadIds = Array ();
		foreach ($objects as $o) {
			foreach ($preloadClasses as $field => $class) {
				if (!isset( $preloadIds[ $class ] ))
					$preloadIds[ $class ] = Array ();
				if (!in_array( $o[ $field ], $preloadIds[ $class ] ) && !O_Dao_ActiveRecord::objectLoaded(
						$o[ $field ], $class ))
					$preloadIds[ $class ][] = $o[ $field ];
			}
		}
		foreach ($preloadIds as $class => $ids)
			if (count( $ids )) {
				self::get( $class )->test( "id", $ids )->getAll();
			}
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
	 * Returns SQL COUNT(), SUM() etc
	 *
	 * @param string $field
	 * @param string $func
	 * @return int
	 */
	public function getFunc( $field = "*", $func = "COUNT" )
	{
		try {
			$q = clone $this;
			return $q->clearFields()->field( $func . "($field) AS c" )->select()->fetch(
					PDO::FETCH_OBJ )->c;
		}
		catch (PDOException $e) {
			if (!O_Dao_TableInfo::get( $this->class )->tableExists()) {
				O_Dao_TableInfo::get( $this->class )->createTable();
				return null;
			}
			throw $e;
		}
	}

	/**
	 * Shortcut for paginator constructor
	 *
	 * @param callback $url_callback
	 * @param int $perpage
	 * @param string $page_registry
	 * @return O_Dao_Paginator
	 */
	public function getPaginator( $url_callback, $perpage = null, $page_registry = "*paginator/page", array $orders = array(), $order_registry = "*paginator/order" )
	{
		return new O_Dao_Paginator( $this, $url_callback, $perpage, $page_registry, $orders,
				$order_registry );
	}

	/**
	 * Returns classname we're currently selecting from
	 *
	 * @return string
	 */
	public function getClass()
	{
		return $this->class;
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
	 * Returns true if an object is in result
	 *
	 * @param O_Dao_ActiveRecord|int $objectOrId
	 * @return bool
	 */
	public function has($objectOrId) {
		if($objectOrId instanceof $this->class) {
			$objectOrId = $objectOrId->id;
		}
		if(!is_numeric($objectOrId)) {
			return false;
		}
		$this->getAll();
		return array_key_exists($objectOrId, $this->objects);
	}

	/**
	 * Apply one or several callbacks on the query
	 *
	 * @return O_Dao_Query
	 */
	public function __invoke() {
		$args = func_get_args();
		foreach($args as $a) {
			// Substitute from registry. If it is a callback name,
			// it will be returned unmodified
			if(is_string($a)) {
				$a = O($a);
			}
			// Handle array of callbacks
			if(is_array($a)) {
				foreach($a as $_a) {
					is_array($_a) ? call_user_func_array($this, $_a) : call_user_func($this, $_a);
				}
			} elseif(is_callable($a)) {
				$a($this);
			}
		}
		return $this;
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
		throw new O_Ex_Logic( "Cannot set an offset to virtual query results array." );
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