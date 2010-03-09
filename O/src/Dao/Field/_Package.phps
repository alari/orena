<?php
/**
 * Package for dao field handlers
 */

/**
 * This interface is to be implemented in all handlers
 *
 */
interface O_Dao_Field_iFace {

	/**
	 * Sets the value with all tests provided
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @throws Exception
	 * @return bool
	 * @access private
	 */
	public function setValue( O_Dao_ActiveRecord $obj, $fieldValue, $fieldExists );

	/**
	 * Returns the field value, even if it's a relation or aliased query
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @return mixed
	 * @access private
	 */
	public function getValue( O_Dao_ActiveRecord $obj, $fieldValue, $fieldExists );

	/**
	 * Handles deletion of object -- or just of relation
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @access private
	 */
	public function deleteThis( O_Dao_ActiveRecord $obj, $fieldValue = null );

	/**
	 * Adds field to CREATE query
	 *
	 * @param O_Db_Query $query
	 * @access private
	 */
	public function addFieldTypeToQuery( O_Db_Query $query );

	/**
	 * Reloads field's cache for the object
	 *
	 * @param int $obj_id
	 */
	public function reload( $obj_id );

	/**
	 * Sets the class
	 *
	 * @see O_Dao_TableInfo::__construct()
	 * @access package
	 * @param string $class
	 */
	public function setFieldInfo( O_Dao_FieldInfo $fieldInfo );

}

/**
 * Relations interface
 *
 */
interface O_Dao_Field_iRelation {

	/**
	 * FieldInfo of reverse field
	 *
	 * @return O_Dao_FieldInfo
	 * @access private
	 */
	public function getInverse();

	/**
	 * Returns relation target classname
	 *
	 * @return string
	 */
	public function getTargetClass();

}

/**
 * Basic fields operations and abstracts
 *
 */
abstract class O_Dao_Field_Bases {

	/**
	 * Reloads field's cache for the object
	 *
	 * @param int $obj_id
	 */
	public function reload( $obj_id )
	{
	}

	/**
	 * No special actions should be done on atomic field deletion
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @access private
	 */
	public function deleteThis( O_Dao_ActiveRecord $obj, $fieldValue = null )
	{
		return;
	}

	/**
	 * Adds field to CREATE query
	 *
	 * @param O_Db_Query $query
	 * @access private
	 */
	public function addFieldTypeToQuery( O_Db_Query $query )
	{
	}

	/**
	 * Returns prepared target class name
	 *
	 * @param string $targetBase
	 * @return string
	 */
	protected function getTargetByBase( $targetBase )
	{
		if ($targetBase[ 0 ] == "{" && $targetBase[ strlen( $targetBase ) - 1 ] == "}") {
			return O_Registry::get( "app/" . substr( $targetBase, 1, -1 ) );
		} elseif ($targetBase[ 0 ] == "_") {
			return O_Registry::get( "app/class_prefix" ) . "_Mdl" . $targetBase;
		} else {
			return $targetBase;
		}
	}

}

/**
 * Simple atomic field
 * @author alari
 *
 */
class O_Dao_Field_Atomic extends O_Dao_Field_Bases implements O_Dao_Field_iFace {
	/**
	 * Field info instance for the field
	 *
	 * @var O_Dao_FieldInfo
	 */
	protected $fieldInfo;
	/**
	 * Database's fieldtype
	 *
	 * @var string
	 */
	protected $type;
	/**
	 * Database's field name
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Was this field added to sql-table this time or not
	 *
	 * @var bool
	 */
	private $isAdded = 0;

	/**
	 * Enumeration middleware
	 *
	 * @var bool
	 */
	private $isEnumerated = 0;

	public function __construct( O_Dao_FieldInfo $fieldInfo, $type, $name )
	{
		$this->fieldInfo = $fieldInfo;
		$this->type = $type;
		$this->name = $name;
		$this->isEnumerated = (bool)$fieldInfo->getParam( "enum" );

		if (!$type)
			throw new O_Ex_Config( "Cannot initiate atomic field without type ($this->name)" );
	}

	/**
	 * Sets the value with all tests provided
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @throws Exception
	 * @return bool
	 * @access private
	 */
	public function setValue( O_Dao_ActiveRecord $obj, $fieldValue, $fieldExists )
	{
		if (!$fieldExists) {
			$this->addFieldToTable();
		}
		if ($this->isEnumerated) {
			$v = $this->fieldInfo->getParam( "enum", 1 );
			$v = array_search( $fieldValue, $v );
			if ($v === false) {
				if (array_key_exists( $fieldValue, $this->fieldInfo->getParam( "enum", 1 ) ))
					$v = $fieldValue;
				else
					throw new O_Ex_WrongArgument(
							"Cannot assign \"$fieldValue\" to enumerated atomic field {$this->name}." );
			}
			$fieldValue = $v;
		}
		return $obj[ $this->name ] = $fieldValue;
	}

	/**
	 * Returns the field value
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @return mixed
	 * @access private
	 */
	public function getValue( O_Dao_ActiveRecord $obj, $fieldValue, $fieldExists )
	{
		if (!$fieldExists) {
			$this->addFieldToTable();
		}
		if ($this->isEnumerated) {
			$v = $this->fieldInfo->getParam( "enum", 1 );
			return isset( $v[ $fieldValue ] ) ? $v[ $fieldValue ] : null;
		}
		return $fieldValue;
	}

	/**
	 * Sets the field info
	 *
	 * @see O_Dao_TableInfo::__construct()
	 * @access package
	 * @param string $class
	 */
	public function setFieldInfo( O_Dao_FieldInfo $fieldInfo )
	{
		$this->fieldInfo = $fieldInfo;
		$this->isEnumerated = (bool)$fieldInfo->getParam( "enum" );
	}

	/**
	 * Alters table to add unexistent field
	 *
	 * @return PDOStatement
	 */
	protected function addFieldToTable()
	{
		if ($this->isAdded)
			return null;
		try {
			$q = new O_Dao_Query( $this->fieldInfo->getClass() );
			$this->addFieldTypeToQuery( $q );
			$r = $q->alter( "ADD" );
			O_Dao_ActiveRecord::saveAndReload( $this->fieldInfo->getClass() );
			O_Dao_Query::disablePreparing( $this->fieldInfo->getClass() );
			$this->isAdded = true;
		}
		catch (PDOException $e) {
			if (O_Registry::get( "app/mode" ) == "debug")
				throw $e;
			return null;
		}
		return $r;
	}

	/**
	 * Adds field to CREATE query
	 *
	 * @param O_Db_Query $query
	 * @access private
	 */
	public function addFieldTypeToQuery( O_Db_Query $query )
	{
		$query->field( $this->name, $this->type );
	}

}

/**
 * Field alias to a query
 * @author alari
 *
 */
class O_Dao_Field_Alias extends O_Dao_Field_Bases implements O_Dao_Field_iFace {
	/**
	 * Field info instance for the field
	 *
	 * @var O_Dao_FieldInfo
	 */
	private $fieldInfo;
	/**
	 * String map for alias field
	 * e.g. fieldA.fieldB means relation field A which targets object whith relation field B
	 * Query with B will be returned
	 *
	 * @var string
	 */
	private $alias;
	/**
	 * Field to test to get concrete alias instance
	 *
	 * @var string
	 */
	private $testField;
	/**
	 * Query for alias
	 *
	 * @var O_Dao_Query
	 */
	private $query;

	/**
	 * Cached queries for concrete objects
	 *
	 * @var O_Dao_Query[]
	 */
	private $objQueries = Array();

	/**
	 * Constructor
	 *
	 * @param O_Dao_FieldInfo $fieldInfo
	 * @param string $alias
	 */
	public function __construct( O_Dao_FieldInfo $fieldInfo, $alias )
	{
		$this->fieldInfo = $fieldInfo;
		$this->alias = $alias;
	}

	/**
	 * @throws O_Ex_Critical
	 * @access private
	 */
	public function setValue( O_Dao_ActiveRecord $obj, $fieldValue, $fieldExists )
	{
		throw new O_Ex_Critical( "Cannot assign to aliases." );
	}

	/**
	 * Returns built and cached aliased query
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @return O_Dao_Query
	 * @access private
	 */
	public function getValue( O_Dao_ActiveRecord $obj, $fieldValue, $fieldExists )
	{
		if (!$this->query) {
			list ($name, $subreq) = explode( ".", $this->alias, 2 );
			$this->testField = O_Dao_TableInfo::get( $this->fieldInfo->getClass() )->getFieldInfo(
					$name )->prepareMappedQuery( $this->query, $subreq );

			if ($this->fieldInfo->getParam( "where" ) && $this->query instanceof O_Dao_Query) {
				$this->query->where( $this->fieldInfo->getParam( "where" ) );
			}
			if ($this->fieldInfo->getParam( "order-by" ) && $this->query instanceof O_Dao_Query) {
				$this->query->orderBy( $this->fieldInfo->getParam( "order-by" ) );
			}
		}
		if (!$this->query instanceof O_Dao_Query) {
			throw new O_Ex_Critical( "Wrong mapped query is produced by $name.$subreq map." );
		}
		if(!$fieldValue) {
			$fieldValue = $obj->id;
		}
		if(!isset($this->objQueries[$fieldValue])) {
			$this->objQueries[$fieldValue] = clone $this->query;
			$this->objQueries[$fieldValue]->test($this->testField, $fieldValue);
		}
		return $this->objQueries[$fieldValue];
	}

	/**
	 * Sets the field info
	 *
	 * @see O_Dao_TableInfo::__construct()
	 * @access package
	 * @param string $class
	 */
	public function setFieldInfo( O_Dao_FieldInfo $fieldInfo )
	{
		$this->fieldInfo = $fieldInfo;
	}

}

/**
 * File storage
 * @author alari
 *
 */
class O_Dao_Field_File extends O_Dao_Field_Atomic {

	public function __construct( O_Dao_FieldInfo $fieldInfo, $type, $name )
	{
		$this->fieldInfo = $fieldInfo;
		$this->type = $type;
		$this->name = $name;
	}

	/**
	 * Sets the value with all tests provided
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @throws Exception
	 * @return bool
	 * @access private
	 */
	public function setValue( O_Dao_ActiveRecord $obj, $fieldValue, $fieldExists )
	{
		if (!$fieldExists && $this->type) {
			$this->addFieldToTable();
		}

		$params = $this->fieldInfo->getParam( "file", 1 );

		if (!isset( $_FILES[ $this->name ] ) || !$_FILES[ $this->name ][ "size" ]) {
			if (isset( $params[ "clear" ] )) {
				$this->deleteThis( $obj );
			}
			return;
		}

		$file = $_FILES[ $this->name ];
		// Check file size
		if (isset( $params[ "max_size" ] ) && $file[ "size" ] > $params[ "max_size" ]) {
			throw new O_Ex_WrongArgument( "File is too large." );
		}

		// Check file extension
		preg_match( "#\\.([a-zA-Z]+)#i", $file[ "name" ], $p );

		$ext = isset( $p[ 1 ] ) ? strtolower( $p[ 1 ] ) : null;
		if (!$ext) {
			throw new O_Ex_WrongArgument( "No extension in uploaded file." );
		}

		// Ext is not allowed
		if (isset( $params[ "ext_allow" ] ) && strpos( $params[ "ext_allow" ], $ext ) ===
						 false) {
							throw new O_Ex_WrongArgument(
									"File extension is not in allowed list." );
		}
		// Ext denied
		if (isset( $params[ "ext_deny" ] ) && strpos( $params[ "ext_deny" ], $ext ) !== false) {
			throw new O_Ex_WrongArgument( "File extension is denied." );
		}

		// Delete old file
		$this->deleteThis( $obj );

		move_uploaded_file( $file[ "tmp_name" ], $this->getFilePath( $obj, $ext ) );

		if ($this->type)
			$obj[ $this->name ] = $this->getValueByExt( $obj, $ext );
	}

	private function getValueByExt( O_Dao_ActiveRecord $obj, $ext = null )
	{
		$params = $this->fieldInfo->getParam( "file", 1 );
		if (!isset( $params[ "value" ] )) {
			return $ext ? substr( $ext, 1 ) : "-";
		}
		return $this->objCallback( $obj, "value", $ext );
	}

	/**
	 * Returns path for image file
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param string $ext
	 * @return string
	 */
	private function getFilePath( O_Dao_ActiveRecord $obj, $ext = null )
	{
		return $this->objCallback( $obj, "filepath", $ext );
	}

	private function objCallback( O_Dao_ActiveRecord $obj, $type, $ext = null )
	{
		$params = $this->fieldInfo->getParam( "file", 1 );
		$callback = $params[ $type ];
		$param = null;
		if (strpos( $callback, " " ))
			list ($callback, $param) = explode( " ", $callback, 2 );
		return $obj->$callback( $ext );
	}

	/**
	 * Returns img src
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @return mixed
	 * @access private
	 */
	public function getValue( O_Dao_ActiveRecord $obj, $fieldValue, $fieldExists )
	{
		return $this->objCallback( $obj, "src" );
	}

	/**
	 * Sets the field info
	 *
	 * @see O_Dao_TableInfo::__construct()
	 * @access package
	 * @param O_Dao_FieldInfo $fieldInfo
	 */
	public function setFieldInfo( O_Dao_FieldInfo $fieldInfo )
	{
		$this->fieldInfo = $fieldInfo;
	}

	/**
	 * Adds field to CREATE query
	 *
	 * @param O_Db_Query $query
	 * @access private
	 */
	public function addFieldTypeToQuery( O_Db_Query $query )
	{
		if ($this->type)
			$query->field( $this->name, $this->type );
	}

	/**
	 * No special actions should be done on atomic field deletion
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @access private
	 */
	public function deleteThis( O_Dao_ActiveRecord $obj, $fieldValue = null )
	{
		$filepath = $this->getFilePath( $obj );
		if (is_file( $filepath ))
			unlink( $filepath );
	}
}

/**
 * Image storage with built-in resizer
 * @author alari
 *
 */
class O_Dao_Field_Image extends O_Dao_Field_Atomic {

	public function __construct( O_Dao_FieldInfo $fieldInfo, $type, $name )
	{
		$this->fieldInfo = $fieldInfo;
		$this->type = $type;
		$this->name = $name;
	}

	/**
	 * Sets the value with all tests provided
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @throws Exception
	 * @return bool
	 * @access private
	 */
	public function setValue( O_Dao_ActiveRecord $obj, $fieldValue, $fieldExists )
	{
		if (!$fieldExists && $this->type) {
			$this->addFieldToTable();
		}

		$params = $this->fieldInfo->getParam( "image", 1 );

		if ($fieldValue instanceof O_Image_Resizer) {
			$resizer = $fieldValue;
		} else {
			if (!isset( $_FILES[ $this->name ] ) || !$_FILES[ $this->name ][ "size" ]) {
				if (isset( $params[ "clear" ] )) {
					$this->deleteThis( $obj );
				}
				return;
			}

			$file = $_FILES[ $this->name ];
			$resizer = new O_Image_Resizer( $file[ "tmp_name" ] );
		}

		$this->deleteThis( $obj );

		$resizer->resize( isset( $params[ "width" ] ) ? $params[ "width" ] : 0,
				isset( $params[ "height" ] ) ? $params[ "height" ] : 0,
				$this->getFilePath( $obj, $resizer->getExtension() ) );

		if ($this->type)
			$obj[ $this->name ] = $this->getValueByExt( $obj, $resizer->getExtension() );

		if (isset( $params[ "cascade" ] )) {
			$fields = explode( ",", $params[ "cascade" ] );
			foreach ($fields as $field) {
				$field = trim( $field );
				$obj->$field = $resizer;
			}
		}
	}

	private function getValueByExt( O_Dao_ActiveRecord $obj, $ext = null )
	{
		$params = $this->fieldInfo->getParam( "image", 1 );
		if (!isset( $params[ "value" ] )) {
			return $ext ? substr( $ext, 1 ) : "-";
		}
		return $this->objCallback( $obj, "value", $ext );
	}

	/**
	 * Returns path for image file
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param string $ext
	 * @return string
	 */
	private function getFilePath( O_Dao_ActiveRecord $obj, $ext = null )
	{
		return $this->objCallback( $obj, "filepath", $ext );
	}

	private function objCallback( O_Dao_ActiveRecord $obj, $type, $ext = null )
	{
		$params = $this->fieldInfo->getParam( "image", 1 );
		$callback = $params[ $type ];
		$param = null;
		if (strpos( $callback, " " ))
			list ($callback, $param) = explode( " ", $callback, 2 );
		return $obj->$callback( $param, $ext );
	}

	/**
	 * Returns img src
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @return mixed
	 * @access private
	 */
	public function getValue( O_Dao_ActiveRecord $obj, $fieldValue, $fieldExists )
	{
		return $this->objCallback( $obj, "src" );
	}

	/**
	 * Sets the field info
	 *
	 * @see O_Dao_TableInfo::__construct()
	 * @access package
	 * @param O_Dao_FieldInfo $fieldInfo
	 */
	public function setFieldInfo( O_Dao_FieldInfo $fieldInfo )
	{
		$this->fieldInfo = $fieldInfo;
	}

	/**
	 * Adds field to CREATE query
	 *
	 * @param O_Db_Query $query
	 * @access private
	 */
	public function addFieldTypeToQuery( O_Db_Query $query )
	{
		if ($this->type)
			$query->field( $this->name, $this->type );
	}

	/**
	 * No special actions should be done on atomic field deletion
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @access private
	 */
	public function deleteThis( O_Dao_ActiveRecord $obj, $fieldValue = null )
	{
		$filepath = $this->getFilePath( $obj );
		if (is_file( $filepath ))
			unlink( $filepath );

		if ($this->type)
			$obj[ $this->name ] = $this->getValueByExt( $obj );

		$params = $this->fieldInfo->getParam( "image", 1 );
		if (isset( $params[ "cascade" ] )) {
			$fields = explode( ",", $params[ "cascade" ] );
			foreach ($fields as $field) {
				$field = trim( $field );
				O_Dao_TableInfo::get( $obj )->getFieldInfo( $field )->deleteThis( $obj );
			}
		}
	}
}

/**
 * Selector for several toone fields
 * @author alari
 *
 */
class O_Dao_Field_OneOf extends O_Dao_Field_Bases implements O_Dao_Field_iFace {
	/**
	 * Field info instance for the field
	 *
	 * @var O_Dao_FieldInfo
	 */
	private $fieldInfo;
	/**
	 * Array of fields to select from
	 *
	 * @var string
	 */
	private $otherFields = Array ();
	/**
	 * Lazy load flag
	 *
	 * @var bool
	 */
	private $isInitiated;

	public function __construct( O_Dao_FieldInfo $fieldInfo )
	{
		$this->fieldInfo = $fieldInfo;
	}

	/**
	 * Sets the value with all tests provided
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @return bool
	 * @access private
	 */
	public function setValue( O_Dao_ActiveRecord $obj, $fieldValue, $fieldExists )
	{
		$this->initiate();
		foreach ($this->otherFields as $class => $field) {
			if ($fieldValue instanceof $class) {
				$obj->$field = $fieldValue;
			} else {
				$obj->$field = null;
			}
		}
		return null;
	}

	/**
	 * Returns the field value, even if it's a relation or aliased query
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @return mixed
	 * @access private
	 */
	public function getValue( O_Dao_ActiveRecord $obj, $fieldValue, $fieldExists )
	{
		$this->initiate();
		foreach ($this->otherFields as $field)
			if ($obj[ $field ])
				return $obj->$field;
		return null;
	}

	/**
	 * Sets the class
	 *
	 * @see O_Dao_TableInfo::__construct()
	 * @access package
	 * @param string $class
	 */
	public function setFieldInfo( O_Dao_FieldInfo $fieldInfo )
	{
		$this->fieldInfo = $fieldInfo;
		$this->isInitiated = 0;
		$this->otherFields = Array ();
	}

	/**
	 * Returns fieldname where current value could be got from
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @return string
	 * @access private
	 */
	public function getExistentFieldName( O_Dao_ActiveRecord $obj )
	{
		$this->initiate();
		foreach ($this->otherFields as $field)
			if ($obj[ $field ])
				return $field;
		return null;
	}

	/**
	 * Lazy load pattern
	 *
	 */
	private function initiate()
	{
		if ($this->isInitiated)
			return;
		$oneOfFields = $this->fieldInfo->getParam( "one-of", 1 );
		foreach ($oneOfFields as $v) {
			$f = O_Dao_TableInfo::get( $this->fieldInfo->getClass() )->getFieldInfo( trim( $v ) );
			if (!$f || !$f->isRelationOne() || isset(
					$this->otherFields[ $f->getRelationTarget() ] )) {
				throw new O_Ex_Config( "Wrong fields enumeration for one-of aliasing." );
			}
			$this->otherFields[ $f->getRelationTarget() ] = trim( $v );
		}
		$this->isInitiated = 1;
	}

}

/**
 * relative field handler
 * @author alari
 *
 */
class O_Dao_Field_Relative extends O_Dao_Field_Bases implements O_Dao_Field_iFace, O_Dao_Field_iRelation {
	/**
	 * Field info instance for the field
	 *
	 * @var O_Dao_FieldInfo
	 */
	private $fieldInfo;
	/**
	 * Array of steps to get to relative object
	 *
	 * @var Array
	 */
	private $relative = Array ();
	/**
	 * Target field of relative object
	 *
	 * @var string
	 */
	private $field;
	/**
	 * Target classname
	 *
	 * @var string
	 */
	private $targetClass;

	public function __construct( O_Dao_FieldInfo $fieldInfo )
	{
		$this->setFieldInfo( $fieldInfo );
	}

	/**
	 * Sets the value with all tests provided
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @throws Exception
	 * @return bool
	 * @access private
	 */
	public function setValue( O_Dao_ActiveRecord $obj, $fieldValue, $fieldExists )
	{
		$relative = $obj;
		foreach ($this->relative as $f) {
			$relative = $relative->$f;
			if (!$relative instanceof O_Dao_ActiveRecord)
				return false;
		}
		return $relative->{$this->field} = $fieldValue;
	}

	/**
	 * Returns the field value
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @return mixed
	 * @access private
	 */
	public function getValue( O_Dao_ActiveRecord $obj, $fieldValue, $fieldExists )
	{
		$relative = $obj;
		foreach ($this->relative as $f) {
			$relative = $relative->$f;
			if (!$relative instanceof O_Dao_ActiveRecord)
				return null;
		}
		return $relative->{$this->field};
	}

	/**
	 * Sets the field info
	 *
	 * @see O_Dao_TableInfo::__construct()
	 * @access package
	 * @param string $class
	 */
	public function setFieldInfo( O_Dao_FieldInfo $fieldInfo )
	{
		$relative = $fieldInfo->getParam( "relative" );
		$this->fieldInfo = $fieldInfo;
		list ($relative, $this->field) = explode( "->", $relative, 2 );
		$this->relative = explode( ".", $relative );
		$this->targetClass = null;
	}

	/**
	 * FieldInfo of reverse field
	 *
	 * @return O_Dao_FieldInfo
	 * @access private
	 */
	public function getInverse()
	{
		return false;
	}

	/**
	 * Returns field info for real relative field
	 *
	 * @return O_Dao_FieldInfo
	 */
	public function getLastFieldInfo()
	{
		$tableInfo = O_Dao_TableInfo::get( $this->fieldInfo->getClass() );
		foreach ($this->relative as $field) {
			$fieldInfo = $tableInfo->getFieldInfo( $field );
			$tableInfo = O_Dao_TableInfo::get( $fieldInfo->getRelationTarget() );
		}
		return $tableInfo->getFieldInfo( $this->field );
	}

	/**
	 * Returns true if it's a *-to-many relation
	 *
	 * @return bool
	 */
	public function isRelationMany()
	{
		return $this->getLastFieldInfo()->isRelationMany();
	}

	/**
	 * Returns relation target classname
	 *
	 * @return string
	 */
	public function getTargetClass()
	{
		if (!$this->targetClass) {
			$this->targetClass = $this->getLastFieldInfo()->getRelationTarget();
		}
		return $this->targetClass;
	}

}

/**
 * Handles one-to-many and many-to-many relations
 * @author alari
 *
 */
class O_Dao_Field_ToMany extends O_Dao_Field_Bases implements O_Dao_Field_iFace, O_Dao_Field_iRelation {
	/**
	 * Field info instance for the field
	 *
	 * @var O_Dao_FieldInfo
	 */
	private $fieldInfo;
	/**
	 * Database's field name
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Owns it its targets or not
	 *
	 * @var bool
	 */
	private $owns = 0;
	/**
	 * Not parsed classname of relation target
	 *
	 * @var string
	 */
	private $targetBase;
	/**
	 * Relation target's classname
	 *
	 * @var string
	 */
	private $target;

	/**
	 * Inverse fieldname
	 *
	 * @var string
	 */
	private $inverse;
	/**
	 * Inverse fieldinfo
	 *
	 * @var O_Dao_FieldInfo
	 */
	private $inverseField;

	/**
	 * Cached relation objects, like O_Dao_Relation_Base
	 *
	 * @var O_Dao_Relation_BaseToMany[]
	 */
	private $relations = Array ();

	/**
	 * Current dao classname -- cached in property
	 *
	 * @var string
	 */
	private $class;
	/**
	 * Dao -order-by key value -- cached in property
	 *
	 * @var string
	 */
	private $orderBy;

	public function __construct( O_Dao_FieldInfo $fieldInfo, $name, $owns, $target )
	{
		$this->fieldInfo = $fieldInfo;
		$this->name = $name;
		$this->owns = $owns;
		$this->class = $fieldInfo->getClass();
		$this->orderBy = $fieldInfo->getParam( "order-by" );
		$this->targetBase = $target;
		$this->target = $this->getTargetByBase( $this->targetBase );
		$this->inverse = $fieldInfo->getParam( "inverse" );
		if (!$this->inverse)
			throw new O_Ex_Config( "Inverse field must be specified for whatever-to-many relations." );

	}

	/**
	 * Reloads field's cache for the object
	 *
	 * @param int $obj_id
	 */
	public function reload( $obj_id )
	{
		if (isset( $this->relations[ $obj_id ] ))
			unset( $this->relations[ $obj_id ] );
	}

	/**
	 * Returns relation target classname
	 *
	 * @return string
	 */
	public function getTargetClass()
	{
		return $this->target;
	}

	/**
	 * Sets the value with all tests provided
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @throws Exception
	 * @return bool
	 * @access private
	 */
	public function setValue( O_Dao_ActiveRecord $obj, $fieldValue, $fieldExists )
	{
		if ($fieldValue instanceof O_Dao_Query)
			$fieldValue = $fieldValue->getAll();
		if (!$fieldValue)
			$fieldValue = Array ();
		if (!is_array( $fieldValue ))
			throw new O_Ex_WrongArgument( "Cannot assign non-array/query to base-to-many relation." );
		$relation = $this->getRelation( $obj->id );
		foreach ($relation as $_el) {
			if (!array_key_exists( $_el->id, $fieldValue ))
				$relation->remove( $_el, $this->owns );
		}
		foreach ($fieldValue as $v) {
			$relation[] = $v;
		}
		return $relation;
	}

	/**
	 * Returns the field value, even if it's a relation or aliased query
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @return mixed
	 * @access private
	 */
	public function getValue( O_Dao_ActiveRecord $obj, $fieldValue, $fieldExists )
	{
		return $this->getRelation( $obj->id );
	}

	/**
	 * Sets the class
	 *
	 * @see O_Dao_TableInfo::__construct()
	 * @access package
	 * @param string $class
	 */
	public function setFieldInfo( O_Dao_FieldInfo $fieldInfo )
	{
		$this->fieldInfo = $fieldInfo;
		$this->class = $fieldInfo->getClass();
		$this->orderBy = $fieldInfo->getParam( "order-by" );
		if ($this->targetBase[ 0 ] == ":") {
			$const = $this->class . ":" . $this->targetBase;
			$this->target = defined( $const ) ? constant( $const ) : null;
			$this->inverseField = null;
			$this->relations = Array ();
		}
	}

	/**
	 * FieldInfo of reverse field
	 *
	 * @return O_Dao_FieldInfo
	 * @access private
	 */
	public function getInverse()
	{
		if (!$this->inverseField)
			$this->inverseField = O_Dao_TableInfo::get( $this->target )->getFieldInfo( $this->inverse );
		return $this->inverseField;
	}

	/**
	 * Create relation with other objects.
	 *
	 * @param int $obj_id
	 * @return O_Dao_Relation_BaseToMany
	 * @access private
	 */
	public function getRelation( $obj_id )
	{
		if (!isset( $this->relation[ $obj_id ] ) || !$this->relation[ $obj_id ] instanceof O_Dao_Relation_BaseToMany) {
			if ($this->getInverse()->isRelationMany()) {
				// Relation with anchors table (many-to-many or one-to-many without inverse)
				$this->relation[ $obj_id ] = new O_Dao_Relation_ManyToMany( $this->target, $this->inverse, $obj_id, $this->class, $this->name, $this->orderBy );
			} else {
				// Has many with inverse
				$this->relation[ $obj_id ] = new O_Dao_Relation_OneToMany( $this->target, $this->inverse, $obj_id, $this->class, $this->name, $this->orderBy );
			}
		}
		if(!$obj_id) {
			return clone $this->relation[ $obj_id ];
		}
		return clone $this->relation[ $obj_id ];
	}

	/**
	 * No special actions should be done on atomic field deletion
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @access private
	 */
	public function deleteThis( O_Dao_ActiveRecord $obj, $fieldValue = null )
	{
		$this->reload( $obj->id );
		$this->getRelation( $obj->id )->removeAll( $this->owns );
		$this->reload( $obj->id );
	}

}

/**
 * handles to-one relations
 * @author alari
 *
 */
class O_Dao_Field_ToOne extends O_Dao_Field_Bases implements O_Dao_Field_iFace, O_Dao_Field_iRelation {
	/**
	 * Field info instance for the field
	 *
	 * @var O_Dao_FieldInfo
	 */
	private $fieldInfo;
	/**
	 * Database's field name
	 *
	 * @var string
	 */
	private $name;

	/**
	 * On delete cascade or not
	 *
	 * @var int
	 */
	private $owns = 0;
	/**
	 * Non-parsed target classname
	 *
	 * @var string
	 */
	private $targetBase;
	/**
	 * Relation target classname
	 *
	 * @var string
	 */
	private $target;

	/**
	 * Was field already added to sql this time or not
	 *
	 * @var bool
	 */
	private $isAdded = 0;

	/**
	 * Inverse field name
	 *
	 * @var string
	 */
	private $inverse;
	/**
	 * Inverse field info
	 *
	 * @var O_Dao_FieldInfo
	 */
	private $inverseField;

	public function __construct( O_Dao_FieldInfo $fieldInfo, $name, $owns, $target )
	{
		$this->fieldInfo = $fieldInfo;
		$this->name = $name;
		$this->owns = $owns;
		$this->targetBase = $target;
		$this->target = $this->getTargetByBase( $this->targetBase );
		$this->inverse = $fieldInfo->getParam( "inverse" );
	}

	/**
	 * Returns relation target classname
	 *
	 * @return string
	 */
	public function getTargetClass()
	{
		return $this->target;
	}

	/**
	 * Sets the value with all tests provided
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @throws Exception
	 * @return bool
	 * @access private
	 */
	public function setValue( O_Dao_ActiveRecord $obj, $fieldValue, $fieldExists )
	{
		if (get_class( $fieldValue ) == $this->target || is_null( $fieldValue )) {
			if (!$fieldExists) {
				$this->addFieldToTable();
			}
			$oldValue = $obj->{$this->name};
			$obj[ $this->name ] = $fieldValue ? $fieldValue->id : 0;
			// One-to-one is symmetric
			if (!$this->getInverse()->isRelationMany()) {

				$inverseName = $this->inverse;
				if ($oldValue) {
					$oldValue->$inverseName = null;
					$oldValue->save();
				}
				if ($fieldValue && (!$fieldValue->$inverseName || $fieldValue->$inverseName->id != $obj->id)) {
					$fieldValue->$inverseName = $obj;
					$fieldValue->save();
				}
			}
			return $fieldValue;
		}
		throw new O_Ex_WrongArgument( "Wrong value for to-one relation." );
	}

	/**
	 * Returns the field value, even if it's a relation or aliased query
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @return mixed
	 * @access private
	 */
	public function getValue( O_Dao_ActiveRecord $obj, $fieldValue, $fieldExists )
	{
		// Base-to-one
		if (!$fieldExists) {
			$this->addFieldToTable();
		}
		if(!$fieldValue) {
			return null;
		}
		return O_Dao_ActiveRecord::getById( $fieldValue, $this->target );
	}

	/**
	 * Sets the class
	 *
	 * @see O_Dao_TableInfo::__construct()
	 * @access package
	 * @param string $class
	 */
	public function setFieldInfo( O_Dao_FieldInfo $fieldInfo )
	{
		$this->fieldInfo = $fieldInfo;
		if ($this->targetBase[ 0 ] == ":") {
			$const = $fieldInfo->getClass() . ":" . $this->targetBase;
			$this->target = defined( $const ) ? constant( $const ) : null;
			$this->inverseField = null;
		}
		$this->inverse = $fieldInfo->getParam( "inverse" );
	}

	/**
	 * FieldInfo of reverse field
	 *
	 * @return O_Dao_FieldInfo
	 * @access private
	 */
	public function getInverse()
	{
		if (!$this->inverse)
			$this->inverse = $this->fieldInfo->getParam( "inverse" );
		if (!$this->inverseField)
			$this->inverseField = O_Dao_TableInfo::get( $this->target )->getFieldInfo( $this->inverse );
		if (!$this->inverseField)
			throw new O_Ex_Critical( "Inverse field not found: $this->target -> $this->inverse" );
		return $this->inverseField;
	}

	/**
	 * No special actions should be done on atomic field deletion
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @access private
	 */
	public function deleteThis( O_Dao_ActiveRecord $obj, $fieldValue = null )
	{
		// Action needed if target is set and must be deleted, or if inverse field must be cleaned
		$relative = O_Dao_ActiveRecord::getById( $fieldValue, $this->target );
		if ($relative) {
			if ($this->owns) {
				$relative->delete();
			}
			// If has one, the database field exists
			if ($this->getInverse() && $this->getInverse()->isRelationOne()) {
				$relative[ $this->inverse ] = null;
				$relative->save();
			}
		}
	}

	/**
	 * Alters table to add unexistent field
	 *
	 * @return PDOStatement
	 */
	private function addFieldToTable()
	{
		if ($this->isAdded)
			return null;
		try {
			$q = new O_Dao_Query( $this->fieldInfo->getClass() );
			$this->addFieldTypeToQuery( $q );
			$r = $q->alter( "ADD" );
			O_Dao_ActiveRecord::saveAndReload( $this->fieldInfo->getClass() );
			O_Dao_Query::disablePreparing( $this->fieldInfo->getClass() );
			$this->isAdded = true;
		}
		catch (PDOException $e) {
			if (O_Registry::get( "app/mode" ) == "debug")
				throw $e;
			return null;
		}
		return $r;
	}

	/**
	 * Adds field to CREATE query
	 *
	 * @param O_Db_Query $query
	 * @access private
	 */
	public function addFieldTypeToQuery( O_Db_Query $query )
	{
		$query->field( $this->name, "int" )->index( $this->name );
	}

}