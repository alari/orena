<?php
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Registry test case.
 */
class Test_Cases_Registry extends PHPUnit_Framework_TestCase {

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
	 * Constructs the test case.
	 */
	public function __construct()
	{
		// TODO Auto-generated constructor
	}

	/**
	 * Tests Registry::add()
	 */
	public function testAdd()
	{
		Registry::add( "test/add/var", "var1" );
		$this->assertEquals( "var1", Registry::get( "test/add/var/0" ), "First array key" );

		Registry::add( "test/add/var", "var2" );
		$this->assertEquals( "var2", Registry::get( "test/add/var/1" ), "Second array key" );

		$this->assertEquals( array ("var1", "var2"), Registry::get( "test/add/var" ), "Array equal" );
	}

	/**
	 * Tests Registry::get()
	 */
	public function testGetSet()
	{
		Registry::set( "test/get-set/b/c", "d" );

		$this->assertArrayHasKey( "b", Registry::get( "test/get-set" ), "Base level" );
		$this->assertArrayHasKey( "c", Registry::get( "test/get-set/b" ), "Middle level" );
		$this->assertEquals( "d", Registry::get( "test/get-set/b/c" ), "Bottom-level" );

		Registry::set( "test/get-set/b/q", "e" );

		$this->assertArrayHasKey( "b", Registry::get( "test/get-set" ), "Base level (2)" );
		$this->assertArrayHasKey( "c", Registry::get( "test/get-set/b" ), "Middle level (2)" );
		$this->assertEquals( "e", Registry::get( "test/get-set/b/q" ), "Bottom-level (2)" );

	}

	/**
	 * Tests Registry::setInheritance()
	 */
	public function testSetInheritance()
	{
		Registry::setInheritance( "test/base/params", "test/extended" );
		Registry::setInheritance( "test/base", "test/extended/params" );

		Registry::set( "test/base/params/a", "b" );

		$this->assertEquals( "b", Registry::get( "test/extended/a" ), "Short to long" );
		$this->assertEquals( array ("params" => array ("a" => "b")), Registry::get( "test/extended/params" ),
				"Long to short, as array" );
	}

}

