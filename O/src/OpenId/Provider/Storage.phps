<?php
class O_OpenId_Provider_Storage extends Zend_OpenId_Provider_Storage {
	
	/**
	 * Storage singleton
	 *
	 * @var O_OpenId_Provider_Storage
	 */
	private static $instance;
	
	const TABLE_ASSOC = "o_openid_assoc_provider";

	/**
	 * Singleton pattern
	 *
	 * @return O_OpenId_Provider_Storage
	 */
	static public function getInstance()
	{
		if (!self::$instance) {
			self::$instance = new self( );
		}
		return self::$instance;
	}

	/**
	 * Creates required tables
	 *
	 */
	protected function __construct()
	{
		$assoc = O_Db_Query::get( self::TABLE_ASSOC );
		if (!$assoc->tableExists()) {
			$assoc->field( "handle", "varchar(255) not null" )->field( "mac_func", "varchar(16) not null" )->field( 
					"secret", "varchar(255) not null" )->field( "expires", "int not null" )->create();
		}
	}

	/**
	 * Stores information about session identified by $handle
	 *
	 * @param string $handle assiciation handle
	 * @param string $macFunc HMAC function (sha1 or sha256)
	 * @param string $secret shared secret
	 * @param string $expires expiration UNIX time
	 * @return void
	 */
	public function addAssociation( $handle, $macFunc, $secret, $expires )
	{
		O_Db_Query::get( self::TABLE_ASSOC )->test( "expires", time(), O_Db_Query::LT )->delete();
		O_Db_Query::get( self::TABLE_ASSOC )->field( "handle", $handle )->field( "mac_func", $macFunc )->field( 
				"secret", $secret )->field( "expires", $expires )->insert();
	}

	/**
	 * Stores information about trusted/untrusted site for given user
	 *
	 * @param string $id user identity URL
	 * @param string $site site URL
	 * @param mixed $trusted trust data from extensions or just a boolean value
	 * @return bool
	 */
	public function addSite( $id, $site, $trusted )
	{
		$user = O_OpenId_Provider_UserPlugin::getByIdentity( $id );
		foreach ($user->trusted_sites as $_site) {
			if ($_site->site == $site) {
				$_site->data = serialize( $trusted );
				return;
			}
		}
		$rec = new O_OpenId_Provider_TrustedSite( );
		$rec->site = $site;
		$rec->data = serialize( $trusted );
		$user->trusted_sites[] = $rec;
		$rec->save();
		return;
	}

	/**
	 * Register new user with given $id and $password
	 * Returns true in case of success and false if user with given $id already
	 * exists
	 *
	 * @param string $id user identity URL
	 * @param string $password encoded user password
	 * @return bool
	 */
	public function addUser( $id, $password )
	{
		$user = O_OpenId_Provider_UserPlugin::getByIdentity( $id );
		if ($user && $user->openid_pwd != $password) {
			$user->openid_pwd = $password;
			$user->save();
			return true;
		}
		return false;
	}

	/**
	 * Verify if user with given $id exists and has specified $password
	 *
	 * @param string $id user identity URL
	 * @param string $password user password
	 * @return bool
	 */
	public function checkUser( $id, $password )
	{
		$user = O_OpenId_Provider_UserPlugin::getByIdentity( $id );
		if (!$user)
			return false;
		return $user->openid_pwd == $password;
	}

	/**
	 * Gets information about association identified by $handle
	 * Returns true if given association found and not expired and false
	 * otherwise
	 *
	 * @param string $handle assiciation handle
	 * @param string &$macFunc HMAC function (sha1 or sha256)
	 * @param string &$secret shared secret
	 * @param string &$expires expiration UNIX time
	 * @return bool
	 */
	public function getAssociation( $handle, &$macFunc, &$secret, &$expires )
	{
		O_Db_Query::get( self::TABLE_ASSOC )->test( "expires", time(), O_Db_Query::LT )->delete();
		$assoc = O_Db_Query::get( self::TABLE_ASSOC )->test( "handle", $handle )->select()->fetch();
		if ($assoc) {
			$macFunc = $assoc[ "mac_func" ];
			$secret = $assoc[ "secret" ];
			$expires = $assoc[ "expires" ];
			return true;
		}
		return false;
	}

	/**
	 * Returns array of all trusted/untrusted sites for given user identified
	 * by $id
	 *
	 * @param string $id user identity URL
	 * @return array
	 */
	public function getTrustedSites( $id )
	{
		$user = O_OpenId_Provider_UserPlugin::getByIdentity( $id );
		$arr = array ();
		foreach ($user->trusted_sites as $site) {
			$arr[ $site->site ] = unserialize( $site->data );
		}
		return $arr;
	}

	/**
	 * Returns true if user with given $id exists and false otherwise
	 *
	 * @param string $id user identity URL
	 * @return bool
	 */
	public function hasUser( $id )
	{
		return (bool)O_OpenId_Provider_UserPlugin::getByIdentity( $id );
	}
}