<?php
abstract class O_Form_Row_Field extends O_Form_Row {
	protected $value;
	protected $name;
	protected $params;
	protected $isVertical = false;

	public function __construct( $name, $params = "" )
	{
		if (!$name) {
			throw new O_Ex_WrongArgument( "Form field must have a name" );
		}
		$this->name = $name;
		$this->params = $params;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setParams( $params )
	{
		$this->params = $params;
	}

	public function setValue( $value )
	{
		$this->value = $value;
	}

	public function setTitle( $title )
	{
		$this->title = $title;
	}

	public function setError( $error )
	{
		$this->error = $error;
	}

	public function setRemark( $remark )
	{
		$this->remark = $remark;
	}
}