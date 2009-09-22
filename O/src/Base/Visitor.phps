<?php
/**
 * Visitor pseudo-user.
 *
 * Registry:
 * "app/classnames/visitor" - visitor classname
 *
 * @author Dmitry Kurinskiy
 */
class O_Base_Visitor {
	protected static $singleton;

	/**
	 * Required by singleton pattern
	 *
	 */
	protected function __construct()
	{
	}

	/**
	 * Returns instance of class
	 *
	 * @return O_Acl_Visitor
	 */
	static public function getInstance()
	{
		if (!self::$singleton) {
			$class = O_Registry::get( "app/classnames/visitor" );
			self::$singleton = new $class( );
		}
		return self::$singleton;
	}

}