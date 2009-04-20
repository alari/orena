<?php
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
	private function getLastFieldInfo()
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