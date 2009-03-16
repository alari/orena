<?php

/**
 * Static test suite.
 */
class Test_Suite extends PHPUnit_Framework_TestSuite {

	/**
	 * Constructs the test suite handler.
	 */
	public function __construct()
	{
		$this->setName( 'Test_Suite' );
		
		$this->addTestSuite( 'Test_Cases_DaoObject' );
		
		$this->addTestSuite( 'Test_Cases_DbManager' );
		
		$this->addTestSuite( 'Test_Cases_DaoTableInfo' );
		
		$this->addTestSuite( 'Test_Cases_DaoSignals' );
		
		$this->addTestSuite( 'Test_Cases_Registry' );
		
		$this->addTestSuite( 'Test_Cases_NestedSet' );
	
	}

	/**
	 * Creates the suite.
	 */
	public static function suite()
	{
		return new self( );
	}
}

