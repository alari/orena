<?php
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
		if ($this->targetBase[ 0 ] == "{" && $this->targetBase[ strlen( $this->targetBase ) - 1 ] == "}") {
			$this->target = O_Registry::get( "app/" . substr( $this->targetBase, 1, -1 ) );
		} else {
			$this->target = $this->targetBase;
		}
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