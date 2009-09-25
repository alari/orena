<?php
class Test_Tpl_Main extends O_OpenId_Consumer_Template  {

	public function __construct()
	{
		$this->layoutClass = "O_Html_MainLayout";
		$this->layout()->title = "Orena Framework Test Suite Running";
	}

	public function displayContents()
	{
		$this->layout()->addHeadLink( "openid.server", "http://{$_SERVER['HTTP_HOST']}/openid/" );

		parent::displayContents();

		echo "<pre>";
		PHPUnit_TextUI_TestRunner::run( Test_Suite::suite() );
		echo "</pre><br/>";

		$record = O_Dao_ActiveRecord::getById( 59, "Test_Models_Core" );

		if ($record) {

			echo "<hr/>";
			$record->show( $this->layout() );
			echo "<hr/>";

			$q = new O_Dao_Query( "Test_Models_Core" );
			$q->where( "intfield is not null" )->limit( 2 )->show( $this->layout() );

			$form = new O_Dao_Renderer_FormProcessor( );
			$form->setActiveRecord( $record );
			$form->setLayout( $this->layout() );

			if ($form->handle()) {
				throw new O_Ex_Redirect( );
			}
			$form->show();
		}
		if (!isset( $_SESSION[ "my_test" ] )) {
			$_SESSION[ "my_test" ] = 1;
		}
		echo $_SESSION[ "my_test" ]++;

		echo "<hr/>";
	}

}