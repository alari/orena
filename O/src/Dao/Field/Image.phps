<?php
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

		$this->deleteThis($obj);

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