<?php

abstract class O_Form_FieldChecker {
	/**
	 * Producer instance to access values
	 *
	 * @var O_Form_Check_AutoProducer
	 */
	protected $producer;

	/**
	 * Creates field checker instance
	 *
	 * @param O_Form_Check_AutoProducer $producer
	 */
	public function __construct( O_Form_Check_AutoProducer $producer )
	{
		$this->producer = $producer;
	}

	/**
	 * Produces field check
	 *
	 * @param bool $createMode
	 * @throws O_Form_Check_Error
	 */
	abstract public function check( $createMode = false );
}