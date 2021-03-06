<?php
/**
 * Handler for HTTP sessions. Stores data in DB.
 *
 * To use it, just use the class and $_SESSION superglobal variable.
 *
 * app/session/name -- name of variable to store sid in
 * app/classnames/session -- subclass of O_Base_Session with special session functionality
 * app/session/user_callback -- method to get user from session active record
 * app/classnames/user -- classname of user class
 * app/classnames/visitor -- classname of visitor singleton
 * app/session/robots = handle -- handle sessions for robots or not (default is not)
 *
 * @author Dmitry Kurinskiy
 *
 * @table o_session
 * @field ses_id varchar(32)
 * @field data text
 * @field started int
 * @field time int
 * @field views int default 0
 * @field user -has one {classnames/user} -inverse session
 * @field user_agent tinytext
 * @index ses_id -unique
 * @index time
 */
class O_Base_Session extends O_Dao_ActiveRecord {

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
		global $O_SESSION_OBJS;
		if(!is_array($O_SESSION_OBJS)) $O_SESSION_OBJS = Array();

		if (!$id) {
			$id = session_id();
		}
		if (!$id) {
			if (!session_start() || !session_id())
				throw new O_Ex_Critical( "Unable to start session" );
			$id = session_id();
		}
		if (!$id) {
			throw new O_Ex_Critical( "Session ID is undefined or inaccessible" );
		}
		$obj = array_key_exists( $id, $O_SESSION_OBJS ) ? $O_SESSION_OBJS[ $id ] : O_Dao_Query::get(
				self::getClassName() )->test( "ses_id", $id )->limit(1)->getOne();
		if (!$obj) {
			$class = self::getClassName();
			$obj = new $class( );
			$obj->ses_id = $id;
			$obj->started = time();
			$obj->time = time();
			$obj->user_agent = $_SERVER['HTTP_USER_AGENT'];
			$obj->save();
			$O_SESSION_OBJS[ $id ] = $obj;
		} elseif(!array_key_exists($id, $O_SESSION_OBJS)) {
			$O_SESSION_OBJS[$id] = $obj;
		}
		return $obj;
	}

	/**
	 * Returns current user object
	 *
	 * @param string $id
	 * @return O_Acl_iUser
	 */
	static public function getUser( $id = null )
	{
		$obj = self::get( $id );
		$callback = O_Registry::get( "app/session/user_callback" );
		return $callback ? $obj->$callback() : null;
	}

	/**
	 * Returns true if user is currently logged, false elsewhere
	 *
	 * @param string $id
	 * @return bool
	 */
	static public function isLogged( $id = null )
	{
		return (bool)(self::get( $id )->user);
	}

	/**
	 * Sets logged user for session
	 *
	 * @param O_Dao_ActiveRecord $user
	 * @param string $id
	 */
	static public function setUser( O_Dao_ActiveRecord $user, $id = null )
	{
		self::get( $id )->user = $user;
	}

	/**
	 * Removes logged user and all stored data from session
	 *
	 * @param string $id
	 */
	static public function delUser( $id = null )
	{
		self::get( $id )->user = null;
		$_SESSION = Array ();
	}

	/**
	 * User callback
	 *
	 * @return O_Acl_iUser
	 */
	public function user()
	{
		return $this->user ? $this->user : call_user_func(
				array (O_Registry::get( "app/classnames/visitor" ), "getInstance") );
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
		self::get()->data = $sess_data;
		self::get()->save();
		return $sess_data;
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
		$d = O_Dao_Query::get( self::getClassName() )->test( "time", time() - $maxlifetime,
				O_Dao_Query::LT )->delete();
		// FIXME: unauthorized users should have theirs own session lifetime
		$d += O_Dao_Query::get( self::getClassName() )->test( "time",
				time() - round( $maxlifetime / 10 ), O_Dao_Query::LT )->test( "user", null )->delete();
		return $d;
	}

	/**
	 * Returns classname of session DAO
	 *
	 * @return string
	 */
	static public function getClassName()
	{
		return O_Registry::get( "app/classnames/session" );
	}

	/**
	 * Registers session handler
	 *
	 */
	static public function registerHandler()
	{
		// Set framework session class as sessions handler
		$ses_class = self::getClassName();

		session_set_save_handler( Array ($ses_class, "open"), Array ($ses_class, "close"),
				Array ($ses_class, "read"), Array ($ses_class, "write"),
				Array ($ses_class, "destroy"), Array ($ses_class, "gc") );
		// Set special session name
		session_name( O_Registry::get( "app/session/name" ) );
		if (O_Registry::get( "app/session/robots" ) != "handle" && O_Base_SearchEngines::isBot())
			return;
			// Start the session
		session_start();
	}

}

$O_SESSION_OBJS = Array();

O_ClassManager::registerClassLoadedCallback( array ("O_Base_Session", "registerHandler"),
		O_Registry::get( "app/classnames/session" ) );
register_shutdown_function(array("O_Base_Session", "close"));