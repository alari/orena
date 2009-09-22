<?php
/**
 * Singleton for javascript framework.
 *
 * @see O_Js_iFramework
 *
 * @author Dmitry Kurinskiy
 */
class O_Js_Middleware {
	/**
	 * Framework object singleton
	 *
	 * @var O_Js_iFramework
	 */
	private static $framework;

	/**
	 * Returns current framework object
	 *
	 * @return O_Js_iFramework
	 */
	static public function getFramework()
	{
		if (!self::$framework) {
			$class = "O_Js_" . O_Registry::get( "app/js/framework" );
			self::$framework = new $class( );
		}
		return self::$framework;
	}
}