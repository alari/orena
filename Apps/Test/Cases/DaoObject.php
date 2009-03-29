<?php

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
		$this->assertEquals( "text value", $obj->textfield, "simple atomic field" );
		$obj->setScalarField( "intfield", "UNIX_TIMESTAMP()" );
		$this->assertGreaterThanOrEqual( time(), $obj->intfield );
	}

	public function testRelationOneToMany()
	{
		$obj = new Test_Models_Core( );

		$this->assertEquals( "O_Dao_Relation_OneToMany", get_class( $obj->subs ) );

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

		$a = new Test_Models_Core( );
		$b = new Test_Models_Core( );
		$x = new Test_Models_Sub( );
		$a->subs[] = $x;
		$x->core = $b;
		$this->assertEquals( 1, count( $a->subs ), "Test with sub's inverse setting" );

	}

	public function testRelationManyToMany()
	{
		$obj = new Test_Models_Core( );

		$this->assertEquals( "O_Dao_Relation_ManyToMany", get_class( $obj->manysubs ) );

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

		$this->assertEquals( "O_Dao_Query", get_class( $obj->{"subs.core"} ), "Query create" );

		$this->assertEquals( get_class( $obj ), get_class( $obj->{"subs.core"}->getOne() ),
				"Getting one object -- core from core" );

		$this->assertEquals( get_class( $sub ), get_class( $sub->{"core.manysubs"}->getOne() ),
				"Getting one object -- sub from sub" );

		$obj->one_sub = $sub;
		$sub->save();

		$this->assertEquals( get_class( $sub ), get_class( $obj->{"manysubs.one_core.one_sub"}->current() ),
				"Getting one object -- one sub from obj with 1-1" );

		$this->assertEquals( get_class( $obj ), get_class( $obj->{"manysubs.one_core.one_sub.core"}->current() ),
				"Getting one object -- one obj from obj with 1-1 (4 steps)" );

		$this->assertEquals( get_class( $obj ), get_class( $obj->{"one_sub.core"}->current() ),
				"Getting one object -- one obj from obj with 1-1 (2 steps)" );
	}

	public function testAlias()
	{
		$obj = new Test_Models_Core( );
		$sub = new Test_Models_Sub( );

		$obj->subs[] = $sub;
		$obj->manysubs[] = $sub;

		$this->assertEquals( "O_Dao_Query", get_class( $obj->myalias ) );
		$this->assertEquals( get_class( $obj ), get_class( $obj->myalias->getOne() ) );
		$this->assertEquals( $obj->id, $obj->myalias->getOne()->id );
	}

	public function testPlugin()
	{
		$obj = new Test_Models_Core( );
		$obj->plugin_field = "test plugin field";
		$obj->save();

		$v = $obj->plugin_field;
		$q = new O_Dao_Query( get_class( $obj ) );
		$q->field( "plugin_field" )->alter( "DROP" );

		$this->assertEquals( "test plugin field", $v );

		$this->assertEquals( "", $obj->notInjection(), "protected injection" );
		$this->assertEquals( "", $obj->notInjection2(), "injection without arguments" );
		$this->assertEquals( $obj->id, $obj->injection(), "valid injection" );
	}

}

