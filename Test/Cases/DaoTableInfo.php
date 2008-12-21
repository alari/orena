<?php

require_once 'Dao/TableInfo.phps';

require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Dao_TableInfo test case.
 */
class Test_Cases_DaoTableInfo extends PHPUnit_Framework_TestCase {

	/**
	 * Object to work with
	 *
	 * @var Dao_TableInfo
	 */
	private $Dao_TableInfo;

	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();

		Db_Manager::getConnection()->query( "DROP TABLE IF EXISTS create_table" );

		$this->Dao_TableInfo = Dao_TableInfo::get( "Test_Models_Tbl" );

	}

	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{

		$this->Dao_TableInfo = null;

		parent::tearDown();
	}

	/**
	 * Tests Dao_TableInfo::get()
	 */
	public function testGet()
	{
		$this->assertEquals( "Dao_TableInfo", get_class( $this->Dao_TableInfo ) );

	}

	public function testCreateTable()
	{
		$this->assertFalse( $this->Dao_TableInfo->tableExists(), "Table should not exist" );

		$this->assertEquals( "PDOStatement", get_class( $this->Dao_TableInfo->createTable() ), "Creating table" );

		$this->assertTrue( $this->Dao_TableInfo->tableExists(), "Table should exist now" );
	}

	public function testTableName()
	{
		$this->assertEquals( "create_table", $this->Dao_TableInfo->getTableName() );
	}

	/**
	 * Tests Dao_TableInfo->getFieldInfo()
	 */
	public function testGetFieldInfo()
	{
		$this->assertEquals( "Dao_FieldInfo", get_class( $this->Dao_TableInfo->getFieldInfo( "textfield" ) ) );
	}

	/**
	 * Tests Dao_TableInfo->getFields()
	 */
	public function testGetFields()
	{
		$this->assertEquals( 2, count( $this->Dao_TableInfo->getFields(/* parameters */) ) );

	}

	/**
	 * Tests Dao_TableInfo::getPrefix()
	 */
	public function testGetPrefix()
	{

		$this->assertEquals( "", Dao_TableInfo::getPrefix(), "Prefix is clear" );

		Dao_TableInfo::setPrefix( "test" );

		$this->assertEquals( "test_", Dao_TableInfo::getPrefix(), "Setting test prefix" );

		Dao_TableInfo::setPrefix( "" );

	}

	public function testGetParam() {
		$fieldInfo = $this->Dao_TableInfo->getFieldInfo("intfield");

		$this->assertEquals(1, $fieldInfo->getParam("test"));
		$this->assertNull($fieldInfo->getParam("asdasas"));
	}

}

