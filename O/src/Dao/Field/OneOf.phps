<?php
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

	public function __construct( O_Dao_FieldInfo $fieldInfo, $oneOfFields )
	{
		$this->fieldInfo = $fieldInfo;
		$oneOfFields = explode( ",", $oneOfFields );
		foreach ($oneOfFields as $v) {
			$f = O_Dao_TableInfo::get( $fieldInfo->getClass() )->getFieldInfo( trim( $v ) );
			if (!$f || !$f->isRelationOne() || isset( $this->otherFields[ $f->getRelationTarget() ] )) {
				throw new O_Ex_Config( "Wrong fields enumeration for one-of aliasing." );
			}
			$this->otherFields[ $f->getRelationTarget() ] = trim( $v );
		}
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
		foreach ($this->otherFields as $class => $field) {
			if ($fieldValue instanceof $class)
				$obj->$field = $fieldValue;
			else
				$obj->$field = null;
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
		foreach ($this->otherFields as $field)
			if ($obj[ $field ])
				return $field;
		return null;
	}

}