<?php
class O_Db_Manager {
	/**
	 * Array of connections
	 *
	 * @var PDO
	 */
	private static $connections = Array ();

	const CONN_DEFAULT = "default";

	/**
	 * Creates a new connection to database server
	 *
	 * @param array $conf engine, host, database, user, password, port
	 * @param string $conn_id CONN_DEFAULT is default connection
	 * @see PDO::__construct()
	 * @return PDO
	 */
	static protected function connect( Array $conf, $conn_id = self::CONN_DEFAULT )
	{
		try {
			$dsn = $conf[ "engine" ] . ":";
			$user = isset( $conf[ "user" ] ) ? $conf[ "user" ] : "";
			$pass = isset( $conf[ "password" ] ) ? $conf[ "password" ] : "";
			foreach ($conf as $k => $v)
				if ($k != "engine" && $k != "user" && $k != "password")
					$dsn .= $k . '=' . $v . ';';

			return self::$connections[ $conn_id ] = new PDO( $dsn, $user, $pass );
		} catch(PDOException $e) {
			throw new O_Ex_Critical("PDO connection error: ".$e->getMessage());
		}
	}

	/**
	 * Returns the specified connection with database
	 *
	 * @param string $conn_id
	 * @return PDO or null
	 */
	static public function getConnection( $conn_id = self::CONN_DEFAULT )
	{
		if (!isset( self::$connections[ $conn_id ] )) {
			$conf = O_Registry::get( "_db/" . $conn_id );
			if (isset( $conf[ "engine" ] )) {
				self::connect( $conf )->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
				self::$connections[ $conn_id ]->query( "SET character_set_client='utf8'" );
				self::$connections[ $conn_id ]->query( "SET character_set_results='utf8'" );
				self::$connections[ $conn_id ]->query(
						"SET collation_connection='utf8_general_ci'" );
			}
		}
		return isset( self::$connections[ $conn_id ] ) ? self::$connections[ $conn_id ] : null;
	}
}