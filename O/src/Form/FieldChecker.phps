<?php

abstract class O_Form_FieldChecker {
	/**
	 * Producer instance to access values
	 *
	 * @var O_Form_Check_AutoProducer
	 */
	protected $producer;

	public function __construct( O_Form_Check_AutoProducer $producer )
	{
		$this->producer = $producer;
	}

	abstract public function check( $createMode = false );
}