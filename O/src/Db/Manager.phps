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
	 */
	static public function connect( Array $conf, $conn_id = self::CONN_DEFAULT )
	{
		$dsn = $conf[ "engine" ] . ":";
		$user = isset( $conf[ "user" ] ) ? $conf[ "user" ] : "";
		$pass = isset( $conf[ "password" ] ) ? $conf[ "password" ] : "";
		foreach ($conf as $k => $v)
			if ($k != "engine" && $k != "user" && $k != "password")
				$dsn .= $k . '=' . $v . ';';

		return self::$connections[ $conn_id ] = new PDO( $dsn, $user, $pass );
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
			$conf = O_Registry::get( "app/db/" . $conn_id );
			if (isset( $conf[ "engine" ] ))
				self::connect( $conf );
		}
		return isset( self::$connections[ $conn_id ] ) ? self::$connections[ $conn_id ] : null;
	}
}