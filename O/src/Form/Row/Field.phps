<?php
abstract class O_Form_Row_Field extends O_Form_Row {
	/**
	 * Current field value
	 *
	 * @var mixed
	 */
	protected $value;
	/**
	 * Field name
	 *
	 * @var string
	 */
	protected $name;
	/**
	 * Additional config params
	 *
	 * @var string
	 */
	protected $params;
	/**
	 * Layout setup
	 *
	 * @var bool
	 * @see O_Form_Row
	 */
	protected $isVertical = false;

	/**
	 * Creates new field row
	 *
	 * @param string $name
	 * @param string $title
	 * @param string $params
	 * @param mixed $value
	 * @param string $error
	 */
	public function __construct( $name, $title = "", $params = "", $value = "", $error = "" )
	{
		if (!$name) {
			throw new O_Ex_WrongArgument( "Form field must have a name" );
		}
		$this->name = $name;
		$this->params = $params;
		$this->title = $title;
		$this->value = $value;
		$this->error = $error;
	}

	/**
	 * Returns field name
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Sets field additional params
	 *
	 * @param string $params
	 */
	public function setParams( $params )
	{
		$this->params = $params;
	}

	/**
	 * Sets current field value
	 *
	 * @param mixed $value
	 */
	public function setValue( $value )
	{
		$this->value = $value;
	}

	/**
	 * Configure field with autoproducer object
	 *
	 * @param O_Form_Row_AutoProducer $producer
	 */
	public function autoProduce( O_Form_Row_AutoProducer $producer )
	{
		$this->title = $producer->getTitle();
		$this->params = $producer->getParams();
		$this->value = $producer->getValue();
	}

}