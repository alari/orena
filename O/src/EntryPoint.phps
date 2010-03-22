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

			// Sets main locale
			setlocale( LC_ALL, O("_locale"));

			if (O( "*mode" ) == "development") {
				set_error_handler( Array (__CLASS__, "errorException"), E_ALL );
			} elseif (O("*mode") == "testing") {
				return self::runTests();
			}

			// Prepare and echo response
			return self::makeResponse();
		}
		catch (Exception $e) {
			$errTpl = O( "_err_tpl" );
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
		O( "~request_url", $url );

		// Saving HTTP_HOST value
		O( "~http_host", $_SERVER[ 'HTTP_HOST' ] );
		O( "~host", $_SERVER[ 'HTTP_HOST' ] );
		// Request method
		O( "~request_method", $_SERVER[ 'REQUEST_METHOD' ] );
		O( "~method", $_SERVER["REQUEST_METHOD"] );

		// Adding request params to env/request registry
		O( "~params", array_merge( $_POST, $_GET ) );

		// Base URL
		O( "~base_url", "/" );
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
	 * Makes and echoes response or throws PageNotFound error
	 */
	static public function makeResponse() {
		$cmd_class = O("*command_class");
		if($cmd_class && self::tryRunCmdTpl($cmd_class)) return;

		if(self::tryCmdTpl( self::getCmdName() )) return;
		if(self::tryCmdTpl( O("_default_command") )) return;

		throw new O_Ex_PageNotFound("Page Not Found", 404);
 	}

 	/**
 	 * Returns command name according to configs and/or url
 	 */
 	static private function getCmdName() {
 		$cmd = O("*command");
		if(!$cmd) {
			$url = O("~process_url");
			$ext = O("_pages_extension");
			if( $ext && substr($url, -strlen($ext)) == $ext) {
				$url = substr( $url, 0, -strlen($ext) );
			}
			$url = trim($url, "/");
			if(!$url) {
				$cmd = O("_default_command");
			} else {
				$cmd = str_replace(" ", "", ucwords(strtr($url, "-", " ")));
				$cmd = strtr($cmd, "./ ", "___");
			}
		}
		return $cmd;
 	}

 	/**
 	 * Tries to run command or template by its full classname
 	 * @param string $class
 	 */
 	static private function tryRunCmdTpl($class) {
 		if(!class_exists($class, true)) return false;
 		$o = new $class;
 		if($o instanceof O_Command) {
 			$o->run();
 			return true;
 		} elseif($o instanceof O_Html_Template) {
 			$o->display();
 			return true;
 		}
 		return false;
 	}

 	/**
 	 * Builds command and template classes, tries to run them
 	 * @param string $name
 	 */
 	static private function tryCmdTpl($name) {
 		$plugin = $plugin && $plugin != "-" ? "_".$plugin : "";
 		if(self::tryRunCmdTpl(O("_prefix").$plugin."_Cmd_".$name)) return true;
 		if(self::tryRunCmdTpl(O("_prefix").$plugin."_Tpl_".$name)) return true;
 		return false;
 	}
}