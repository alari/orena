<?php

class Test_Models_Decorators {
	public $called = Array();
	/**
	 * @Value(123)
	 */
	public $prop;
	/**
	 * @Value(456)
	 */
	static public $stProp;

	/**
	 * @Test(foo=432, bar=123)
	 */
	public function __construct($foo, $bar) {
		$this->called[__METHOD__] = Array($foo, $bar);
	}

	/**
	 * @Test(foo=432, bar=123)
	 */
	public function __invoke($foo, $bar) {
		$this->called[__METHOD__] = Array($foo, $bar);
	}

	/**
	 * @Test(foo=432, bar=123)
	 */
	public function method($foo, $bar) {
		$this->called[__METHOD__] = Array($foo, $bar);
	}

	/**
	 * @Test(foo=123, bar=345)
	 */
	static public function stMethod($foo, $bar) {
		$this->called[__METHOD__] = Array($foo, $bar);
	}
}