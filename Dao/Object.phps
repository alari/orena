<?php
abstract class Dao_Object {
	private static $objs = array ();
	
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
		self::$objs[ get_class( $this ) ][ $this->fields[ "id" ] ] = $this;
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
			return $this->getFieldInfo( $name )->getMappedQuery( $this, isset( $this->fields[ $name ] ) ? $this->fields[ $name ] : null, $subreq );
		}
		
		return $this->getFieldInfo( $name )->getValue( $this, isset( $this->fields[ $name ] ) ? $this->fields[ $name ] : null, array_key_exists( $name, $this->fields ) );
	}

	/**
	 * Works only with atomic fields
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set( $name, $value )
	{
		if ($name == "id")
			return;
		
		if ($this->getFieldInfo( $name )->isAtomic()) {
			if ($this->fields[ $name ] != $value)
				$this->changed[ $name ] = $value;
		} else {
			$this->getFieldInfo( $name )->setValue( $this, $value );
		}
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
				if (array_key_exists( $name, $this->fields ))
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
	 * @return Dao_Object
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
	}

}
