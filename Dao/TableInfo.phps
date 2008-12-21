<?php
class Dao_TableInfo {
	
	private static $conf = Array ();
	private static $prefix = "";
	private static $default_tail = "";
	
	private $table;
	private $fields = Array ();
	private $class;
	private $indexes = Array ();
	
	private $tail = "";

	/**
	 * Uses recursion to get table config
	 *
	 * @param string $class
	 */
	private function __construct( $class )
	{
		$this->class = $class;
		
		$reflection = new ReflectionClass( $class );
		if (!$reflection->isSubclassOf( "Dao_Object" ))
			return;
			
		// Copy all data from parent object
		if ($reflection->getParentClass()) {
			$parent = self::get( $reflection->getParentClass()->getName() );
			
			$this->table = $parent->table;
			foreach ($parent->fields as $name => $info) {
				$this->fields[ $name ] = clone $info;
				$this->fields[ $name ]->setClass( $this->class );
			}
			$this->indexes = $parent->indexes;
			$this->tail = $parent->tail;
		}
		
		// Override
		foreach (explode( "\n", $reflection->getDocComment() ) as $line) {
			$matches = Array ();
			preg_match( "/@(table|field|index|tail) (.*)/", $line, $matches );
			if ($matches) {
				if ($matches[ 1 ] == "tail") {
					$this->tail = trim( $matches[ 2 ] );
					continue;
				}
				$_subkeys = explode( " -", trim( $matches[ 2 ] ) );
				$value = array_shift( $_subkeys );
				$subkeys = array ();
				if (count( $_subkeys )) {
					foreach ($_subkeys as $v) {
						$v = explode( " ", $v, 2 );
						$subkeys[ trim( $v[ 0 ] ) ] = isset( $v[ 1 ] ) ? $v[ 1 ] : 1;
					}
				}
				switch ($matches[ 1 ]) {
					case "table" :
						$this->table = self::$prefix . $value;
					break;
					case "field" :
						$name = $value;
						$type = null;
						if (strpos( $name, " " ))
							list ($name, $type) = explode( " ", $value, 2 );
						$this->fields[ $name ] = new Dao_FieldInfo( $this->class, $name, $type, $subkeys );
					break;
					case "index" :
						$this->indexes[ $value ] = $subkeys;
					break;
				}
			}
		}
	}

	/**
	 * Checks if table exists in database
	 *
	 * @return bool
	 */
	public function tableExists()
	{
		if (!$this->table)
			throw new Exception( "Table name isn't specified for " . $this->class );
		$stmt = Db_Manager::getConnection()->query( "SHOW TABLE STATUS WHERE name = '" . $this->table . "'" );
		if (!$stmt)
			return false;
		$stmt->execute();
		return (bool)count( $stmt->fetchAll() );
	}

	/**
	 * Tries to create table
	 *
	 * @return PDOStatement with the result of CREATE TABLE request
	 */
	public function createTable()
	{
		if (!$this->table)
			throw new Exception( "Can't create unnamed table." );
		
		$query = new Db_Query( $this->table );
		
		$query->field( "id", "int auto_increment primary key" );
		
		foreach ($this->fields as $fieldInfo) {
			$fieldInfo->addFieldTypeToQuery( $query );
		}
		
		foreach ($this->indexes as $fields => $keys) {
			$indexType = "index";
			if (isset( $keys[ "unique" ] ))
				$indexType = "unique";
			if (isset( $keys[ "fulltext" ] ))
				$indexType = "fulltext";
			$query->index( $fields, $indexType, isset( $keys[ "name" ] ) ? $keys[ "name" ] : null );
		}
		
		return $query->create( $this->tail ? $this->tail : self::$default_tail );
	}

	/**
	 * Returns field info object
	 *
	 * @param string $name
	 * @return Dao_FieldInfo
	 */
	public function getFieldInfo( $name )
	{
		return isset( $this->fields[ $name ] ) ? $this->fields[ $name ] : null;
	}

	/**
	 * Returns table name
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return $this->table;
	}

	/**
	 * Returns array of field info
	 *
	 * @return Dao_FieldInfo[]
	 */
	public function getFields()
	{
		return $this->fields;
	}

	/**
	 * Returns instance of table info object
	 *
	 * @param string $class
	 * @return Dao_TableInfo
	 */
	static public function get( $class )
	{
		if (is_object( $class ))
			$class = get_class( $class );
		if (!isset( self::$conf[ $class ] ))
			self::$conf[ $class ] = new self( $class );
		return self::$conf[ $class ];
	}

	/**
	 * Sets the prefix for all table names
	 *
	 * @param string $prefix
	 */
	static public function setPrefix( $prefix )
	{
		if (!$prefix) {
			self::$prefix = "";
			return;
		}
		if (substr( $prefix, -1 ) != "_")
			$prefix .= "_";
		self::$prefix = $prefix;
	}

	/**
	 * Returns the prefix for all table names
	 *
	 * @return string
	 */
	static public function getPrefix()
	{
		return self::$prefix;
	}

	/**
	 * Sets default query tail to be used in CREATE TABLE
	 *
	 * @param string $tail like ENGINE=InnoDB
	 */
	static public function setDefaultTail( $tail )
	{
		self::$default_tail = $tail;
	}
}
