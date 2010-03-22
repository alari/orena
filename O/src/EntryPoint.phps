<?php
// We need to require it manually
require 'Utils.phps';
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
 * ./Apps/{APP_NAME}/Conf/Conditions.php -- application conditions
 * ./Apps/{APP_NAME}/Conf/Registry.conf -- registry in app rootkey
 * ./Apps/{APP_NAME}/Conf/Urls.php -- url parser
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
			O( "*start-time", microtime( true ) );

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

			if (O( "*mode" ) == "development") {
				set_error_handler( Array (__CLASS__, "errorException"), E_ALL );
			} elseif (O("*mode") == "testing") {
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
		$suite = O( "_prefix" ) . "_Tests_Suite";
		if(!class_exists($suite, true)) $suite = O( "_prefix" ) . "_Suite";
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
		// Look into applications directories
		$d = opendir( self::$APPS_DIR."" );
		while ($f = readdir( $d )) {
			if ($f == "." || $f == "..")
				continue;

			if(is_file(self::$APPS_DIR."/" . $f . "/Conf/Conditions.php")) {
				include self::$APPS_DIR."/" . $f . "/Conf/Conditions.php";
				$cond = O("*conditions");
				$cond();
				if(O("*mode")) {
					O("_name", $f);
					O( "~process_url", substr( O( "~request_url" ), strlen( O( "~base_url" ) ) ) );
					O_ClassManager::registerPrefix(O("_prefix"), self::$APPS_DIR."/" . $f, O("_ext"));
					return true;
				}
			}
		}
		throw new O_Ex_Critical( "Neither app-selecting config nor app config found." );
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
		$app_name = O( "_name" );

		if (is_file( self::$APPS_DIR."/" . $app_name . "/Conf/Registry.conf" )) {
			O_Registry::parseFile( self::$APPS_DIR."/" . $app_name . "/Conf/Registry.conf", "app" );
		}

		if(is_file(self::$APPS_DIR."/" . $app_name . "/Conf/Urls.php")) {
			include self::$APPS_DIR."/" . $app_name . "/Conf/Urls.php";
			$dispatcher = O("*url_dispatcher");
			$dispatcher();
		}

		// Processing class uses
		$uses = O( "_uses" );
		if (is_array( $uses ))
			foreach ($uses as $class)
				class_exists( $class );
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
		$cmd_name = O( "*command" );
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

		$plugin_name = O_Registry::get( "*plugin" );
		$plugin_name = $plugin_name && $plugin_name != "-" ? "_" . $plugin_name : "";

		if (!O_Registry::get( "app/command_full" )) {
			$cmd_class = O_Registry::get( "_prefix" ) . $plugin_name . "_Cmd_" . $cmd_name;
			$tpl_class = O_Registry::get( "_prefix" ) . $plugin_name . "_Tpl_" . $cmd_name;
		} else {
			$cmd_class = $cmd_name;
		}
		if (!class_exists( $cmd_class, true ) && !class_exists( $tpl_class, true ) && $cmd_name != "Default") {
			$cmd_name = "Default";
			$cmd_class = O_Registry::get( "_prefix" ) . $plugin_name . "_Cmd_" . $cmd_name;
			$tpl_class = O_Registry::get( "_prefix" ) . $plugin_name . "_Tpl_" . $cmd_name;
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