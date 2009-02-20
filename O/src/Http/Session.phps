<?php
/**
 * Handler for HTTP sessions. Stores data in DB.
 *
 * To use it, just use the class and $_SESSION superglobal variable.
 *
 * @copyright Dmitry Kourinski
 *
 * @todo Add one-to-one relation with logged user
 *
 * @table o_session
 * @field ses_id varchar(32)
 * @field data text
 * @field started int
 * @field time int
 * @field views int default 0
 * @index ses_id -unique
 * @index time
 */
class O_Http_Session extends O_Dao_ActiveRecord {
	
	/**
	 * Cached objects with session ID as array keys
	 *
	 * @var array
	 */
	private static $objs = Array ();

	/**
	 * Returns session active record
	 *
	 * @param string $id If it's null, current session is returned
	 * @return O_Http_Session
	 */
	static public function get( $id = null )
	{
		if (!$id)
			$id = session_id();
		$obj = isset( self::$objs[ $id ] ) ? self::$objs[ $id ] : O_Dao_Query::get( __CLASS__ )->test( "ses_id", $id )->getOne();
		if (!$obj) {
			$obj = new self( );
			$obj->ses_id = $id ? $id : session_id();
			$obj->started = time();
			$obj->time = time();
			$obj->save();
			self::$objs[ $id ] = $obj;
		}
		return $obj;
	}

	/**
	 * Does nothing; needed to set user sessions handling
	 *
	 * @param string $save_path
	 * @param string $session_name
	 * @return true
	 * @access private
	 */
	static public function open( $save_path, $session_name )
	{
		return true;
	}

	/**
	 * Evaluated on script exiting.
	 *
	 * @return bool
	 * @access private
	 */
	static public function close()
	{
		self::get()->time = time();
		self::get()->views += 1;
		self::get()->save();
		return true;
	}

	/**
	 * Returns session serialized data
	 *
	 * @param string $id
	 * @return string
	 * @access private
	 */
	static public function read( $id )
	{
		return self::get( $id )->data;
	}

	/**
	 * Sets serialized data to session.
	 *
	 * @param string $id
	 * @param string $sess_data
	 * @return string
	 * @access private
	 */
	static public function write( $id, $sess_data )
	{
		return self::get( $id )->data = $sess_data;
	}

	/**
	 * Removes one session from database.
	 *
	 * @param string $id
	 * @return bool
	 */
	static public function destroy( $id )
	{
		return self::get( $id )->delete();
	}

	/**
	 * Garbage collector -- removes old sessions.
	 *
	 * @param int $maxlifetime
	 * @return int
	 * @access private
	 */
	static public function gc( $maxlifetime )
	{
		return O_Dao_Query::get( __CLASS__ )->test( "time", time() - $maxlifetime, O_Dao_Query::LT )->delete();
	}

}

// Set framework session class as sessions handler
session_set_save_handler( Array ("O_Http_Session", "open"), Array ("O_Http_Session", "close"), 
		Array ("O_Http_Session", "read"), Array ("O_Http_Session", "write"), Array ("O_Http_Session", "destroy"), 
		Array ("O_Http_Session", "gc") );
// Set special session name
session_name( O_Registry::get( "app/session/name" ) );
// Start the session
session_start();