<?php
/**
 * @todo think if we really do need DAO classes for storage
 *
 */
class O_OpenId_Consumer_Storage extends Zend_OpenId_Consumer_Storage {
	/**
	 * Simgleton pattern
	 *
	 * @var O_OpenId_Consumer_Storage
	 */
	private static $singleton;

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
		new O_OpenId_Consumer_Assotiation( $url, $handle, $macFunc, $secret, $expires );
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
		O_Dao_Query::get( "O_OpenId_Consumer_Assotiation" )->test( "expires", time(), O_Dao_Query::LT )->delete();
		$assoc = O_Dao_Query::get( "O_OpenId_Consumer_Assotiation" )->test( "url", $url )->getOne();
		if ($assoc) {
			$handle = $assoc->handle;
			$macFunc = $assoc->mac_func;
			$secret = base64_decode( $assoc->secret );
			$expires = $assoc->expires;
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

		O_Dao_Query::get( "O_OpenId_Consumer_Assotiation" )->test( "expires", time(), O_Dao_Query::LT )->delete();
		$assoc = O_Dao_Query::get( "O_OpenId_Consumer_Assotiation" )->test( "handle", $handle )->getOne();
		if ($assoc) {
			$url = $assoc->url;
			$macFunc = $assoc->mac_func;
			$secret = base64_decode( $assoc->secret );
			$expires = $assoc->expires;
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
		O_Dao_Query::get( "O_OpenId_Consumer_Assotiation" )->test( "url", $url )->delete();
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
	public function addDiscoveryInfo( $id, $realId, $server, $version, $expires ){
		new O_OpenId_Consumer_Discovery( $id, $realId, $server, $version, $expires );
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
		O_Dao_Query::get( "O_OpenId_Consumer_Discovery" )->test( "expires", time(), O_Dao_Query::LT )->delete();
		$disc = O_Dao_Query::get( "O_OpenId_Consumer_Discovery" )->test( "disc_id", $id )->getOne();
		if ($disc) {
			$realId = $disc->real_id;
			$server = $disc->server;
			$version = $disc->version;
			$expires = $disc->expires;
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
		O_Dao_Query::get( "O_OpenId_Consumer_Discovery" )->test( "disc_id", $id )->delete();
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
			new O_OpenId_Consumer_Nonce( $nonce );
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
		O_Dao_Query::get( "O_OpenId_Consumer_Nonce" )->test( "created", strtotime( $date ) + 8600000, O_Dao_Query::LT )->delete();
	}
}