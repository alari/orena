<?php
class Test_Tpl_Main extends O_Html_Template {

	public function __construct()
	{
		$this->layoutClass = "O_Html_MainLayout";
		$this->layout()->title = "Orena Framework Test Suite Running";
	}

	public function displayContents()
	{
		$this->layout()->addHeadLink( "openid.server", "http://{$_SERVER['HTTP_HOST']}/openid/" );
		
		$status = "";
		if (isset( $_POST[ 'openid_action' ] ) && $_POST[ 'openid_action' ] == "login" && !empty( 
				$_POST[ 'openid_identifier' ] )) {
			
			$consumer = new O_OpenId_Consumer( );
			if (!$consumer->login( $_POST[ 'openid_identifier' ] )) {
				$status = "OpenID login failed. // ";
				echo $consumer->getError();
			}
		} elseif (isset( $_GET[ 'openid_mode' ] )) {
			if ($_GET[ 'openid_mode' ] == "id_res") {
				$consumer = new O_OpenId_Consumer( );
				if ($consumer->verify( $_GET, $id )) {
					$status = "VALID " . htmlspecialchars( $id ); // On production: redirect here
				} else {
					$status = "INVALID " . htmlspecialchars( $id );
				}
			} else if ($_GET[ 'openid_mode' ] == "cancel") {
				$status = "CANCELLED";
			}
		}
		?>

<form method="post">
<fieldset><legend>OpenID Login // <?=$status?></legend> <input
	type="text" name="openid_identifier" value="" /> <input type="submit"
	name="openid_action" value="login" /></fieldset>
</form>

<?
		
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
		
		echo "<hr/>";
	}

}