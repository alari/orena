<?php

require_once 'PHPUnit/Framework/TestSuite.php';

require_once 'Test/Cases/DaoObject.php';

require_once 'Test/Cases/DbManager.php';

/**
 * Static test suite.
 */
class Test_Suite extends PHPUnit_Framework_TestSuite {

	/**
	 * Constructs the test suite handler.
	 */
	public function __construct()
	{
		Registry::add("app/dao/Test_Models_Core/plugins", "Test_Models_CorePlugin");

		$this->setName( 'Test_Suite' );

		$this->addTestSuite( 'Test_Cases_DaoObject' );

		$this->addTestSuite( 'Test_Cases_DbManager' );

		$this->addTestSuite( 'Test_Cases_DaoTableInfo' );

		$this->addTestSuite( 'Test_Cases_DaoSignals' );

		$this->addTestSuite( 'Test_Cases_Registry' );

	}

	/**
	 * Creates the suite.
	 */
	public static function suite()
	{
		return new self( );
	}
}

