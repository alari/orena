<?php
/**
 * Implements ActiveRecord pattern. Simply extend this class to create a new model.
 *
 * To see configuration rules and examples,
 * @see O_Dao_TableInfo
 *
 * Active records could be automatically rendered,
 * @see O_Dao_Renderer
 *
 * Active record models could be dynamically extended by plugins,
 * @see O_Dao_iPlugin
 *
 * Signals support is provided by
 * @see O_Dao_Signals
 *
 * To retrieve O_Dao_ActiveRecord objects, use
 * @see O_Dao_Query
 *
 * @author Dmitry Kurinskiy
 */
abstract class O_Dao_ActiveRecord implements ArrayAccess {
	/**
	 * All O_Dao_ActiveRecord objects loaded during current HTTP request.
	 *
	 * @var array
	 */
	private static $objs = array ();
	/**
	 * Injected methods for classes.
	 *
	 * @see O_Dao_iPlugin
	 * @var Array
	 */
	private static $injected_methods = Array ();

	/**
	 * Array of SQL field values of a database record
	 *
	 * @var array
	 */
	private $fields = array ();
	/**
	 * Changed, but not saved yet database values of fields
	 *
	 * @var array
	 */
	private $changed = array ();
	/**
	 * Deletion process marker
	 *
	 * @var int
	 */
	private $__is_deleted = 0;

	/**
	 * Creates a new object in database
	 *
	 */
	public function __construct()
	{
		$query = new O_Dao_Query( get_class( $this ) );
		if (count( $this->changed )) {
			foreach ($this->changed as $name => $value)
				$query->field( $name, $value );
		}
		try {
			$this->fields[ "id" ] = $query->insert();
			if (!$this->fields[ "id" ])
				throw new Exception( "Not inserted" );
		}
		catch (Exception $e) {
			$tableInfo = O_Dao_TableInfo::get( $this );
			if (!$tableInfo->tableExists()) {
				$tableInfo->createTable();
			}
			if ($tableInfo->tableExists()) {
				$this->fields[ "id" ] = $query->insert();
			} else {
				throw $e;
			}
		}

		$this->reload();
		$class = get_class( $this );
		self::$objs[ $class ][ $this->fields[ "id" ] ] = $this;

		if (O_Dao_TableInfo::get( $class )->getParam( "signal" )) {
			O_Dao_Signals::fire( O_Dao_Signals::EVENT_CREATE,
					O_Dao_TableInfo::get( $class )->getParam( "signal" ), $class, $this,
					$this->fields[ "id" ] );
		}
	}

	/**
	 * Magic functionality
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get( $name )
	{
		if ($name == "id")
			return $this->fields[ "id" ];
		if (strpos( $name, "." )) {
			list ($name, $subreq) = explode( ".", $name, 2 );
			return $this->getFieldInfo( $name )->getMappedQuery( $this,
					isset( $this->fields[ $name ] ) ? $this->fields[ $name ] : null, $subreq );
		}
		if (strpos( $name, "->" )) {
			$obj = $this;
			foreach (explode( "->", $name ) as $f) {
				if (!$obj instanceof self)
					return false;
				$obj = $obj->$f;
			}
			return $obj;
		}

		return $this->getFieldInfo( $name )->getValue( $this,
				isset( $this->fields[ $name ] ) ? $this->fields[ $name ] : null,
				array_key_exists( $name, $this->fields ) );
	}

	/**
	 * Works only with atomic and base-to-one
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set( $name, $value )
	{
		if ($name == "id")
			return;
		$this->getFieldInfo( $name )->setValue( $this, $value,
				array_key_exists( $name, $this->fields ) );
	}

	/**
	 * Clones an object by inserting the same one in database; keeping all many-to-* relations
	 *
	 */
	public function __clone()
	{
		$fields = Array ();
		foreach (O_Dao_TableInfo::get( get_class( $this ) )->getFields() as $fieldName => $fieldInfo) {
			/* @var $fieldInfo O_Dao_FieldInfo */
			if ($fieldInfo->isOneOf() || $fieldInfo->isAlias() || $fieldInfo->isRelative())
				continue;
			if ($fieldInfo->isOneToWhateverRelation())
				$fields[ $fieldName ] = null;
			else
				$fields[ $fieldName ] = $this->$fieldName;
		}
		$this->fields = Array ();
		$this->__construct();
		foreach ($fields as $fieldName => $fieldValue) {
			$this->$fieldName = $fieldValue;
		}
		$this->save();
	}

	/**
	 * Sets the field without any test
	 *
	 * @access package
	 * @param string $name
	 * @param mixed $value
	 */
	public function setField( $name, $value )
	{
		$this->changed[ $name ] = $value;
	}

	/**
	 * Sets the field value as is
	 *
	 * @param string $name
	 * @param string $value
	 * @return bool
	 */
	public function setScalarField( $name, $value )
	{
		$query = new O_Dao_Query( $this );
		if ($query->test( "id", $this->fields[ "id" ] )->field( $name, $value, true )->update()) {
			$this->fields[ $name ] = $query->clearFields()->field( $name )->select()->fetch(
					PDO::FETCH_OBJ )->$name;
			return true;
		}
		return false;
	}

	/**
	 * Returns field info object
	 *
	 * @param string $name
	 * @return O_Dao_FieldInfo
	 */
	private function getFieldInfo( $name )
	{
		$fieldInfo = O_Dao_TableInfo::get( get_class( $this ) )->getFieldInfo( $name );
		if (!$fieldInfo)
			throw new O_Ex_NotFound( "Unknown field: $name." );

		return $fieldInfo;
	}

	/**
	 * Saves the changes
	 *
	 * @return bool
	 */
	public function save()
	{
		if (!count( $this->changed ))
			return true;

		$query = new O_Dao_Query( $this );
		$query->test( "id", $this->fields[ "id" ] );

		foreach ($this->changed as $name => $value) {
			$query->field( $name, $value );
		}

		if ($query->update()) {
			foreach ($this->changed as $name => $value)
				$this->fields[ $name ] = $value;
			$this->changed = array ();
			return true;
		}

		return false;
	}

	/**
	 * Updates object fields from database
	 *
	 * @return true
	 */
	public function reload()
	{
		$this->changed = Array ();
		$query = new O_Dao_Query( $this );
		$this->fields = $query->test( "id", $this->fields[ "id" ] )->select()->fetch();
		foreach (O_Dao_TableInfo::get( get_class( $this ) )->getFields() as $fieldInfo)
			if (!$fieldInfo->isAtomic())
				$fieldInfo->reload( $this->id );
		return true;
	}

	/**
	 * Returns an object by its ID
	 *
	 * @param int $id
	 * @param string $class
	 * @param array $row
	 * @return O_Dao_ActiveRecord
	 */
	static public function getById( $id, $class, Array $row = null )
	{
		if (isset( self::$objs[ $class ][ $id ] ))
			return self::$objs[ $class ][ $id ];

		self::$objs[ $class ][ $id ] = unserialize(
				sprintf( 'O:%d:"%s":0:{}', strlen( $class ), $class ) );
		if (!isset( $row[ "id" ] )) {
			$query = new O_Dao_Query( $class );
			try {
				$row = $query->test( "id", $id )->select()->fetch();
			}
			catch (PDOException $e) {
				if (!O_Dao_TableInfo::get( $class )->tableExists()) {
					O_Dao_TableInfo::get( $class )->createTable();
				}
			}
		}
		if (isset( $row[ "id" ] ) && $row[ "id" ] == $id) {
			self::$objs[ $class ][ $id ]->fields = $row;
			return self::$objs[ $class ][ $id ];
		}
		return self::$objs[ $class ][ $id ] = null;
	}

	/**
	 * Checks if an object already was loaded
	 *
	 * @param int $id
	 * @param string $class
	 * @return bool
	 */
	static public function objectLoaded( $id, $class )
	{
		return isset( self::$objs[ $class ][ $id ] );
	}

	/**
	 * Deletes the object
	 *
	 */
	public function delete()
	{
		if ($this->__is_deleted)
			return;
		$this->__is_deleted = 1;
		$fields = O_Dao_TableInfo::get( $this )->getFields();
		foreach ($fields as $name => $field)
			$field->deleteThis( $this,
					isset( $this->fields[ $name ] ) ? $this->fields[ $name ] : null );

		$query = new O_Dao_Query( $this );
		$query->test( "id", $this->fields[ "id" ] )->delete();

		$class = get_class( $this );
		if (O_Dao_TableInfo::get( $class )->getParam( "signal" )) {
			O_Dao_Signals::fire( O_Dao_Signals::EVENT_DELETE,
					O_Dao_TableInfo::get( $class )->getParam( "signal" ), $class, $this,
					$this->fields[ "id" ] );
		}
	}

	/**
	 * Updates all objects after table altering
	 *
	 * @param string $class
	 * @access package
	 */
	static public function saveAndReload( $class )
	{
		if (isset( self::$objs[ $class ] ) && count( self::$objs[ $class ] )) {
			foreach (self::$objs[ $class ] as $id => $obj) {
				if ($obj instanceof self) {
					$obj->save();
					$obj->reload();
				} else
					unset( self::$objs[ $class ][ $id ] );
			}
		}
	}

	/**
	 * Makes method injection. $this is given for $callback as first parameter.
	 *
	 * @param string $class
	 * @param string $method_name
	 * @param callback $callback
	 * @throws O_Ex_WrongArgument
	 */
	static public function injectMethod( $class, $method_name, $callback )
	{
		if (is_callable( $callback ))
			self::$injected_methods[ $class ][ $method_name ] = $callback;
		else
			throw new O_Ex_WrongArgument( "Not a valid callback for injection: $callback." );
	}

	/**
	 * Returns all injected methods for specified class
	 *
	 * @param string $class
	 * @return array
	 */
	static public function getInjectedMethods( $class )
	{
		return isset( self::$injected_methods[ $class ] ) ? self::$injected_methods[ $class ] : Array ();
	}

	/**
	 * Magic functionality to handle calls to injected methods
	 *
	 * @param string $name
	 * @param array $arguments
	 * @return mixed or "" if method is not implemented
	 */
	public function __call( $name, $arguments )
	{
		if (isset( self::$injected_methods[ get_class( $this ) ][ $name ] )) {
			array_unshift( $arguments, $this );
			return call_user_func_array( self::$injected_methods[ get_class( $this ) ][ $name ],
					$arguments );
		}
		return "";
	}

	/**
	 * Shows ActiveRecord as HTML via O_Dao_Renderer
	 *
	 * @param O_Html_Layout $layout
	 * @see O_Dao_Renderer::show()
	 */
	public function show( O_Html_Layout $layout = null, $type = O_Dao_Renderer::TYPE_DEF )
	{
		O_Dao_Renderer::show( $this, $layout, $type );
	}

	/**
	 * Returns form handler instance for this active record
	 *
	 * @return O_Form_Handler
	 */
	public function form()
	{
		return new O_Form_Handler($this);
	}

	/**
	 * Shortcut for class field parameters getting
	 *
	 * @param string $fieldName
	 * @param string $paramName
	 * @param bool $parseAsArray
	 * @return string or array
	 */
	public function getParam( $paramName, $fieldName = null, $parseAsArray = 0 )
	{
		$tableInfo = O_Dao_TableInfo::get( get_class( $this ) );
		return $fieldName ? $tableInfo->getFieldInfo( $fieldName )->getParam( $paramName,
				$parseAsArray ) : $tableInfo->getParam( $fieldName, $parseAsArray );
	}

	/**
	 * Checks if a field exists
	 *
	 * @param string $offset
	 * @return bool
	 * @access private
	 */
	public function offsetExists( $offset )
	{
		return isset( $this->fields[ $offset ] );
	}

	/**
	 * Returns as-is value of the sql field
	 *
	 * @param string $offset
	 * @return string|int
	 * @access private
	 */
	public function offsetGet( $offset )
	{
		if (!$this->offsetExists( $offset ))
			return false;
		return $this->fields[ $offset ];
	}

	/**
	 * Changes the field value
	 *
	 * @param string $offset
	 * @param mixed $value
	 * @access private
	 */
	public function offsetSet( $offset, $value )
	{
		$this->setField( $offset, $value );
	}

	/**
	 * Does nothing
	 *
	 * @param string $offset
	 * @throws Exception
	 * @access private
	 */
	public function offsetUnset( $offset )
	{
		throw new O_Ex_Logic( "Cannot unset value of an ActiveRecord sql-field." );
	}

}
