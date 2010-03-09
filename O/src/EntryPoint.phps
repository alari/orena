<?php
// We need to require it manually
require 'Profiler.phps';
require 'ClassManager.phps';
/**
 * Processes request -- from url and host parsing to response echoing.
 *
 * To build your project based on this, type in your entry-point file:
 * <code>
 * require_once "O/src/EntryPoint.phps";
 * O_EntryPoint::processRequest();
 * </code>
 *
 * This depends on several configuration files:
 * ./Apps/Orena.fw.conf -- framework registry configuration (to be used instead of default one, located in ./O/src/)
 * ./Apps/Conditions.conf -- application selection
 * ./Apps/{APP_NAME}/Conf/Conditions.conf -- application conditions
 * ./Apps/{APP_NAME}/Conf/Registry.conf -- registry in app rootkey
 * ./Apps/{APP_NAME}/Conf/Urls.conf -- url parser
 *
 * @author Dmitry Kurinskiy
 */
class O_EntryPoint {

	static private $APPS_DIR;

	/**
	 * Processes request and echoes response.
	 *
	 * Prepares environment, parses framework configuration file, then
	 * parses application selection config, selects current application,
	 * parses its config, finds command or template to process it,
	 * processes it and echoes response.
	 *
	 * @return bool True on success
	 */
	static public function processRequest()
	{
		try {
			O_Registry::set( "start-time", microtime( true ) );

			self::$APPS_DIR = O_DOC_ROOT."/Apps";

			// Preparing environment
			self::prepareEnvironment();

			// At first we parse framework registry config
			self::processFwConfig();

			// Then we handle applications to select what to run
			self::selectApp();

			// Parsing application registry
			self::processAppConfig();

			// TODO: get locale from registry
			setlocale( LC_ALL, "ru_RU.UTF8" );

			if (O_Registry::get( "app/mode" ) == "development") {
				set_error_handler( Array (__CLASS__, "errorException"), E_ALL );
			} elseif (O_Registry::get("app/mode") == "testing") {
				return self::runTests();
			}

			// Prepare and echo response
			return self::makeResponse();
		}
		catch (Exception $e) {
			$errTpl = O_Registry::get( "app/err_tpl" );
			$tpl = new $errTpl( $e );
			if ($tpl instanceof O_Html_Template) {
				$tpl->display();
				return true;
			}
		}
	}

	static public function runTests() {
		require "PHPUnit/Framework.php";
		$suite = O_Registry::get( "app/class_prefix" ) . "_Tests_Suite";
		if(!class_exists($suite, true)) $suite = O_Registry::get( "app/class_prefix" ) . "_Suite";
		if(!class_exists($suite, true)) return "Cannot find test suite.";
		Header("Content-type: text/plain; charset=utf-8");
		// TODO: drop all tables
		PHPUnit_TextUI_TestRunner::run( new $suite );
	}

	/**
	 * Internal errors handler
	 *
	 * @param int $code
	 * @param string $msg
	 */
	static public function errorException( $code, $msg )
	{
		throw new O_Ex_CodeError( $msg, $code );
	}

	/**
	 * Prepares registry environment for future use.
	 *
	 * Sets current URL (without query string) to "env/request_url"
	 * Sets current HTTP_HOST to "env/http_host"
	 * Merges GET and POST parameters to "env/params"
	 * Sets "app" inheritance from "fw"
	 */
	static public function prepareEnvironment()
	{
		// Saving url without query string to process it correctly
		$url = $_SERVER[ 'REQUEST_URI' ];
		if (strpos( $url, "?" ))
			$url = substr( $url, 0, strpos( $url, "?" ) );
		O_Registry::set( "env/request_url", $url );

		// Saving HTTP_HOST value
		O_Registry::set( "env/http_host", $_SERVER[ 'HTTP_HOST' ] );
		// Request method
		O_Registry::set( "env/request_method", $_SERVER[ 'REQUEST_METHOD' ] );

		// Setting registry inheritance
		O_Registry::setInheritance( "fw", "app" );

		// Adding request params to env/request registry
		O_Registry::set( "env/params", array_merge( $_POST, $_GET ) );

		// Base URL
		O_Registry::set( "env/base_url", "/" );
	}

	/**
	 * Parses and processes application selecting according with current environment.
	 *
	 * Uses configuration file allocated in "./Apps/Conditions.conf"
	 * Per-app conditions are in "./Apps/{APP_NAME}/Conf/Conditions.conf"
	 * Sets "env/base_url" for application prefix.
	 * Sets "env/process_url" for future use inside application.
	 * Sets "app/name", "app/class_prefix", "app/mode" registry keys.
	 *
	 * @throws O_Ex_Critical
	 */
	static public function selectApp()
	{
		// Find application in central applications conditions
		if (is_file( self::$APPS_DIR."/Conditions.conf" )) {
			$configs = O_Registry::parseFile( self::$APPS_DIR."/Conditions.conf" );
			foreach ($configs as $app => $cond) {
				if (self::processConditions( $cond, $app )) {
					return true;
				}
			}
		}
		// Look into applications directories
		$d = opendir( self::$APPS_DIR."" );
		while ($f = readdir( $d )) {
			if ($f == "." || $f == "..")
				continue;
			if (!is_dir( self::$APPS_DIR."/" . $f ) || !is_file( self::$APPS_DIR."/" . $f . "/Conf/Conditions.conf" ))
				continue;
			$cond = O_Registry::parseFile( self::$APPS_DIR."/" . $f . "/Conf/Conditions.conf" );
			if (self::processConditions( $cond, $f )) {
				return true;
			}
		}
		throw new O_Ex_Critical( "Neither app-selecting config nor app config found." );
	}

	/**
	 * Processes application conditions array as mode=>conf
	 *
	 * @param array $cond
	 * @param string $app_name
	 * @return bool
	 * @throws O_Ex_Config
	 */
	static protected function processConditions( Array $cond, $app_name )
	{
		$app_prefix = isset( $cond[ "prefix" ] ) ? $cond[ "prefix" ] : null;
		$app_ext = isset( $cond[ "ext" ] ) ? $cond[ "ext" ] : null;
		if (!$app_ext)
			$app_ext = O_ClassManager::DEFAULT_EXTENSION;

		if (!$app_prefix || !$app_name)
			throw new O_Ex_Config( "Application without name or class prefix cannot be processed." );

		foreach ($cond[ "conditions" ] as $mode => $c) {
			if (self::processCondRules( $c )) {
				O_ClassManager::registerPrefix( $app_prefix, self::$APPS_DIR."/" . $app_name, $app_ext );
				O_Registry::set( "app/class_prefix", $app_prefix );
				O_Registry::set( "app/name", $app_name );
				O_Registry::set( "app/mode", $mode );

				if (isset( $c[ "registry" ] ) && is_array( $c[ "registry" ] )) {
					foreach ($c[ "registry" ] as $rootkey => $values) {
						if (is_array( $values )) {
							O_Registry::mixIn( $values, $rootkey );
						} else {
							O_Registry::set( $rootkey, $values );
						}
					}
				}

				O_Registry::set( "env/process_url", substr( O_Registry::get( "env/request_url" ), strlen( O_Registry::get( "env/base_url" ) ) ) );

				return true;
			}
		}
		return false;
	}

	/**
	 * Processes app-selecting condition rules
	 *
	 * @param array $cond
	 * #return bool
	 */
	static private function processCondRules( Array $cond )
	{
		if ($cond[ "pattern" ] == "any")
			return true;

		foreach ($cond[ "pattern" ] as $name => $part) {
			switch ($name) {
				// Checks if url starts with "base" attribute or matches "pattern"
				case "url" :
					$d = 0;
					if (isset( $part[ "base" ] ) && $part[ "base" ]) {
						if (strpos( O_Registry::get( "env/request_url" ), $part[ "base" ] ) === 0) {
							O_Registry::set( "env/base_url", $part[ "base" ] );
							$d = 1;
						} else
							return false;
					}
					if (isset( $part[ "pattern" ] ) && $part[ "pattern" ]) {
						$pattern = $part[ "pattern" ];
						if (preg_match( "#^$pattern$#i", O_Registry::get( "env/request_url" ) ))
							continue;
					}
					if ($d)
						continue;
					return false;
				break;
				// Checks if hostname matches pattern
				case "host" :
					if ($part && preg_match( "#^$part$#i", O_Registry::get( "env/http_host" ) ))
						continue;
					return false;
				break;
				default :
					throw new O_Ex_Config( "Wrong node in app-selection condition: " . $name );
			}
		}
		return true;
	}

	/**
	 * Processes current application configs.
	 *
	 * Gets application name from registry key "app/name"
	 * Parses config allocated in self::$APPS_DIR."/{APP_NAME}/Conf/Registry.conf"
	 * and self::$APPS_DIR."/{APP_NAME}/Conf/Urls.conf"
	 *
	 * @throws O_Ex_Critical
	 */
	static private function processAppConfig()
	{
		$app_name = O_Registry::get( "app/name" );

		if (is_file( self::$APPS_DIR."/" . $app_name . "/Conf/Registry.conf" )) {
			O_Registry::parseFile( self::$APPS_DIR."/" . $app_name . "/Conf/Registry.conf", "app" );
		}

		if (!is_file( self::$APPS_DIR."/" . $app_name . "/Conf/Urls.conf" ))
			return false;

		$conf = O_Registry::parseFile( self::$APPS_DIR."/" . $app_name . "/Conf/Urls.conf" );

		foreach ($conf as $key => $params) {
			self::processUrlsConfPart( $key, $params );
		}

		// Processing class uses
		$uses = O_Registry::get( "app/uses" );
		if (is_array( $uses ))
			foreach ($uses as $class)
				class_exists( $class );
	}

	/**
	 * Processes part of Urls.conf configuration file
	 *
	 * @param string $key
	 * @param mixed $params
	 * @param array $pockets
	 */
	static private function processUrlsConfPart( $key, $params, $pockets = Array() )
	{
		$subkey = "";
		if (strpos( $key, " " )) {
			list ($key, $subkey) = explode( " ", $key, 2 );
			$subkey = trim( $subkey );
		}
		switch ($key) {
			// Process registry in "app" rootkey
			case "registry" :
				if (is_array( $params )) {
					$v = null;
					if (isset( $params[ "pocket" ] )) {
						$v = isset( $pockets[ $params[ "pocket" ] ] ) ? $pockets[ $params[ "pocket" ] ] : null;
					}
					if (isset( $params[ "call" ] ) && is_callable( $params[ "call" ] )) {
						$v = call_user_func( $params[ "call" ], $v );
					} elseif (isset( $params[ "class" ] ) && class_exists( $params[ "class" ] )) {
						$v = O_Dao_ActiveRecord::getById( $v, $params[ "class" ] );
					}
				} else
					$v = $params;
				O_Registry::set( "app/" . $subkey, $v );
			break;
			// Condition based on mode name and plugin name
			case "if" :
				list ($what, $to_what) = explode( "=", $subkey, 2 );
				$what = trim( $what );
				$to_what = trim( $to_what );
				if ($what == "mode" && O_Registry::get( "app/mode" ) != $to_what)
					break;
				if ($what == "plugin" && O_Registry::get( "app/plugin_name" ) != $to_what)
					break;
				foreach ($params as $k => $v) {
					self::processUrlsConfPart( $k, $v );
				}

			break;
			// Parses hostname with pattern, processes child nodes if matches
			case "host" :
				if (preg_match( "#^$subkey$#i", O_Registry::get( "env/http_host" ), $pockets )) {
					foreach ($params as $k => $v)
						self::processUrlsConfPart( $k, $v, $pockets );
				}
			break;
			// Parses URL with pattern, processes child nodes if matches
			case "url" :
				$url = O_Registry::get( "env/process_url" );
				if (preg_match( "#^$subkey$#i", $url, $pockets )) {
					// Set command for URL, if available
					foreach ($params as $k => $v)
						self::processUrlsConfPart( $k, $v, $pockets );
				}
			break;
			// Sets "app/command_name" registry key, continues processing
			case "command" :
				// TODO: add command type and so on processing
				if (!O_Registry::get( "app/command_name" ))
					O_Registry::set( "app/command_name", $params );
			break;
			// Set plugin into "app/plugin_name" registry
			case "plugin" :
				O_Registry::set( "app/plugin_name", $params );
			break;
			default :
				throw new O_Ex_Config( "Unknown key in urls configuration file." );
		}
	}

	/**
	 * Parses framework config, puts it into "fw" registry rootkey.
	 *
	 * @throws O_Ex_Critical
	 */
	static public function processFwConfig()
	{
		$src = is_file( self::$APPS_DIR."/Orena.fw.conf" ) ? self::$APPS_DIR."/Orena.fw.conf" : __DIR__."/Orena.fw.conf";
		if (!is_file( $src ))
			throw new O_Ex_Critical( "Cannot find framework configuration file." );
		O_Registry::parseFile( $src, "fw" );
	}

	/**
	 * According with current application settings, processes command or template and echoes response
	 *
	 * @return bool True on success, false on 404 error (will be also echoed)
	 */
	static public function makeResponse()
	{
		// Create O_Command and process it
		$cmd_name = O_Registry::get( "app/command_name" );
		if (!$cmd_name) {
			$url = O_Registry::get( "env/process_url" );
			// Remove extension
			if (O_Registry::get( "app/pages_extension" )) {
				$ext = O_Registry::get( "app/pages_extension" );
				if (strlen( $url ) > strlen( $ext ) && substr( $url, -strlen( $ext ) ) == $ext) {
					$url = substr( $url, 0, -strlen( $ext ) );
				}
			}
			// Remove slashes
			$url = trim( $url, "/" );
			if (!$url) {
				$cmd_name = "Default";
			} else {
				$cmd_name = str_replace( " ", "", ucwords( str_replace( "-", " ", $url ) ) );
				$cmd_name = str_replace( array (".", "/"), array (" ", " "), $cmd_name );
				$cmd_name = str_replace( " ", "_", ucwords( $cmd_name ) );
			}
		}

		$plugin_name = O_Registry::get( "app/plugin_name" );
		$plugin_name = $plugin_name && $plugin_name != "-" ? "_" . $plugin_name : "";

		if (!O_Registry::get( "app/command_full" )) {
			$cmd_class = O_Registry::get( "app/class_prefix" ) . $plugin_name . "_Cmd_" . $cmd_name;
			$tpl_class = O_Registry::get( "app/class_prefix" ) . $plugin_name . "_Tpl_" . $cmd_name;
		} else {
			$cmd_class = $cmd_name;
		}
		if (!class_exists( $cmd_class, true ) && !class_exists( $tpl_class, true ) && $cmd_name != "Default") {
			$cmd_name = "Default";
			$cmd_class = O_Registry::get( "app/class_prefix" ) . $plugin_name . "_Cmd_" . $cmd_name;
			$tpl_class = O_Registry::get( "app/class_prefix" ) . $plugin_name . "_Tpl_" . $cmd_name;
		}

		if (class_exists( $cmd_class, true )) {
			$cmd = new $cmd_class( );
			if ($cmd instanceof O_Command) {
				/* @var $cmd O_Command */
				$cmd->run();
				return true;
			}
		}

		// Else create O_Html_Template
		if (class_exists( $tpl_class, true )) {
			$tpl = new $tpl_class( );
			if ($tpl instanceof O_Html_Template) {
				$tpl->display();
				return true;
			}
		}
		throw new O_Ex_PageNotFound( "Page Not Found", 404 );
	}
}