<?php
/**
 * Class to store and parse configuration of O_Dao_ActiveRecord subclass.
 *
 * It parses the classes PHPDoc and finds config params there
 * Configs looks like this:
 * @{directive} value -key1 value 1 -key-2 value 2 \
 * 		-key-3 value 3 ...
 *
 * Backslash is used for multiline configuration.
 *
 * Possible directives:
 * @table [sqlTableName or -]
 * @field name [type]
 * @field:config name [keys]
 * @field:replace field1 field2
 * @index field1[, field2, ...] [-name index_name] [-(unique|fulltext)]
 * @tail tail-directives
 * @registry key value
 *
 * Possible params of table and fields are used and described in other classes, e.g.
 * @see O_Dao_Renderer
 *
 * Description of fields declaration:
 * @see O_Dao_FieldInfo
 *
 * @author Dmitry Kurinskiy
 */
class O_Dao_TableInfo {
	/**
	 * Array of constructed table info objects
	 *
	 * @var O_Dao_TableInfo[]
	 */
	private static $conf = Array ();
	
	/**
	 * Name of sql table data stored in
	 *
	 * @var string
	 */
	private $table;
	/**
	 * Array of fields infos
	 *
	 * @var O_Dao_FieldInfo[]
	 */
	private $fields = Array ();
	/**
	 * Classname this tableinfo is provided for
	 *
	 * @var string
	 */
	private $class;
	/**
	 * Table indexes described in config
	 *
	 * @var array
	 */
	private $indexes = Array ();
	/**
	 * Whole-table (or whole-class) Dao params
	 *
	 * @var array
	 */
	private $params = Array ();
	/**
	 * Tail directives for concrete sql table
	 *
	 * @var unknown_type
	 */
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
		if (!$reflection->isSubclassOf( "O_Dao_ActiveRecord" ))
			return;
			
		// Copy all data from parent object
		if ($reflection->getParentClass()) {
			$parent = self::get( $reflection->getParentClass()->getName() );
			
			$this->table = $parent->table;
			foreach ($parent->fields as $name => $info) {
				$this->fields[ $name ] = clone $info;
				$this->fields[ $name ]->setClass( $this->class );
			}
			$this->params = $parent->params;
			$this->indexes = $parent->indexes;
			$this->tail = $parent->tail;
		}
		
		// Inherited injections
		foreach (O_Dao_ActiveRecord::getInjectedMethods( 
				$reflection->getParentClass()->getName() ) as $name => $callback) {
			O_Dao_ActiveRecord::injectMethod( $class, $name, $callback );
		}
		
		$docCommentLines = Array ();
		
		// Import data from plugins
		$plugins = O_Registry::get( "plugins", $class );
		if (is_array( $plugins )) {
			foreach ($plugins as $plugin) {
				if (!class_exists( $plugin ))
					throw new O_Ex_NotFound( "Unexistent plugin class: $plugin." );
				$pluginReflection = new ReflectionClass( $plugin );
				if (!$pluginReflection->implementsInterface( "O_Dao_iPlugin" ))
					throw new O_Ex_Logic( 
							"Dao plugins must implement interface O_Dao_iPlugin, but $plugin doesn't." );
					
				// Methods injection
				foreach ($pluginReflection->getMethods() as $method) {
					if (substr( $method->getName(), 0, 2 ) != "i_")
						continue;
					if (!$method->isPublic())
						continue;
					if (!$method->isStatic())
						continue;
					if ($method->getNumberOfParameters() < 1)
						continue;
					O_Dao_ActiveRecord::injectMethod( $class, substr( $method->getName(), 2 ), 
							array ($plugin, $method->getName()) );
				}
				
				// Attributes injection
				$docCommentLines = array_merge( $docCommentLines, 
						explode( "\n", $pluginReflection->getDocComment() ) );
			}
		}
		
		// Override
		$docCommentLines = array_merge( $docCommentLines, 
				explode( "\n", $reflection->getDocComment() ) );
		for ($line = current( $docCommentLines ); $line; $line = next( $docCommentLines )) {
			$matches = Array ();
			preg_match( "/@(table|field|index|tail|field:config|field:replace|registry) (.*)/", 
					$line, $matches );
			if ($matches) {
				$lineDirective = $matches[ 1 ];
				$lineContent = trim( $matches[ 2 ] );
				
				// Processing multiline config
				while ($lineContent[ strlen( $lineContent ) - 1 ] == '\\') {
					$line = next( $docCommentLines );
					$lineContent = substr( $lineContent, 0, -1 ) . " " . trim( 
							substr( $line, 2 ) );
				}
				
				// Processing tail directive before parsing subkeys (tail have no subkeys)
				if ($lineDirective == "tail") {
					$this->tail = $lineContent;
					continue;
				}
				
				$_subkeys = explode( " -", $lineContent );
				
				$value = array_shift( $_subkeys );
				$subkeys = array ();
				if (count( $_subkeys )) {
					foreach ($_subkeys as $v) {
						$v = explode( " ", $v, 2 );
						$subkeys[ trim( $v[ 0 ] ) ] = isset( $v[ 1 ] ) ? trim( $v[ 1 ] ) : 1;
					}
				}
				switch ($lineDirective) {
					case "table" :
						// To give ability to override params without overriding table name
						if ($value)
							$this->table = self::getPrefix() . $value;
						$this->params = array_merge( $this->params, $subkeys );
					break;
					
					case "field" :
						$name = $value;
						$type = null;
						if (strpos( $name, " " ))
							list ($name, $type) = explode( " ", $value, 2 );
						$this->fields[ $name ] = new O_Dao_FieldInfo( $this->class, $name, 
								$type, $subkeys );
					break;
					
					case "field:config" :
						$name = $value;
						if ((isset( $this->fields[ $name ] ))) {
							$this->fields[ $name ]->addParams( $subkeys );
						} else
							throw new O_Ex_Config( "field:config for unexistent field." );
					break;
					
					case "field:replace" :
						// Just replace field positions in array
						if (!strpos( $value, "," ))
							throw new O_Ex_Config( 
									"field:replace requires two fields, separated with comma. Comma not found." );
						list ($field1, $field2) = explode( ",", $value, 2 );
						$field1 = trim( $field1 );
						$field2 = trim( $field2 );
						if (!isset( $this->fields[ $field1 ] ) || !isset( 
								$this->fields[ $field2 ] ))
							throw new O_Ex_Config( "field:replace for unexistent field." );
						$tmp_fields = $this->fields;
						$this->fields = array ();
						foreach ($tmp_fields as $name => $info) {
							if ($name == $field1)
								$this->fields[ $field2 ] = $tmp_fields[ $field2 ];
							elseif ($name == $field2)
								$this->fields[ $field1 ] = $tmp_fields[ $field1 ];
							else
								$this->fields[ $name ] = $info;
						}
					break;
					
					case "index" :
						$this->indexes[ $value ] = $subkeys;
					break;
					
					case "registry" :
						list ($key, $value) = explode( " ", $value, 2 );
						if (isset( $subkeys[ "add" ] )) {
							O_Registry::add( $key, $value );
						} else {
							O_Registry::set( $key, $value );
						}
					break;
				}
			}
		}
	}

	/**
	 * Returns param given in @table config line
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function getParam( $name )
	{
		return isset( $this->params[ $name ] ) ? $this->params[ $name ] : null;
	}

	/**
	 * Checks if table exists in database
	 *
	 * @return bool
	 */
	public function tableExists()
	{
		if (!$this->table)
			throw new O_Ex_Config( "Table name isn't specified for " . $this->class );
		return O_Db_Query::get( $this->table )->tableExists();
	}

	/**
	 * Tries to create table
	 *
	 * @return PDOStatement with the result of CREATE TABLE request
	 */
	public function createTable()
	{
		if (!$this->table)
			throw new O_Ex_Config( "Can't create unnamed table." );
		
		$query = new O_Db_Query( $this->table );
		
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
		
		return $query->create( 
				$this->tail ? $this->tail : O_Registry::get( "app/dao-params/default_tail" ) );
	}

	/**
	 * Returns field info object
	 *
	 * @param string $name
	 * @return O_Dao_FieldInfo or null
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
	 * @return O_Dao_FieldInfo[]
	 */
	public function getFields()
	{
		return $this->fields;
	}

	/**
	 * Returns instance of table info object
	 *
	 * @param string $class
	 * @return O_Dao_TableInfo
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
			O_Registry::set( "app/dao-params/table_prefix", "" );
			return;
		}
		if (substr( $prefix, -1 ) != "_")
			$prefix .= "_";
		O_Registry::set( "app/dao-params/table_prefix", $prefix );
	}

	/**
	 * Returns the prefix for all table names
	 *
	 * @return string
	 */
	static public function getPrefix()
	{
		return O_Registry::get( "app/dao-params/table_prefix" );
	}

	/**
	 * Sets default query tail to be used in CREATE TABLE
	 *
	 * @param string $tail like ENGINE=InnoDB
	 */
	static public function setDefaultTail( $tail )
	{
		O_Registry::set( "app/dao-params/default_tail", $tail );
	}
}
