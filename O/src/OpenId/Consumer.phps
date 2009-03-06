<?php
class O_OpenId_Consumer extends Zend_OpenId_Consumer {

	public function __construct( $dumbMode = false )
	{
		parent::__construct( O_OpenId_Consumer_Storage::getInstance(), $dumbMode );
	}
}