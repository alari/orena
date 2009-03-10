<?php
/**
 * @field trusted_sutes -owns many O_OpenId_Provider_TrustedSite -inverse user
 * @field openid_identity varchar(64)
 * @field openid_pwd varchar(32)
 * @index openid_identity -unique
 */
class O_OpenId_Provider_UserPlugin extends Zend_OpenId_Provider_User implements O_Dao_iPlugin {
	private static $instance;
	private static $objs = Array ();

	/**
	 * Returns instance of class
	 *
	 * @return O_OpenId_Provider_UserPlugin
	 */
	static public function getInstance()
	{
		if (!self::$instance)
			self::$instance = new self( );
		return self::$instance;
	}

	/**
	 * Returns user by openid identity url
	 *
	 * @param string $id
	 * @return object
	 */
	static public function getByIdentity( $id )
	{
		if (!isset( self::$objs[ $id ] )) {
			$class = O_Registry::get( "app/acl/user_class" );
			self::$objs[ $id ] = O_Dao_Query::get( $class )->test( "openid_identity", $id )->getOne();
		}
		return self::$objs[ $id ];
	}

	/**
	 * Stores information about logged in user
	 *
	 * @param string $id user identity URL
	 * @return bool
	 */
	public function setLoggedInUser( $id )
	{
		O_Http_Session::setUser( self::getByIdentity( $id ) );
	}

	/**
	 * Returns identity URL of logged in user or false
	 *
	 * @return mixed
	 */
	public function getLoggedInUser()
	{
		$user = O_Http_Session::getUser();
		return $user ? $user->openid_identity : null;
	}

	/**
	 * Performs logout. Clears information about logged in user.
	 *
	 * @return bool
	 */
	public function delLoggedInUser()
	{
		O_Http_Session::delUser();
	}
}