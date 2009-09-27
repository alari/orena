<?php
require_once "Auth/OpenID/Discover.php";
abstract class O_OpenId_Provider_Command extends O_Command {

	protected $identity;

	public function process()
	{
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

	protected function prepareIdentity() {

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

		if(O_Base_Session::isLogged() && O_Base_Session::getUser()->id = $user->id) {
		if(isset($_SESSION["provider.login.redirect"])) {
			unset($_SESSION["provider.login.redirect"]);
		}
			return true;
		}

		if(O_Base_Session::isLogged() && O_Base_Session::getUser()->id != $user->id) O_Base_Session::delUser();

		if(!isset($_SESSION["provider.login.redirect"])) {
			$_SESSION["provider.login.redirect"] = $this->buildURL()."?".$_SERVER['QUERY_STRING'];
		}

		// Password was posted
		if ($_SERVER[ 'REQUEST_METHOD' ] == "POST"){
			if($user->login($_POST["pwd"])) return true;
		}

		$tpl = $this->getTemplate();
		$tpl->mode = "auth";
		$tpl->error = "Вы должны быть авторизованы как $this->identity, чтобы войти на другой сайт с помощью OpenId.";
		return $tpl;
	}

	/**
	 * Handle a standard OpenID server request
	 */
	protected function handleServerRequest()
	{
		header( 'X-XRDS-Location: ' . $this->buildUrl( "idpXrds" ) );

		$server = $this->getServer();
		$request = $server->decodeRequest();

		if (!$request) {
			return $this->redirect( "/" );
		}

		$this->setRequestInfo( $request );

		if (in_array( $request->mode, array ('checkid_immediate', 'checkid_setup') )) {

			if ($request->idSelect()) {
				// Perform IDP-driven identifier selection
				if ($request->mode == 'checkid_immediate') {
					$response = $request->answer( false );
				} else {
					return $this->displayTrust( $request );
				}
			} else if ((!$request->identity) && (!$request->idSelect())) {
				// No identifier used or desired; display a page saying
				// so.
				return $this->redirect( "/" );
			} else if ($request->immediate) {
				$response = $request->answer( false, buildURL() );
			} else {
				$r = $this->prepareUserAuthenticated();
				if(is_object($r)) return $r;
				return $this->displayTrust( $request );
			}
		} else {
			$response = $server->handleRequest( $request );
		}

		$webresponse = $server->encodeResponse( $response );

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

	/**
	 * Ask the user whether he wants to trust this site
	 */
	function action_trust()
	{
		$info = getRequestInfo();
		$trusted = isset( $_POST[ 'trust' ] );
		return $this->doAuth( $info, $trusted, true, @$_POST[ 'idSelect' ] );
	}

	protected function displayTrust( $request )
	{
		;
	}

	function trust_render( $info )
	{
		$current_user = getLoggedInUser();
		$lnk = link_render( idURL( $current_user ) );
		$trust_root = htmlspecialchars( $info->trust_root );
		$trust_url = buildURL( 'trust', true );

		if ($info->idSelect()) {
			$prompt = id_select_pat;
		} else {
			$prompt = sprintf( normal_pat, $lnk, $trust_root );
		}

		$form = sprintf( trust_form_pat, $trust_url, $prompt );

		return page_render( $form, $current_user, 'Trust This Site' );
	}

	/**
	 * Get the URL of the current script
	 *
	 * @return string
	 */
	abstract protected function getServerURL();

	/**
	 * Build a URL to a server action
	 *
	 * @return string
	 */
	protected function buildURL( $action = null, $escaped = true )
	{
		$url = $this->getServerURL();
		if ($action) {
			$url .= '/' . $action;
		}
		return $escaped ? htmlspecialchars( $url, ENT_QUOTES ) : $url;
	}

	/**
	 * Instantiate a new OpenID server object
	 *
	 * @return O_OpenId_Provider
	 */
	protected function getServer()
	{
		return O_OpenId_Provider::getInstance( O_OpenId_Storage::getInstance(), $this->buildURL() );
	}

	private function getRequestInfo()
	{
		return isset( $_SESSION[ 'request' ] ) ? unserialize( $_SESSION[ 'request' ] ) : false;
	}

	private function setRequestInfo( $info = null )
	{
		if (!isset( $info )) {
			unset( $_SESSION[ 'request' ] );
		} else {
			$_SESSION[ 'request' ] = serialize( $info );
		}
	}

	/**
	 * Return a hashed form of the user's password
	 */
	function hashPassword( $password )
	{
		return bin2hex( Auth_OpenID_SHA1( $password ) );
	}

	/**
	 * Get the openid_url out of the cookie
	 *
	 * @return mixed $openid_url The URL that was stored in the cookie or
	 * false if there is none present or if the cookie is bad.
	 */
	function getLoggedInUser()
	{
		return isset( $_SESSION[ 'openid_url' ] ) ? $_SESSION[ 'openid_url' ] : false;
	}

	/**
	 * Set the openid_url in the cookie
	 *
	 * @param mixed $identity_url The URL to set. If set to null, the
	 * value will be unset.
	 */
	function setLoggedInUser( $identity_url = null )
	{
		if (!isset( $identity_url )) {
			unset( $_SESSION[ 'openid_url' ] );
		} else {
			$_SESSION[ 'openid_url' ] = $identity_url;
		}
	}

	function getSreg( $identity )
	{
		// from config.php
		global $openid_sreg;

		if (!is_array( $openid_sreg )) {
			return null;
		}

		return $openid_sreg[ $identity ];

	}

	function authCancel( $info )
	{
		if ($info) {
			setRequestInfo();
			$url = $info->getCancelURL();
		} else {
			$url = getServerURL();
		}
		return $this->redirect( $url );
	}

	function doAuth( $info, $trusted = null, $fail_cancels = false, $idpSelect = null )
	{
		if (!$info) {
			// There is no authentication information, so bail
			return authCancel( null );
		}

		if ($info->idSelect()) {
			if ($idpSelect) {
				$req_url = idURL( $idpSelect );
			} else {
				$trusted = false;
			}
		} else {
			$req_url = $info->identity;
		}

		$user = getLoggedInUser();
		setRequestInfo( $info );

		if ((!$info->idSelect()) && ($req_url != idURL( $user ))) {
			return login_render( array (), $req_url, $req_url );
		}

		$trust_root = $info->trust_root;

		if ($trusted) {
			setRequestInfo();
			$server = & getServer();
			$response = & $info->answer( true, null, $req_url );

			// Answer with some sample Simple Registration data.
			$sreg_data = array ('fullname' => 'Example User',
												'nickname' => 'example',
												'dob' => '1970-01-01',
												'email' => 'invalid@example.com',
												'gender' => 'F', 'postcode' => '12345',
												'country' => 'ES', 'language' => 'eu',
												'timezone' => 'America/New_York');

			// Add the simple registration response values to the OpenID
			// response message.
			$sreg_request = Auth_OpenID_SRegRequest::fromOpenIDRequest(
					$info );

			$sreg_response = Auth_OpenID_SRegResponse::extractResponse( $sreg_request, $sreg_data );

			$sreg_response->toMessage( $response->fields );

			// Generate a response to send to the user agent.
			$webresponse = & $server->encodeResponse( $response );

			$new_headers = array ();

			foreach ($webresponse->headers as $k => $v) {
				$new_headers[] = $k . ": " . $v;
			}

			return array ($new_headers, $webresponse->body);
		} elseif ($fail_cancels) {
			return authCancel( $info );
		} else {
			return trust_render( $info );
		}
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
