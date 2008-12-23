<?php
/**
 * Dao_Signals test case.
 */
class Test_Cases_DaoSignals extends PHPUnit_Framework_TestCase {
	
	protected static $signalResults = Array ();

	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		self::$signalResults = Array ();
		parent::setUp();
	}

	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		self::$signalResults = Array ();
		parent::tearDown();
	}

	static public function listener_class( $value )
	{
		self::$signalResults[ "class" ][] = $value;
	}

	static public function listener_event( $value )
	{
		self::$signalResults[ "event" ][] = $value;
	}

	static public function listener_signal( $value )
	{
		self::$signalResults[ "signal" ][] = $value;
	}

	static public function listener( $value )
	{
		self::$signalResults[ "none" ][] = $value;
	}

	static public function listener_es( $value )
	{
		self::$signalResults[ "event-signal" ][] = $value;
	}

	static public function listener_ec( $value )
	{
		self::$signalResults[ "event-class" ][] = $value;
	}

	static public function listener_sc( $value )
	{
		self::$signalResults[ "signal-class" ][] = $value;
	}

	static public function listener_esc( $value )
	{
		self::$signalResults[ "event-signal-class" ][] = $value;
	}

	public function __construct()
	{
		Dao_Signals::bind( __CLASS__ . "::listener" );
		Dao_Signals::bind( __CLASS__ . "::listener_class", null, null, "Test_Models_Core" );
		Dao_Signals::bind( __CLASS__ . "::listener_event", Dao_Signals::EVENT_REMOVE );
		Dao_Signals::bind( __CLASS__ . "::listener_signal", null, "test" );
		Dao_Signals::bind( __CLASS__ . "::listener_esc", Dao_Signals::EVENT_SET, "test", "Test_Models_Core" );
		Dao_Signals::bind( __CLASS__ . "::listener_es", Dao_Signals::EVENT_SET, "test" );
		Dao_Signals::bind( __CLASS__ . "::listener_ec", Dao_Signals::EVENT_SET, null, "Test_Models_Sub" );
		Dao_Signals::bind( __CLASS__ . "::listener_sc", null, "test", "Test_Models_Sub" );
		
		$this->core = new Test_Models_Core( );
		$this->sub = new Test_Models_Sub( );
	}

	public function testListen()
	{
		$this->core->subs[] = $this->sub;
		$this->assertEquals( Array (), self::$signalResults, "No signal given" );
		
		$this->core->intfield = 5;
		$expected = Array ("none" => array (null, 5), "class" => array (null, 5), "event" => array (null));
		$this->assertEquals( $expected, self::$signalResults, "Results of value setting without signal type." );
	}

	public function testListenType()
	{
		$v = "test field";
		$this->core->textfield = $v;
		$expected = Array ("none" => array (null, $v), "event" => array (null), "signal" => array (null, $v), 
							"class" => array (null, $v), "event-signal-class" => array ($v), 
							"event-signal" => array ($v));
		$this->assertEquals( $expected, self::$signalResults, "Results of value setting with signal type." );
	}

	public function testUnbind()
	{
		Dao_Signals::unbind( __CLASS__ . "::listener_sc" );
		$expected = Array (__CLASS__ . "::listener", __CLASS__ . "::listener_ec");
		$this->assertEquals( $expected, Dao_Signals::getListeners( Dao_Signals::EVENT_SET, null, "Test_Models_Sub" ) );
		
		Dao_Signals::unbind( null, Dao_Signals::EVENT_SET );
		$expected = Array (__CLASS__ . "::listener", __CLASS__ . "::listener_signal");
		$this->assertEquals( $expected, Dao_Signals::getListeners( null, "test", null ) );
		
		Dao_Signals::unbind();
	}

}