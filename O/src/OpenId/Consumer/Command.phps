<?php
abstract class O_OpenId_Consumer_Command extends O_Command {

	public function process()
	{
		if ($this->getParam('openid_action') == "login" && $this->getParam('openid_identifier')) {

			return $this->tryAuth();

		}

		if ($this->getParam( 'openid_mode' )) {
			return $this->finishAuth();
		}

		return $this->startAuth();
	}



	/**
	 * Returns storage object
	 *
	 * @return Auth_OpenID_OpenIDStore
	 */
	protected function getStore()
	{
		return O_OpenId_Storage::getInstance();
	}

	/**
	 * Returns new consumer object
	 *
	 * @return O_OpenId_Consumer
	 */
	protected function getConsumer()
	{
		return new O_OpenId_Consumer( $this->getStore() );
	}

	/**
	 * Returns path to finish auth
	 *
	 * @return string
	 */
	protected function getReturnTo()
	{
		return "http://" . O_Registry::get( "env/http_host" ) . O_Registry::get(
				"env/request_url" );
	}

	/**
	 * Returns pattern to be handled as trusted site
	 *
	 * @return string
	 */
	protected function getTrustRoot()
	{
		return "http://" . O_Registry::get( "env/http_host" ) . "/";
	}

	/**
	 * Returns openid we're logging with
	 *
	 * @return string
	 */
	protected function getOpenId()
	{
		return $this->getParam( "openid_identifier" );
	}

	/**
	 * Adds authentication extensions (like SREG and PAPE) to an openid request
	 *
	 * @param Auth_OpenID_Request $request
	 */
	protected function addAuthExtensions( Auth_OpenID_AuthRequest $request )
	{
		include_once 'Auth/OpenID/SReg.php';
		$sreg_request = Auth_OpenID_SRegRequest::build( null, array ('nickname', 'email') );

		if ($sreg_request) {
			$request->addExtension( $sreg_request );
		}
	}

	/**
	 * Handles auth extensions when openid auth completed
	 *
	 * @param Auth_OpenID_ConsumerResponse $response
	 */
	protected function getSRegResponse( Auth_OpenID_ConsumerResponse $response )
	{
		include_once 'Auth/OpenID/SReg.php';
		$sreg_resp = Auth_OpenID_SRegResponse::fromSuccessResponse( $response );

		return $sreg_resp->contents();
	}

	/**
	 * Processes first step of openid auth
	 *
	 * @return unknown
	 */
	protected function tryAuth()
	{
		$openid = $this->getOpenId();
		$consumer = $this->getConsumer();

		// Begin the OpenID authentication process.
		$auth_request = $consumer->begin( $openid );

		// No auth request means we can't begin OpenID.
		if (!$auth_request) {
			return $this->authFailed( "Authentication error; not a valid OpenID." );
		}

		$this->addAuthExtensions( $auth_request );

		// Redirect the user to the OpenID server for authentication.
		// Store the token for this authentication so we can verify the
		// response.


		// For OpenID 1, send a redirect.  For OpenID 2, use a Javascript
		// form to send a POST request to the server.
		if ($auth_request->shouldSendRedirect()) {
			$redirect_url = $auth_request->redirectURL( $this->getTrustRoot(),
					$this->getReturnTo() );

			// If the redirect URL can't be built, display an error
			// message.
			if (Auth_OpenID::isFailure( $redirect_url )) {
				return $this->authFailed( "Could not redirect to server (#1): " . $redirect_url->message );
			} else {
				// Send redirect.
				return $this->redirect( $redirect_url );
			}
		} else {
			// Generate form markup and render it.
			$form_id = 'openid_message';
			$form_html = $auth_request->htmlMarkup( $this->getTrustRoot(), $this->getReturnTo(),
					false, array ('id' => $form_id) );

			// Display an error if the form markup couldn't be generated;
			// otherwise, render the HTML.
			if (Auth_OpenID::isFailure( $form_html )) {
				return $this->authFailed( "Could not redirect to server (#2): " . $form_html->message );
			} else {
				print $form_html;
			}
		}
	}

	/**
	 * Second step of authentication
	 *
	 * @return mixed
	 */
	protected function finishAuth()
	{
		$consumer = $this->getConsumer();

		// Complete the authentication process using the server's
		// response.
		$response = $consumer->complete( $this->getReturnTo() );

		// Check the response status.
		if ($response->status == Auth_OpenID_CANCEL) {
			return $this->authCancel( $response );
		} else if ($response->status == Auth_OpenID_FAILURE) {
			return $this->authFailed( $response->message );
		} else if ($response->status == Auth_OpenID_SUCCESS) {
			return $this->authSuccess( $response );
		}
	}

	/**
	 * Authentication completed successfull
	 *
	 * @param Auth_OpenID_SuccessResponse $response
	 * @return O_Html_Template or mixed
	 */
	abstract protected function authSuccess( Auth_OpenID_SuccessResponse $response );

	/**
	 * Authentication was cancelled; display error message
	 *
	 * @param Auth_OpenID_ConsumerResponse $response
	 * @return O_Html_Template
	 */
	protected function authCancel( Auth_OpenID_ConsumerResponse $response )
	{
		$tpl = $this->getTemplate();
		$tpl->mode = O_OpenId_Consumer_Template::MODE_EX_CANCEL;
		$tpl->error = "Verification cancelled.";
		return $tpl;
	}

	/**
	 * Authentication failed; display error message
	 *
	 * @param string $error_msg
	 * @return O_Html_Template
	 */
	protected function authFailed( $error_msg )
	{
		$tpl = $this->getTemplate();
		$tpl->mode = O_OpenId_Consumer_Template::MODE_EX_INVALID;
		$tpl->error = "OpenID authentication failed: " . $error_msg;
		return $tpl;
	}


	/**
	 * Shows login box template
	 *
	 * @return O_Html_Template
	 */
	protected function startAuth() {
		$tpl = $this->getTemplate();
		$tpl->mode = O_OpenId_Consumer_Template::MODE_AUTH;
		return $tpl;
	}
}
