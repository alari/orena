<?php

require_once 'PHPUnit/Framework/TestCase.php';

/**
 * O_Dao_TableInfo test case.
 */
class Test_Cases_DaoTableInfo extends PHPUnit_Framework_TestCase {
	
	/**
	 * Object to work with
	 *
	 * @var O_Dao_TableInfo
	 */
	private $O_Dao_TableInfo;

	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		
		O_Db_Manager::getConnection()->query( "DROP TABLE IF EXISTS create_table" );
		
		$this->O_Dao_TableInfo = O_Dao_TableInfo::get( "Test_Models_Tbl" );
	
	}

	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		
		$this->O_Dao_TableInfo = null;
		
		parent::tearDown();
	}

	/**
	 * Tests O_Dao_TableInfo::get()
	 */
	public function testGet()
	{
		$this->assertEquals( "O_Dao_TableInfo", get_class( $this->O_Dao_TableInfo ) );
	
	}

	public function testCreateTable()
	{
		$this->assertFalse( $this->O_Dao_TableInfo->tableExists(), "Table should not exist" );
		
		$this->assertEquals( "PDOStatement", get_class( $this->O_Dao_TableInfo->createTable() ), "Creating table" );
		
		$this->assertTrue( $this->O_Dao_TableInfo->tableExists(), "Table should exist now" );
	}

	public function testTableName()
	{
		$this->assertEquals( "create_table", $this->O_Dao_TableInfo->getTableName() );
	}

	/**
	 * Tests O_Dao_TableInfo->getFieldInfo()
	 */
	public function testGetFieldInfo()
	{
		$this->assertEquals( "O_Dao_FieldInfo", get_class( $this->O_Dao_TableInfo->getFieldInfo( "textfield" ) ) );
	}

	/**
	 * Tests O_Dao_TableInfo->getFields()
	 */
	public function testGetFields()
	{
		$this->assertEquals( 2, count( $this->O_Dao_TableInfo->getFields(/* parameters */) ) );
	
	}

	/**
	 * Tests O_Dao_TableInfo::getPrefix()
	 */
	public function testGetPrefix()
	{
		
		$this->assertEquals( "", O_Dao_TableInfo::getPrefix(), "Prefix is clear" );
		
		O_Dao_TableInfo::setPrefix( "test" );
		
		$this->assertEquals( "test_", O_Dao_TableInfo::getPrefix(), "Setting test prefix" );
		
		O_Dao_TableInfo::setPrefix( "" );
	
	}

	public function testGetParam()
	{
		$fieldInfo = $this->O_Dao_TableInfo->getFieldInfo( "intfield" );
		
		$this->assertEquals( 1, $fieldInfo->getParam( "test" ) );
		$this->assertNull( $fieldInfo->getParam( "asdasas" ) );
	}

}

