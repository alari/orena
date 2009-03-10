<?php
class O_OpenId_Provider extends Zend_OpenId_Provider {

	/**
	 * Constructs a Zend_OpenId_Provider object with given parameters.
	 *
	 * @param string $loginUrl is an URL that provides login screen for
	 *  end-user (by default it is the same URL with additional GET variable
	 *  openid.action=login)
	 * @param string $trustUrl is an URL that shows a question if end-user
	 *  trust to given consumer (by default it is the same URL with additional
	 *  GET variable openid.action=trust)
	 * @param integer $sessionTtl is a default time to live for association
	 *   session in seconds (1 hour by default). Consumer must reestablish
	 *   association after that time.
	 */
	public function __construct( $loginUrl = null, $trustUrl = null, $sessionTtl = 3600 )
	{
		parent::__construct( $loginUrl, $trustUrl, O_OpenId_Provider_UserPlugin::getInstance(), 
				O_OpenId_Provider_Storage::getInstance(), $sessionTtl );
	}

}