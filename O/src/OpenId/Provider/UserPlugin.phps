<?php
/**
 * @field trusted_sites -owns many O_OpenId_Provider_TrustedSite -inverse user
 * @field identity varchar(64)
 * @field pwd_hash varchar(32)
 * @index identity -unique
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
		self::normalize( $id );
		if (!isset( self::$objs[ $id ] )) {
			$class = O_Registry::get( "app/classnames/user" );
			self::$objs[ $id ] = O_Dao_Query::get( $class )->test( "identity", $id )->getOne();
		}
		return self::$objs[ $id ];
	}

	/**
	 * Normalizes openid identifier
	 *
	 * @param string $id
	 */
	static public function normalize( &$id )
	{
		Zend_OpenId::normalize( $id );
	}

	/**
	 * Stores information about logged in user
	 *
	 * @param string $id user identity URL
	 * @return bool
	 */
	public function setLoggedInUser( $id )
	{
		call_user_func( array (O_Registry::get( "app/classnames/session" ), "setUser"), self::getByIdentity( $id ) );
	}

	/**
	 * Returns identity URL of logged in user or false
	 *
	 * @return mixed
	 */
	public function getLoggedInUser()
	{
		$user = call_user_func( array (O_Registry::get( "app/classnames/session" ), "getUser") );
		return $user instanceof O_Dao_ActiveRecord ? $user->identity : null;
	}

	/**
	 * Performs logout. Clears information about logged in user.
	 *
	 * @return bool
	 */
	public function delLoggedInUser()
	{
		call_user_func( array (O_Registry::get( "app/classnames/session" ), "delUser") );
	}
}