<?php
/**
 * @table o_session
 * @field ses_id varchar(32)
 * @field data text
 * @field started int
 * @field time int
 * @index ses_id -unique
 * @index time
 */
class O_Http_Session extends O_Dao_ActiveRecord {

	static private $objs = Array();

	/**
	 * Returns session active record
	 *
	 * @param string $id
	 * @return O_Http_Session
	 */
	static public function get($id=null)
	{
		if(!$id) $id=session_id();
		$obj = isset(self::$objs[$id]) ? self::$objs[$id] : O_Dao_Query::get(__CLASS__)->test("ses_id", $id)->getOne();
		if(!$obj) {
			$obj = new self;
			$obj->ses_id = $id ? $id : session_id();
			$obj->started = time();
			$obj->time = time();
			$obj->save();
			self::$objs[$id] = $obj;
		}
		return $obj;
	}

	static public function open( $save_path, $session_name )
	{
		return true;
	}

	static public function close()
	{
		self::get()->time = time();
		self::get()->save();
		return true;
	}

	static public function read( $id )
	{
		return self::get($id)->data;
	}

	static public function write( $id, $sess_data )
	{
		return self::get($id)->data = $sess_data;
	}

	static public function destroy( $id )
	{
		return self::get($id)->delete();
	}

	static public function gc( $maxlifetime )
	{
		return O_Dao_Query::get(__CLASS__)->test("time", time()-$maxlifetime, O_Dao_Query::LT)->delete();
	}

}

session_set_save_handler( Array("O_Http_Session", "open"), Array("O_Http_Session", "close"), Array("O_Http_Session", "read"), Array("O_Http_Session", "write"), Array("O_Http_Session", "destroy"), Array("O_Http_Session", "gc") );
session_name(O_Registry::get("app/session/name"));
session_start();