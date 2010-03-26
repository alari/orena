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

		$this->addTestSuite( 'Test_Cases_Acl' );

		$this->addTestSuite( 'Test_Cases_AclResourse' );

		$this->addTestSuite( 'Test_Cases_DecoratorsTest' );

	}

	/**
	 * Creates the suite.
	 */
	public static function suite()
	{
		return new self( );
	}
}

