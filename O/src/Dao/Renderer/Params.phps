<?php

abstract class O_Dao_Renderer_Params {
	protected $fieldName;
	protected $class;
	protected $record;
	protected $layout;
	protected $params;
	protected $value;

	/**
	 * Basic constructor
	 *
	 * @param string $fieldName
	 * @param string $class
	 * @param string $params
	 * @param O_Dao_ActiveRecord $record
	 */
	public function __construct( $fieldName, $class, $params, O_Dao_ActiveRecord $record = null )
	{
		$this->fieldName = $fieldName;
		$this->class = $class;
		$this->record = $record;
		$this->params = $params;
	}

	/**
	 * Sets layout object
	 *
	 * @param O_Html_Layout $layout
	 */
	public function setLayout( O_Html_Layout $layout )
	{
		$this->layout = $layout;
	}

	/**
	 * Sets field value
	 *
	 * @param string $value
	 */
	public function setValue( $value )
	{
		$this->value = $value;
	}

	/**
	 * Returns current field value to handle
	 *
	 * @return unknown
	 */
	public function value()
	{
		return $this->value;
	}

	/**
	 * Returns callback subparams
	 *
	 * @return string
	 */
	public function params()
	{
		return $this->params;
	}

	/**
	 * Returns layout object
	 *
	 * @return O_Html_Layout
	 */
	public function layout()
	{
		return $this->layout;
	}

	/**
	 * Returns active record we're processing
	 *
	 * @return O_Dao_ActiveRecord
	 */
	public function record()
	{
		return $this->record;
	}

	/**
	 * Returns current fieldname
	 *
	 * @return string
	 */
	public function fieldName()
	{
		return $this->fieldName;
	}

	/**
	 * Returns current classname
	 *
	 * @return string
	 */
	public function className()
	{
		return $this->class;
	}
}