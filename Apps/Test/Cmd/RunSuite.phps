<?php
class Test_Cmd_RunSuite extends O_Command {
	public function process() {
		echo "<pre>";
		PHPUnit_TextUI_TestRunner::run( Test_Suite::suite() );
		echo "</pre><br/>";
	}
}