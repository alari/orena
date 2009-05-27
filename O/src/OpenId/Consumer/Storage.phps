<?php
/**
 *
 */
class O_OpenId_Consumer_Storage extends Zend_OpenId_Consumer_Storage {
	/**
	 * Singleton pattern
	 *
	 * @var O_OpenId_Consumer_Storage
	 */
	private static $singleton;
	
	const TABLE_NONCE = "o_openid_nonce";
	const TABLE_ASSOC = "o_openid_assoc";
	const TABLE_DISCOVERY = "o_openid_discovery";

	/**
	 * Returns instance of storage
	 *
	 * @return O_OpenId_Consumer_Storage
	 */
	static public function getInstance()
	{
		if (!self::$singleton)
			self::$singleton = new self( );
		return self::$singleton;
	}

	/**
	 * Creates database tables if they don't exist
	 *
	 */
	protected function __construct()
	{
		$nonce = O_Db_Query::get( self::TABLE_NONCE );
		if (!$nonce->tableExists()) {
			$nonce->field( "nonce", "varchar(255) not null" )->index( "nonce", "unique" )->field( 
					"created", "int not null" )->create();
		}
		
		$assoc = O_Db_Query::get( self::TABLE_ASSOC );
		if (!$assoc->tableExists()) {
			$assoc->field( "url", "varchar(255) not null" )->field( "handle", 
					"varchar(255) not null" )->field( "mac_func", "varchar(16) not null" )->field( 
					"secret", "varchar(255) not null" )->field( "expires", "int not null" )->index( 
					"url", "unique" )->create();
		}
		
		$discovery = O_Db_Query::get( self::TABLE_DISCOVERY );
		if (!$discovery->tableExists()) {
			$discovery->field( "disc_id", "varchar(255) not null" )->field( "real_id", 
					"varchar(255) not null" )->field( "server", "varchar(255) not null" )->field( 
					"version", "float default 0" )->field( "expires", "int not null" )->index( 
					"disc_id", "unique" )->create();
		}
	}

	/**
	 * Stores information about association identified by $url/$handle
	 *
	 * @param string $url OpenID server URL
	 * @param string $handle assiciation handle
	 * @param string $macFunc HMAC function (sha1 or sha256)
	 * @param string $secret shared secret
	 * @param long $expires expiration UNIX time
	 * @return void
	 */
	public function addAssociation( $url, $handle, $macFunc, $secret, $expires )
	{
		$secret = base64_encode( $secret );
		O_Db_Query::get( self::TABLE_ASSOC )->field( "url", $url )->field( "handle", $handle )->field( 
				"mac_func", $macFunc )->field( "secret", $secret )->field( "expires", $expires )->insert();
	}

	/**
	 * Gets information about association identified by $url
	 * Returns true if given association found and not expired and false
	 * otherwise
	 *
	 * @param string $url OpenID server URL
	 * @param string &$handle assiciation handle
	 * @param string &$macFunc HMAC function (sha1 or sha256)
	 * @param string &$secret shared secret
	 * @param long &$expires expiration UNIX time
	 * @return bool
	 */
	public function getAssociation( $url, &$handle, &$macFunc, &$secret, &$expires )
	{
		O_Db_Query::get( self::TABLE_ASSOC )->test( "expires", time(), O_Db_Query::LT )->delete();
		$assoc = O_Db_Query::get( self::TABLE_ASSOC )->test( "url", $url )->select()->fetch();
		if ($assoc) {
			$handle = $assoc[ "handle" ];
			$macFunc = $assoc[ "mac_func" ];
			$secret = base64_decode( $assoc[ "secret" ] );
			$expires = $assoc[ "expires" ];
			return true;
		}
		return false;
	}

	/**
	 * Gets information about association identified by $handle
	 * Returns true if given association found and not expired and false
	 * othverwise
	 *
	 * @param string $handle assiciation handle
	 * @param string &$url OpenID server URL
	 * @param string &$macFunc HMAC function (sha1 or sha256)
	 * @param string &$secret shared secret
	 * @param long &$expires expiration UNIX time
	 * @return bool
	 */
	public function getAssociationByHandle( $handle, &$url, &$macFunc, &$secret, &$expires )
	{
		
		O_Db_Query::get( self::TABLE_ASSOC )->test( "expires", time(), O_Db_Query::LT )->delete();
		$assoc = O_Db_Query::get( self::TABLE_ASSOC )->test( "handle", $handle )->select()->fetch();
		if ($assoc) {
			$url = $assoc[ "url" ];
			$macFunc = $assoc[ "mac_func" ];
			$secret = base64_decode( $assoc[ "secret" ] );
			$expires = $assoc[ "expires" ];
			return true;
		}
		return false;
	}

	/**
	 * Deletes association identified by $url
	 *
	 * @param string $url OpenID server URL
	 * @return void
	 */
	public function delAssociation( $url )
	{
		O_Db_Query::get( self::TABLE_ASSOC )->test( "url", $url )->delete();
	}

	/**
	 * Stores information discovered from identity $id
	 *
	 * @param string $id identity
	 * @param string $realId discovered real identity URL
	 * @param string $server discovered OpenID server URL
	 * @param float $version discovered OpenID protocol version
	 * @param long $expires expiration UNIX time
	 * @return void
	 */
	public function addDiscoveryInfo( $id, $realId, $server, $version, $expires )
	{
		O_Db_Query::get( self::TABLE_DISCOVERY )->field( "disc_id", $id )->field( "real_id", 
				$realId )->field( "server", $server )->field( "version", $version )->field( 
				"expires", $expires )->insert();
	}

	/**
	 * Gets information discovered from identity $id
	 * Returns true if such information exists and false otherwise
	 *
	 * @param string $id identity
	 * @param string &$realId discovered real identity URL
	 * @param string &$server discovered OpenID server URL
	 * @param float &$version discovered OpenID protocol version
	 * @param long &$expires expiration UNIX time
	 * @return bool
	 */
	public function getDiscoveryInfo( $id, &$realId, &$server, &$version, &$expires )
	{
		O_Db_Query::get( self::TABLE_DISCOVERY )->test( "expires", time(), O_Db_Query::LT )->delete();
		$disc = O_Db_Query::get( self::TABLE_DISCOVERY )->test( "disc_id", $id )->select()->fetch();
		if ($disc) {
			$realId = $disc[ "real_id" ];
			$server = $disc[ "server" ];
			$version = $disc[ "version" ];
			$expires = $disc[ "expires" ];
			return true;
		}
		return false;
	}

	/**
	 * Removes cached information discovered from identity $id
	 *
	 * @param string $id identity
	 * @return bool
	 */
	public function delDiscoveryInfo( $id )
	{
		O_Db_Query::get( self::TABLE_DISCOVERY )->test( "disc_id", $id )->delete();
		return true;
	}

	/**
	 * The function checks the uniqueness of openid.response_nonce
	 *
	 * @param string $provider openid.openid_op_endpoint field from authentication response
	 * @param string $nonce openid.response_nonce field from authentication response
	 * @return bool
	 */
	public function isUniqueNonce( $provider, $nonce )
	{
		try {
			O_Db_Query::get( self::TABLE_NONCE )->field( "nonce", $nonce )->field( "created", 
					time() )->insert();
		}
		catch (PDOException $e) {
			return false;
		}
		return true;
	}

	/**
	 * Removes data from the uniqueness database that is older then given date
	 *
	 * @param string $date Date of expired data
	 */
	public function purgeNonces( $date = null )
	{
		O_Db_Query::get( self::TABLE_NONCE )->test( "created", strtotime( $date ), O_Db_Query::LT )->delete();
	}
}