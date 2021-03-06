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