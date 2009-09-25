<?php
class O_OpenId_Consumer extends Auth_OpenID_Consumer {

	public function __construct( Auth_OpenID_OpenIDStore $store = null, $session = null, $consumer_cls = null )
	{
		if (!$store)
			$store = O_OpenId_Consumer_Storage::getInstance();
		parent::Auth_OpenID_Consumer( $store, $session, $consumer_cls );
	}

}