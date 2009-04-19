<?php
class O_Dao_Field_Relative implements O_Dao_Field_iFace {
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
		return $obj->{$this->field} = $fieldValue;
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
		return $obj->{$this->field};
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
	}

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

}