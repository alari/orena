<?php
require_once "Auth/OpenID/Discover.php";
abstract class O_OpenId_Provider_Command extends O_Command {

	protected $identity;

	public function process()
	{error_reporting(E_ALL);function my_echo(){print_r(func_get_args());}
set_error_handler("my_echo", E_ERROR);
		$this->prepareIdentity();
		$action = O_Registry::get( "app/current/action" );
		switch ($action) {
			case "idp-xrds" :
				return $this->displayIdpXrds();
			case "user-xrds" :
				return $this->displayUserXrds();
			default :
				return $this->handleServerRequest();
		}
	}

	protected function prepareIdentity()
	{

		// Prepare current identity
		$this->identity = O_Registry::get( "app/env/http_host" );
		if (strpos( $this->identity, "openid." ) === 0 || strpos( $this->identity, "www." ) === 0)
			list (, $this->identity) = explode( ".", $this->identity, 2 );
	}

	protected function prepareUserAuthenticated()
	{
		$user = O_OpenId_Provider_UserPlugin::getByIdentity( $this->identity );
		if (!$user) {
			return $this->redirect( "/" );
		}

		if (O_Base_Session::isLogged() && O_Base_Session::getUser()->id = $user->id) {
			if (isset( $_SESSION[ "provider.login.redirect" ] )) {
				unset( $_SESSION[ "provider.login.redirect" ] );
			}
			return true;
		}

		if (O_Base_Session::isLogged() && O_Base_Session::getUser()->id != $user->id)
			O_Base_Session::delUser();

		if (!isset( $_SESSION[ "provider.login.redirect" ] )) {
			$_SESSION[ "provider.login.redirect" ] = $this->buildURL() . "?" . $_SERVER[ 'QUERY_STRING' ];
		}

		// Password was posted
		if ($_SERVER[ 'REQUEST_METHOD' ] == "POST") {
			if ($user->login( $_POST[ "pwd" ] ))
				return true;
		}

		$tpl = $this->getTemplate();
		$tpl->mode = "auth";
		$tpl->error = "Вы должны быть авторизованы как $this->identity, чтобы войти на другой сайт с помощью OpenId.";
		return $tpl;
	}

	protected function decidedPositive( $trust_root )
	{
		$user = O_OpenId_Provider_UserPlugin::getByIdentity( $this->identity );
		if ($user->trusted_sites->test( "site", $trust_root )->getOne())
			return true;
		if ($_SERVER[ 'REQUEST_METHOD' ] == "POST" && $_POST[ "openid_action" ] == "trust") {
			if (isset( $_POST[ "allow" ] )) {
				return true;
			} else
				throw new O_Ex_Error( "Cancelled" );
		}
		return false;
	}

	protected function showDecidePage( $trust_root )
	{
		$tpl = $this->getTemplate();
		$tpl->mode = "trust";
		$tpl->site = $trust_root;
		return $tpl;
	}

	protected function buildUrl($action) {
		return "http://".O_Registry::get("app/env/http_host")."/openid/provider".($action?"/".$action:"");
	}


	/**
	 * Handle a standard OpenID server request
	 */
	protected function handleServerRequest()
	{
		header( 'X-XRDS-Location: ' . $this->buildUrl( "idp-xrds" ) );

		$oserver = new Auth_OpenID_Server( O_OpenId_Storage::getInstance(),
				"http://centralis.name" );
		$request = $oserver->decodeRequest();

		if(!$request) return $this->redirect("/");
		if ($this->identity != $request->identity)
			throw new O_Ex_Error( "Wrong identity: $this->identity != $request->identity" );

		if (in_array( $request->mode, array ('checkid_immediate', 'checkid_setup') )) {
			if ($this->decidedPositive( $request->trust_root )) {
				$response = $request->answer( true );
			} else if ($request->immediate) {
				$response = $request->answer( false );
			} else {
				return $this->showDecidePage( $request->trust_root );
			}
		} else {
			$response = $oserver->handleRequest( $request );
		}

		$webresponse = $oserver->encodeResponse( $response );

		if ($webresponse->code != AUTH_OPENID_HTTP_OK) {
			header( sprintf( "HTTP/1.1 %d ", $webresponse->code ), true, $webresponse->code );
		}

		foreach ($webresponse->headers as $k => $v) {
			header( "$k: $v" );
		}

		header( "Connection: close" );
		print $webresponse->body;
		exit( 0 );
	}

	protected function displayIdpXrds()
	{
		Header( "Content-type: application/xrds+xml" );

		printf(
				'<?xml version="1.0" encoding="UTF-8"?>
<xrds:XRDS
    xmlns:xrds="xri://$xrds"
    xmlns="xri://$xrd*($v*2.0)">
  <XRD>
    <Service priority="0">
      <Type>%s</Type>
      <URI>%s</URI>
    </Service>
  </XRD>
</xrds:XRDS>
', Auth_OpenID_TYPE_2_0_IDP, $this->buildURL() );
	}

	protected function displayUserXrds()
	{
		Header( "Content-type: application/xrds+xml" );

		printf(
				'<?xml version="1.0" encoding="UTF-8"?>
<xrds:XRDS
    xmlns:xrds="xri://$xrds"
    xmlns="xri://$xrd*($v*2.0)">
  <XRD>
    <Service priority="0">
      <Type>%s</Type>
      <Type>%s</Type>
      <URI>%s</URI>
    </Service>
  </XRD>
</xrds:XRDS>
', Auth_OpenID_TYPE_2_0,
				Auth_OpenID_TYPE_1_1, $this->buildURL() );
	}
}
