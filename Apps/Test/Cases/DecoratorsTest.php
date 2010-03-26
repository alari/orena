<?php
/**
 * O_Meta_Decorators test case.
 */
class DecoratorsTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var O_Meta_Decorators
	 */
	private $O_Meta_Decorators;

	/**
	 * Prepares the environment before running a test.
	 */
	protected function setUp()
	{
		parent::setUp();
		O("_decorators/Test", Array($this, "_test"));
		O("_decorators/Value", Array($this, "_value"));
	}

	public function _test(O_Meta_Decorators $d) {
		if($d->isCalled()) return;
		$params = $d->getHandlerParams();
		foreach($params as $k=>$v) {
			$d->$k = $v;
		}
	}

	public function _value(O_Meta_Decorators $d) {
		if($d->isCalled()) throw new Exception("Calling value after assigned");
		$params = $d->getHandlerParams();
		$d->value = array_shift($params);
	}

	public function testProp() {
		$d = new Test_Models_Decorators;
		O_Meta::call(array($d, '$prop'), "something");
		$this->assertEquals(123, $d->prop);
	}

	public function testStProp() {
		O_Meta::call(array(Test_Models_Decorators, '$stProp'), "something");
		$this->assertEquals(456, Test_Models_Decorators::$stProp);
	}

	public function testConstruct() {
		$o = O_Meta::call("Test_Models_Decorators", "foo", "bar");
		$k = "Test_Models_Decorators::__construct";
		$this->assertArrayHasKey($k, $o->called);
		$this->assertEquals(432, $o->called[$k][0]);
		$this->assertEquals(432, $o->called[$k][1]);
	}

	public function testInvoke() {
		$o = new O_Meta;
		$o = O_Meta::call($o, "foo", "bar");
		$k = "Test_Models_Decorators::__invoke";
		$this->assertArrayHasKey($k, $o->called);
		$this->assertEquals(432, $o->called[$k][0]);
		$this->assertEquals(432, $o->called[$k][1]);
	}

	public function testMethod() {
		$o = new O_Meta;
		$o = O_Meta::call(Array($o, "method"), "foo", "bar");
		$k = "Test_Models_Decorators::method";
		$this->assertArrayHasKey($k, $o->called);
		$this->assertEquals(432, $o->called[$k][0]);
		$this->assertEquals(432, $o->called[$k][1]);
	}

	public function testStMethod() {
		$a = O_Meta::call("Test_Models_Decorators::stMethod", "foo", "bar");
		$this->assertEquals(array("Test_Models_Decorators::stMethod", 123, 345), $a);
	}
}