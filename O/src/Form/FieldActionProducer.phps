<?php

abstract class O_Form_FieldActionProducer {
	/**
	 * Rendered fieldname
	 *
	 * @var string
	 */
	protected $name;
	/**
	 * Callable resourse to generate a row
	 *
	 * @var callback
	 */
	protected $callback;
	/**
	 * String params given in dao' table info after callback
	 *
	 * @var string
	 */
	protected $params = "";
	/**
	 * Current field value
	 *
	 * @var mixed
	 */
	protected $value;
	/**
	 * FieldInfo object of current field
	 *
	 * @var O_Dao_FieldInfo
	 */
	protected $fieldInfo;
	/**
	 * Active record we're editing
	 *
	 * @var O_Dao_ActiveRecord
	 */
	protected $record;
	/**
	 * Was callback already prepared from params and field info or not
	 *
	 * @var bool
	 */
	protected $callbackPrepared = null;
	/**
	 * Relation query to choose valid target of relative field
	 *
	 * @var O_Dao_Query
	 */
	protected $relationQuery;

	/**
	 * Returns currently handled field info
	 *
	 * @return O_Dao_FieldInfo
	 */
	public function getFieldInfo()
	{
		return $this->fieldInfo;
	}

	/**
	 * Returns a query to select relative objects from
	 *
	 * @return O_Dao_Query
	 */
	public function getRelationQuery()
	{
		return $this->relationQuery;
	}

	/**
	 * Returns current field name
	 *
	 * @return string
	 */
	public function getFieldName()
	{
		return $this->name;
	}

	/**
	 * Returns current field value
	 *
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * Returns ActiveRecord we're handling
	 *
	 * @return O_Dao_ActiveRecord
	 */
	public function getRecord()
	{
		return $this->record;
	}

	/**
	 * Returns additional params for row generator
	 *
	 * @return string
	 */
	public function getParams()
	{
		return $this->params;
	}

}