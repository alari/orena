<?php
class Test_Cmd_OpenIdServer extends O_Command {

	public function process()
	{
		$server = new O_OpenId_Provider( );
		
		$user = O_OpenId_Provider_UserPlugin::getByIdentity( "http://exp/" );
		if (!$user) {
			$user = new O_Acl_User( );
			$user->openid_identity = "http://exp/";
			$user->save();
		}
		
		$server->register( "exp", "12345" );
		
		O_Http_Session::setUser( $user );
		
		if ($_SERVER[ 'REQUEST_METHOD' ] == 'GET' && isset( $_GET[ 'openid_action' ] ) && $_GET[ 'openid_action' ] === 'login') {
			$server->login( "exp", "12345" );
			unset( $_GET[ 'openid_action' ] );
			Zend_OpenId::redirect( Zend_OpenId::selfUrl(), $_GET );
		} else if ($_SERVER[ 'REQUEST_METHOD' ] == 'GET' && isset( $_GET[ 'openid_action' ] ) && $_GET[ 'openid_action' ] ===
					 'trust') {
						unset( $_GET[ 'openid_action' ] );
			$server->respondToConsumer( $_GET );
		} else {
			$ret = $server->handle();
			if (is_string( $ret )) {
				echo $ret;
			} else if ($ret !== true) {
				header( 'HTTP/1.0 403 Forbidden' );
				echo 'Forbidden';
			}
		}
	}
}