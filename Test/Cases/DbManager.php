<?php

require_once 'Db/Manager.phps';

require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Db_Manager test case.
 */
class Test_Cases_DbManager extends PHPUnit_Framework_TestCase {
	
	/**
	 * @var Db_Manager
	 */
	private $Db_Manager;

	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
	
	}

	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		parent::tearDown();
	}

	/**
	 * Tests Db_Manager::getConnection()
	 */
	public function testGetConnection()
	{
		$this->assertEquals( "PDO", get_class( Db_Manager::getConnection() ) );
		$this->assertEquals( "PDOStatement", 
				get_class( Db_Manager::getConnection()->query( "SELECT UNIX_TIMESTAMP()" ) ) );
	}
}

