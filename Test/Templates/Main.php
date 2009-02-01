<?php
class Test_Templates_Main extends O_Html_Template {
	public function __construct() {
		$this->layoutClass = "O_Html_MainLayout";
		$this->getLayout()->title = "Orena Framework Test Suite Running";
	}

	public function displayContents() {
		echo "<pre>";
		PHPUnit_TextUI_TestRunner::run(Test_Suite::suite());
		echo "</pre>";
	}

}