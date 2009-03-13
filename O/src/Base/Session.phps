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
 *
 * @copyright Dmitry Kourinski
 *
 * @table o_session
 * @field ses_id varchar(32)
 * @field data text
 * @field started int
 * @field time int
 * @field views int default 0
 * @field user -has one {classnames/user} -inverse session
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
		if (!$id)
			$id = session_id();
		$obj = isset( self::$objs[ $id ] ) ? self::$objs[ $id ] : O_Dao_Query::get( self::getClassName() )->test(
				"ses_id", $id )->getOne();
		if (!$obj) {
			$class = self::getClassName();
			$obj = new $class( );
			$obj->ses_id = $id ? $id : session_id();
			$obj->started = time();
			$obj->time = time();
			$obj->save();
			self::$objs[ $id ] = $obj;
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
	 * Removes logged user from session
	 *
	 * @param string $id
	 */
	static public function delUser( $id = null )
	{
		self::get( $id )->user = null;
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

	/**
	 * Returns classname of session DAO
	 *
	 * @return string
	 */
	static public function getClassName()
	{
		static $ses_class;
		if ($ses_class == null) {
			$ses_class = O_Registry::get( "app/session/class_name" );

			if (!$ses_class)
				$ses_class = __CLASS__;

		}
		return $ses_class;
	}

}

// Set framework session class as sessions handler
$ses_class = O_Http_Session::getClassName();
session_set_save_handler( Array ($ses_class, "open"), Array ($ses_class, "close"), Array ($ses_class, "read"),
		Array ($ses_class, "write"), Array ($ses_class, "destroy"), Array ($ses_class, "gc") );
// Set special session name
session_name( O_Registry::get( "app/session/name" ) );
// Start the session
session_start();