<?php
abstract class Dao_ActiveRecord {
	private static $objs = array ();
	private static $injected_methods = Array ();

	private $fields = array ();
	private $changed = array ();

	/**
	 * Creates a new object in database
	 *
	 */
	public function __construct()
	{
		$query = new Dao_Query( $this );
		try {
			$this->fields[ "id" ] = $query->insert();
		}
		catch (PDOException $e) {
			$tableInfo = Dao_TableInfo::get( $this );
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
		if (Dao_TableInfo::get( $class )->getParam( "signal" )) {
			Dao_Signals::fire( Dao_Signals::EVENT_CREATE, Dao_TableInfo::get( $class )->getParam( "signal" ), $class,
					$this, $this->fields[ "id" ] );
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

		$this->getFieldInfo( $name )->setValue( $this, $value, array_key_exists( $name, $this->fields ) );
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
		$query = new Dao_Query( $this );
		if ($query->test( "id", $this->fields[ "id" ] )->field( $name, $value, true )->update()) {
			$this->fields[ $name ] = $value;
			return true;
		}
		return false;
	}

	/**
	 * Returns field info object
	 *
	 * @param string $name
	 * @return Dao_FieldInfo
	 */
	private function getFieldInfo( $name )
	{
		$fieldInfo = Dao_TableInfo::get( get_class( $this ) )->getFieldInfo( $name );
		if (!$fieldInfo)
			throw new Exception( "Unknown field: $name." );

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

		$query = new Dao_Query( $this );
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
		$query = new Dao_Query( $this );
		$this->fields = $query->test( "id", $this->fields[ "id" ] )->select()->fetch();
		foreach (Dao_TableInfo::get( get_class( $this ) )->getFields() as $fieldInfo)
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
	 * @return Dao_ActiveRecord
	 */
	static public function getById( $id, $class, Array $row = null )
	{
		if (isset( self::$objs[ $class ][ $id ] ))
			return self::$objs[ $class ][ $id ];

		self::$objs[ $class ][ $id ] = unserialize( sprintf( 'O:%d:"%s":0:{}', strlen( $class ), $class ) );
		if (!isset( $row[ "id" ] )) {
			$query = new Dao_Query( $class );
			$row = $query->test( "id", $id )->select()->fetch();
		}
		if (isset( $row[ "id" ] ) && $row[ "id" ] == $id) {
			self::$objs[ $class ][ $id ]->fields = $row;
			return self::$objs[ $class ][ $id ];
		}
		return self::$objs[ $class ][ $id ] = null;
	}

	/**
	 * Delete the object
	 *
	 */
	public function delete()
	{
		$fields = Dao_TableInfo::get( $this )->getFields();
		foreach ($fields as $name => $field)
			$field->deleteThis( $this, isset( $this->fields[ $name ] ) ? $this->fields[ $name ] : null );

		$query = new Dao_Query( $this );
		$query->test( "id", $this->fields[ "id" ] )->delete();

		$class = get_class( $this );
		if (Dao_TableInfo::get( $class )->getParam( "signal" )) {
			Dao_Signals::fire( Dao_Signals::EVENT_DELETE, Dao_TableInfo::get( $class )->getParam( "signal" ), $class,
					$this, $this->fields[ "id" ] );
		}
	}

	/**
	 * Update all objects after table altering
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
	 * @throws Exception
	 */
	static public function injectMethod( $class, $method_name, $callback )
	{
		if (is_callable( $callback ))
			self::$injected_methods[ $class ][ $method_name ] = $callback;
		else
			throw new Exception( "Not a valid callback for injection: $callback." );
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
			return call_user_func_array( self::$injected_methods[ get_class( $this ) ][ $name ], $arguments );
		}
		return "";
	}

}
