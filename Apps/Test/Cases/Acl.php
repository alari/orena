<?php

/**
 * O_Acl_Role test case.
 */
class Test_Cases_Acl extends PHPUnit_Framework_TestCase {

	/**
	 * Enter description here...
	 *
	 * @var O_Acl_Role
	 */
	private static $role;

	public function __construct()
	{
		try {
			O_Acl_Role::getQuery()->delete();
		} catch(PDOException $e) {
			echo $e;
		}
	}

	public function testGetInh()
	{
		self::$role = O_Acl_Role::getByName( "test role" );
		self::$role->parent = O_Acl_Role::getByName( "test parent" );
		self::$role->save();
		$this->assertEquals( O_Acl_Role::getByName( "test parent" ), self::$role->parent );
	}

	/**
	 * Tests O_Acl_Role->allow()
	 */
	public function testAllow()
	{
		self::$role->allow( "test allow" );
		print_r(self::$role->actions->getAll());
		$this->assertEquals( O_Acl_Action::TYPE_ALLOW, self::$role->getActionStatus("test allow") );
	}

	/**
	 * Tests O_Acl_Role->deny()
	 */
	public function testDeny()
	{
		self::$role->deny( "test deny" );
		$this->assertEquals( O_Acl_Action::TYPE_DENY, self::$role->getActionStatus("test deny") );
	}

	/**
	 * Tests O_Acl_Role->can()
	 * @depends testAllow
	 * @depends testDeny
	 * @depends testGetInh
	 */
	public function testCan()
	{
		self::$role->parent->allow( "test inherit" );
		$this->assertTrue( self::$role->can( "test allow" ) );
		$this->assertFalse( self::$role->can( "test deny" ) );
		$this->assertTrue( self::$role->can( "test inherit" ) );
		$this->assertNull( self::$role->can( "test unknown" ) );
	}

	/**
	 * Tests O_Acl_Role->clear()
	 * @depends testCan
	 */
	public function testClear()
	{
		self::$role->clear( "test allow" );
		$this->assertNull( self::$role->can( "test allow" ) );
	}

	/**
	 * Tests O_Acl_Role->setAsVisitorRole()
	 */
	public function testSetAsVisitorRole()
	{
		self::$role->setAsVisitorRole();
	}

	/**
	 * Tests O_Acl_Role::getVisitorRole()
	 * @depends testSetAsVisitorRole
	 */
	public function testGetVisitorRole()
	{
		$this->assertEquals( self::$role->name, O_Acl_Role::getVisitorRole()->name );
	}

}

