<?php
class O_Dao_Field_Atomic implements O_Dao_Field_iFace {
	/**
	 * Field info instance for the field
	 *
	 * @var O_Dao_FieldInfo
	 */
	private $fieldInfo;
	/**
	 * Database's fieldtype
	 *
	 * @var string
	 */
	private $type;
	/**
	 * Database's field name
	 *
	 * @var string
	 */
	private $name;
	
	/**
	 * Was this field added to sql-table this time or not
	 *
	 * @var bool
	 */
	private $isAdded = 0;

	public function __construct( O_Dao_FieldInfo $fieldInfo, $type, $name )
	{
		$this->fieldInfo = $fieldInfo;
		$this->type = $type;
		$this->name = $name;
		
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
		$query->field( $this->name, $this->type );
	}

}