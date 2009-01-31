<?php
class Db_Manager {
	private static $connections = Array ();

	/**
	 * Creates a new connection to database server
	 *
	 * @param array $conf engine, host, database, user, pass, port
	 * @param int $conn_id 1 is default connection
	 * @see PDO::__construct()
	 */
	static public function connect( Array $conf, $conn_id = 1 )
	{
		return self::$connections[ $conn_id ] = new PDO( 
				$conf[ "engine" ] . ":host=" . $conf[ "host" ] . ";port=" . $conf[ "port" ] . ";dbname=" . $conf[ "dbname" ], 
				$conf[ "user" ], $conf[ "password" ] );
	}

	/**
	 * Returns the specified connection with database
	 *
	 * @param int $conn_id
	 * @return PDO or null
	 */
	static public function getConnection( $conn_id = 1 )
	{
		return isset( self::$connections[ $conn_id ] ) ? self::$connections[ $conn_id ] : null;
	}

}