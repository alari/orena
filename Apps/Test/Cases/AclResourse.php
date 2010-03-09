<?php

/**
 * Test_Models_Acl test case.
 */
class Test_Cases_AclResourse extends PHPUnit_Framework_TestCase {

	/**
	 * @var Test_Models_Acl
	 */
	private $resourse;

	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();

		$this->resourse = new Test_Models_Acl(/* parameters */);

	}

	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{

		$this->Test_Models_Acl = null;

		parent::tearDown();
	}

	public function testVisitor()
	{
		$visitorRole = O_Acl_Role::getByName( "test visitor" );
		$visitorRole->allow( "res test 1" );
		$this->assertTrue( O_Acl_Visitor::getInstance()->can( "res test 1", $this->resourse ) );
		$this->assertNull( O_Acl_Visitor::getInstance()->can( "res test 2", $this->resourse ) );
	}

	public function testUserIn()
	{
		$user = new Test_Models_User( );
		$role1 = O_Acl_Role::getByName( "test owner" );
		$role2 = O_Acl_Role::getByName( "test owners" );
		$role1->allow( "test own" );
		$role1->clear( "test own many" );
		$role2->allow( "test own many" );
		$role2->clear( "test one" );
		$role3 = O_Acl_Role::getByName( "test prop" );
		$role3->allow( "test propp" );

		$this->assertNull( $user->can( "test own", $this->resourse ) );
		$this->resourse->owner = $user;
		$this->assertTrue( $user->can( "test own", $this->resourse, true ) );

		$this->assertNull( $user->can( "test own many", $this->resourse ) );
		$this->resourse->owners[] = $user;
		$this->assertTrue( $user->can( "test own many", $this->resourse, true ) );

		$this->assertNull( $user->can( "test propp", $this->resourse ) );
		$this->resourse->prop = "abb";
		$this->resourse->save();
		$this->assertTrue( $user->can( "test propp", $this->resourse, true ) );
	}

}

