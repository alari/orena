<?php
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
		if(isset($params["max_size"]) && $file["size"] > $params["max_size"]) {
			return false;
		}

		// Check file extension
		preg_match("#\\.([a-zA-Z]+)#i", $file["name"], $p);

		$ext = isset($p[1]) ? strtolower($p[1]) : null;
		if(!$ext) {
			return false;
		}

		// Ext is not allowed
		if(isset($params["ext_allow"]) && strpos($params["ext_allow"], $ext) === false) {
			return false;
		}
		// Ext denied
		if(isset($params["ext_deny"]) && strpos($params["ext_deny"], $ext) !== false) {
			return false;
		}

		// Delete old file
		$this->deleteThis($obj);

		move_uploaded_file($file["tmp_name"], $this->getFilePath( $obj, $ext ));

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