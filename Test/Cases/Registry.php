<?php
require_once 'PHPUnit/Framework/TestCase.php';

/**
 * O_Registry test case.
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
	 * Tests O_Registry::add()
	 */
	public function testAdd()
	{
		O_Registry::add( "test/add/var", "var1" );
		$this->assertEquals( "var1", O_Registry::get( "test/add/var/0" ), "First array key" );

		O_Registry::add( "test/add/var", "var2" );
		$this->assertEquals( "var2", O_Registry::get( "test/add/var/1" ), "Second array key" );

		$this->assertEquals( array ("var1", "var2"), O_Registry::get( "test/add/var" ), "Array equal" );
	}

	/**
	 * Tests O_Registry::get()
	 */
	public function testGetSet()
	{
		O_Registry::set( "test/get-set/b/c", "d" );

		$this->assertArrayHasKey( "b", O_Registry::get( "test/get-set" ), "Base level" );
		$this->assertArrayHasKey( "c", O_Registry::get( "test/get-set/b" ), "Middle level" );
		$this->assertEquals( "d", O_Registry::get( "test/get-set/b/c" ), "Bottom-level" );

		O_Registry::set( "test/get-set/b/q", "e" );

		$this->assertArrayHasKey( "b", O_Registry::get( "test/get-set" ), "Base level (2)" );
		$this->assertArrayHasKey( "c", O_Registry::get( "test/get-set/b" ), "Middle level (2)" );
		$this->assertEquals( "e", O_Registry::get( "test/get-set/b/q" ), "Bottom-level (2)" );

	}

	/**
	 * Tests O_Registry::setInheritance()
	 */
	public function testSetInheritance()
	{
		O_Registry::setInheritance( "test/base/params", "test/extended" );
		O_Registry::setInheritance( "test/base", "test/extended/params" );

		O_Registry::set( "test/base/params/a", "b" );

		$this->assertEquals( "b", O_Registry::get( "test/extended/a" ), "Short to long" );
		$this->assertEquals( array ("params" => array ("a" => "b")), O_Registry::get( "test/extended/params" ),
				"Long to short, as array" );
	}

}

