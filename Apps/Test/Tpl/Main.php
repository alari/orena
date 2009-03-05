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

		$record = O_Dao_ActiveRecord::getById( 59, "Test_Models_Core" );

		echo "<hr/>";
		$record->show( $this->layout() );
		echo "<hr/>";

		$q = new O_Dao_Query( "Test_Models_Core" );
		$q->where( "intfield is not null" )->limit( 2 )->show( $this->layout() );

		$form = new O_Dao_Renderer_FormProcessor( );
		$form->setActiveRecord( $record );
		$form->setLayout( $this->layout() );

		echo $form->handle() ? "handled" : "not handled";
		$form->show();

		if (!isset( $_SESSION[ "my_test" ] )) {
			$_SESSION[ "my_test" ] = 1;
		}
		echo $_SESSION[ "my_test" ]++;
	}

}