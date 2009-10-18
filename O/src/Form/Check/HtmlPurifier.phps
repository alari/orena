<?php

class O_Form_Check_HtmlPurifier extends O_Form_FieldChecker {

	public function check( $createMode = false )
	{
		// TODO: add purifier configuration
		$purifier = new HTMLPurifier( );
		$this->producer->setValue( $purifier->purify( $this->producer->getValue() ) );
	}

}