<?php

require_once 'Test/Models/Core.php';

require_once 'PHPUnit/Framework/TestCase.php';

/**
 * test_CoreModel test case.
 */
class Test_Cases_DaoObject extends PHPUnit_Framework_TestCase {
	
	/**
	 * @var test_CoreModel
	 */
	private $test_CoreModel;

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
		$this->test_CoreModel = null;
		
		parent::tearDown();
	}

	public function testCreateObject()
	{
		$obj = new Test_Models_Core( );
		$this->assertEquals( "Test_Models_Core", get_class( $obj ), "Object is not created!" );
		$this->assertGreaterThanOrEqual( 1, $obj->id, "Object has no ID!" );
	}

	public function testAtomicField()
	{
		$obj = new Test_Models_Core( );
		$obj->textfield = "text value";
		$obj->save();
		$this->assertEquals( "text value", $obj->textfield );
	}

	public function testRelationOneToMany()
	{
		$obj = new Test_Models_Core( );
		
		$this->assertEquals( "Dao_Relation_OneToMany", get_class( $obj->subs ) );
		
		$obj->subs[] = new Test_Models_Sub( );
		$obj->subs[] = new Test_Models_Sub( );
		$this->assertEquals( "Test_Models_Sub", get_class( $obj->subs->current() ) );
		$this->assertEquals( 2, count( $obj->subs->getAll() ) );
		
		$obj->subs->current()->reload();
		$this->assertEquals( "Test_Models_Core", get_class( $obj->subs->current()->core ), "Inverse class" );
		$this->assertEquals( $obj->id, $obj->subs->current()->core->id, "Inverse id" );
		
		$obj->subs->remove( $obj->subs->current(), true );
		$this->assertEquals( 1, count( $obj->subs ) );
		
		$obj->subs->remove( $obj->subs->current(), true );
		$this->assertEquals( 0, count( $obj->subs ) );
	}

	public function testRelationManyToMany()
	{
		$obj = new Test_Models_Core( );
		
		$this->assertEquals( "Dao_Relation_ManyToMany", get_class( $obj->manysubs ) );
		
		$obj->manysubs[] = new Test_Models_Sub( );
		$obj->manysubs[] = new Test_Models_Sub( );
		
		$this->assertEquals( "Test_Models_Sub", get_class( $obj->manysubs->current() ) );
		
		$this->assertEquals( 2, count( $obj->manysubs ) );
		
		$obj->manysubs->remove( $obj->manysubs->current() );
		
		$this->assertEquals( 1, count( $obj->manysubs->getAll() ), "Remove one" );
		
		$this->assertEquals( $obj->id, $obj->manysubs->current()->cores->current()->id, "Inverse link (id)" );
		$this->assertEquals( get_class( $obj ), get_class( $obj->manysubs->current()->cores->current() ), 
				"Inverse link (class)" );
		
		$obj->manysubs->remove( $obj->manysubs->current(), true );
		$this->assertEquals( 0, count( $obj->manysubs ), "remove and delete one" );
		
		$this->assertFalse( $obj->manysubs->current(), "no more objects" );
	}

	public function testRelationOneToOne()
	{
		$core = new Test_Models_Core( );
		$sub = new Test_Models_Sub( );
		
		$this->assertNull( $core->one_sub, "Relation is not defined." );
		
		$core->one_sub = $sub;
		$this->assertEquals( get_class( $sub ), get_class( $core->one_sub ), "Direct field is set (test class)." );
		$this->assertEquals( $sub->id, $core->one_sub->id, "Direct field is set (test id)." );
		
		$this->assertEquals( get_class( $core ), get_class( $sub->one_core ), "Inverse field is set (test class)." );
		$this->assertEquals( $core->id, $sub->one_core->id, "Inverse field is set (test id)." );
		
		$another_core = new Test_Models_Core( );
		
		$core->core_direct = $another_core;
		
		$this->assertEquals( $another_core->id, $core->core_direct->id, "Relation with the same class, direct" );
		$this->assertEquals( $another_core->core_inverse->id, $core->id, "Relation with the same class, inverse" );
	}

	public function testMappedQuery()
	{
		$obj = new Test_Models_Core( );
		$sub = new Test_Models_Sub( );
		
		$obj->subs[] = $sub;
		$obj->manysubs[] = $sub;
		
		$this->assertEquals( $obj->id, $sub->core->id, "Inverse object" );
		
		$this->assertEquals( "Dao_Query", get_class( $obj->{"subs.core"} ), "Query create" );
		
		$this->assertEquals( get_class( $obj ), get_class( $obj->{"subs.core"}->getOne() ), 
				"Getting one object -- core from core" );
		
		$this->assertEquals( get_class( $sub ), get_class( $sub->{"core.manysubs"}->getOne() ), 
				"Getting one object -- sub from sub" );
		
		$obj->one_sub = $sub;
		
		$this->assertEquals( get_class( $sub ), get_class( $obj->{"manysubs.one_core.one_sub"}->current() ), 
				"Getting one object -- one sub from sub" );
	}

	public function testAlias()
	{
		$obj = new Test_Models_Core( );
		$sub = new Test_Models_Sub( );
		
		$obj->subs[] = $sub;
		$obj->manysubs[] = $sub;
		
		$this->assertEquals( "Dao_Query", get_class( $obj->myalias ) );
		$this->assertEquals( get_class( $obj ), get_class( $obj->myalias->getOne() ) );
		$this->assertEquals( $obj->id, $obj->myalias->getOne()->id );
	}

}

