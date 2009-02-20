<?php
class Test_Tpl_Main extends O_Html_Template {

	public function __construct()
	{
		$this->layoutClass = "O_Html_MainLayout";
		$this->layout()->title = "Orena Framework Test Suite Running";
	}

	public function displayContents()
	{
		echo "<pre>";
		PHPUnit_TextUI_TestRunner::run( Test_Suite::suite() );
		echo "</pre><br/>";

		O_Dao_ActiveRecord::getById( 59, "Test_Models_Core" )->show( $this->layout() );

		$q = new O_Dao_Query( "Test_Models_Core" );
		$q->where( "intfield is not null" )->limit( 2 )->show( $this->layout() );

		if($_SERVER['REQUEST_METHOD'] == "POST") {
			O_Registry::set("app/env/params", $_POST);
			$arr = O_Dao_FormHandler::edit("Test_Models_Core");
		} else {
			$arr=array("errors"=>array());
		}

		O_Dao_Renderer::edit(O_Dao_ActiveRecord::getById( 59, "Test_Models_Core" ), "/test.php", $this->layout(), @$arr["errors"], "Edit it");

		if(!isset($_SESSION["my_test"])) {
			$_SESSION["my_test"] = 1;
		}
		echo $_SESSION["my_test"]++;
	}

}