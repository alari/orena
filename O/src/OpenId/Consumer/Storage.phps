<?php

require_once 'Auth/OpenID/Interface.php';
/**
 *
 */
class O_OpenId_Consumer_Storage extends Auth_OpenID_OpenIDStore {
	/**
	 * Singleton pattern
	 *
	 * @var O_OpenId_Consumer_Storage
	 */
	private static $singleton;

	const TABLE_NONCE = "o_openid_nonce2";
	const TABLE_ASSOC = "o_openid_assoc2";

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
			$nonce->field( "server_url", "VARCHAR(2047) NOT NULL" )->field( "timestamp",
					"INT NOT NULL" )->field( "salt", "CHAR(40) NOT NULL" )->index(
					"server_url(255), timestamp, salt", "unique" )->create( "ENGINE=InnoDB" );
		}

		$assoc = O_Db_Query::get( self::TABLE_ASSOC );
		if (!$assoc->tableExists()) {
			$assoc->field( "server_url", "BLOB NOT NULL" )->field( "handle",
					"VARCHAR(255) NOT NULL" )->field( "secret", "BLOB NOT NULL" )->field(
					"issued", "INT NOT NULL" )->field( "lifetime", "INT NOT NULL" )->field(
					"assoc_type", "VARCHAR(64) NOT NULL" )->index( "server_url(255), handle",
					"PRIMARY KEY" )->create( "ENGINE=InnoDB" );
		}
	}

	/**
	 * This method puts an Association object into storage,
	 * retrievable by server URL and handle.
	 *
	 * @param string $server_url The URL of the identity server that
	 * this association is with. Because of the way the server portion
	 * of the library uses this interface, don't assume there are any
	 * limitations on the character set of the input string. In
	 * particular, expect to see unescaped non-url-safe characters in
	 * the server_url field.
	 *
	 * @param Auth_OpenID_Association $association The Association to store.
	 */
	function storeAssociation( $server_url, $association )
	{
		O_Db_Query::get( self::TABLE_ASSOC )->field( "server_url", $server_url )->field( "handle",
				$association->handle )->field( "secret", $association->secret )->field( "issued",
				$association->issued )->field( "lifetime", $association->lifetime )->field(
				"assoc_type", $association->assoc_type )->insert();

	}

	/*
     * Remove expired nonces from the store.
     *
     * Discards any nonce from storage that is old enough that its
     * timestamp would not pass useNonce().
     *
     * This method is not called in the normal operation of the
     * library.  It provides a way for store admins to keep their
     * storage from filling up with expired data.
     *
     * @return the number of nonces expired
     */
	function cleanupNonces()
	{
		global $Auth_OpenID_SKEW;

		return O_Db_Query::get( self::TABLE_NONCE )->test( "timestamp", time() - $Auth_OpenID_SKEW,
				O_Db_Query::LT )->delete();
	}

	/*
     * Remove expired associations from the store.
     *
     * This method is not called in the normal operation of the
     * library.  It provides a way for store admins to keep their
     * storage from filling up with expired data.
     *
     * @return the number of associations expired.
     */
	function cleanupAssociations()
	{
		return O_Db_Query::get( self::TABLE_ASSOC )->where( "issued + lifetime < UNIX_TIMESTAMP()" )->delete();
	}

	/*
     * Shortcut for cleanupNonces(), cleanupAssociations().
     *
     * This method is not called in the normal operation of the
     * library.  It provides a way for store admins to keep their
     * storage from filling up with expired data.
     */
	function cleanup()
	{
		return array ($this->cleanupNonces(), $this->cleanupAssociations());
	}

	/**
	 * Report whether this storage supports cleanup
	 */
	function supportsCleanup()
	{
		return true;
	}

	/**
	 * This method returns an Association object from storage that
	 * matches the server URL and, if specified, handle. It returns
	 * null if no such association is found or if the matching
	 * association is expired.
	 *
	 * If no handle is specified, the store may return any association
	 * which matches the server URL. If multiple associations are
	 * valid, the recommended return value for this method is the one
	 * most recently issued.
	 *
	 * This method is allowed (and encouraged) to garbage collect
	 * expired associations when found. This method must not return
	 * expired associations.
	 *
	 * @param string $server_url The URL of the identity server to get
	 * the association for. Because of the way the server portion of
	 * the library uses this interface, don't assume there are any
	 * limitations on the character set of the input string.  In
	 * particular, expect to see unescaped non-url-safe characters in
	 * the server_url field.
	 *
	 * @param mixed $handle This optional parameter is the handle of
	 * the specific association to get. If no specific handle is
	 * provided, any valid association matching the server URL is
	 * returned.
	 *
	 * @return Association The Association for the given identity
	 * server.
	 */
	function getAssociation( $server_url, $handle = null )
	{
		$assocs = Array ();
		$q = O_Db_Query::get( self::TABLE_ASSOC )->test( "server_url", $server_url );
		if ($handle !== null) {
			$q->test( "handle", $handle );
		}
		try {
			$assocs = $q->select()->fetchAll();
		}
		catch (PDOException $e) {
			$assocs = null;
		}

		if (!$assocs || (count( $assocs ) == 0)) {
			return null;
		} else {
			$associations = array ();

			foreach ($assocs as $assoc_row) {
				$assoc = new Auth_OpenID_Association( $assoc_row[ 'handle' ],
						$assoc_row[ 'secret' ], $assoc_row[ 'issued' ], $assoc_row[ 'lifetime' ],
						$assoc_row[ 'assoc_type' ] );

				if ($assoc->getExpiresIn() == 0) {
					$this->removeAssociation( $server_url, $assoc->handle );
				} else {
					$associations[] = array ($assoc->issued, $assoc);
				}
			}

			if ($associations) {
				$issued = array ();
				$assocs = array ();
				foreach ($associations as $key => $assoc) {
					$issued[ $key ] = $assoc[ 0 ];
					$assocs[ $key ] = $assoc[ 1 ];
				}

				array_multisort( $issued, SORT_DESC, $assocs, SORT_DESC, $associations );

				// return the most recently issued one.
				list ($issued, $assoc) = $associations[ 0 ];
				return $assoc;
			} else {
				return null;
			}
		}
	}

	/**
	 * This method removes the matching association if it's found, and
	 * returns whether the association was removed or not.
	 *
	 * @param string $server_url The URL of the identity server the
	 * association to remove belongs to. Because of the way the server
	 * portion of the library uses this interface, don't assume there
	 * are any limitations on the character set of the input
	 * string. In particular, expect to see unescaped non-url-safe
	 * characters in the server_url field.
	 *
	 * @param string $handle This is the handle of the association to
	 * remove. If there isn't an association found that matches both
	 * the given URL and handle, then there was no matching handle
	 * found.
	 *
	 * @return mixed Returns whether or not the given association existed.
	 */
	function removeAssociation( $server_url, $handle )
	{
		return O_Db_Query::get( self::TABLE_ASSOC )->test( "server_url", $server_url )->test(
				"handle", $handle )->delete() ? true : false;
	}

	/**
	 * Called when using a nonce.
	 *
	 * This method should return C{True} if the nonce has not been
	 * used before, and store it for a while to make sure nobody
	 * tries to use the same value again.  If the nonce has already
	 * been used, return C{False}.
	 *
	 * Change: In earlier versions, round-trip nonces were used and a
	 * nonce was only valid if it had been previously stored with
	 * storeNonce.  Version 2.0 uses one-way nonces, requiring a
	 * different implementation here that does not depend on a
	 * storeNonce call.  (storeNonce is no longer part of the
	 * interface.
	 *
	 * @param string $nonce The nonce to use.
	 *
	 * @return bool Whether or not the nonce was valid.
	 */
	function useNonce( $server_url, $timestamp, $salt )
	{
		global $Auth_OpenID_SKEW;

		if (abs( $timestamp - time() ) > $Auth_OpenID_SKEW) {
			return false;
		}
echo O_Db_Query::get( self::TABLE_NONCE )->field( "server_url", $server_url )->field(
				"timestamp", $timestamp )->field( "salt", $salt )->prepareInsert(); return false;
		return O_Db_Query::get( self::TABLE_NONCE )->field( "server_url", $server_url )->field(
				"timestamp", $timestamp )->field( "salt", $salt )->insert() ? true : false;
	}

	/**
	 * Removes all entries from the store; implementation is optional.
	 */
	function reset()
	{
		O_Db_Query::get( self::TABLE_ASSOC )->delete();
		O_Db_Query::get( self::TABLE_NONCE )->delete();
	}

}