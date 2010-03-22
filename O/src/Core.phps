<?php
namespace O {

	use O\Conf;

	class Core {
		const VAR_CONTEXT = "_context";
		const VAR_ENV = "_env";
		const VAR_APP = "_app";
		const VAR_FW = "_fw";

		private static $_app = Array ();
		private static $_context = Array ();
		private static $_fw = Array ();
		private static $_env = Array ();

		const CONFIG_FILE_FW = "Orena.fw.conf";
		const CONFIG_FILE_APP = "Conf/Registry.conf";
		const CONFIG_FILE_APP_FW = "Conf/Orena.fw.conf";

		private static $APPS_DIR;
		private static $APP_NAME;

		/**
		 * Returns variable by its simple name
		 *
		 * @param string $name (*cont ~env `fw _app)
		 * @return mixed
		 */
		static public function get( $name )
		{
			$varType = self::getVarType( $name );
			if (!$varType)
				return $name;
			$name = substr( $name, 1 );
			$res = Utils::getFromArray( $name, self::$$varType );
			// Return framework value for app request
			if ($res === null && $varType == self::VAR_APP) {
				return Utils::getFromArray( $name, self::$_fw );
			}
			return $res;
		}

		/**
		 * Sets (or adds) variable by its simple name
		 *
		 * @param string $name (*cont ~env `fw _app)
		 * @param mixed $value
		 * @param bool $add
		 */
		static public function set( $name, $value, $add = false )
		{
			$varType = self::getVarType( $name );
			if (!$varType)
				return null;
			if ($varType != self::VAR_CONTEXT) {
				self::log( "You should set only context variables from scripts. (Setting '$name')", LOG_NOTICE );
			}
			$name = substr( $name, 1 );
			Utils::setIntoArray( $name, self::$$varType, $value, $add );
		}

		/**
		 * Returns variable type
		 *
		 * @param string $name (*cont ~env `fw _app)
		 * @return const
		 */
		static public function getVarType( $name )
		{
			if (strlen( $name ) < 3)
				throw new O_Ex_Critical( "Var name '$name' is too short." );
			if ($name[ 0 ] == '*') {
				return self::VAR_CONTEXT;
			} elseif ($name[ 0 ] == '~') {
				return self::VAR_ENV;
			} elseif ($name[ 0 ] == '`') {
				return self::VAR_FW;
			} elseif ($name[ 0 ] == '_') {
				return self::VAR_APP;
			}
			return null;
		}

		/**
		 * Initiates app processor
		 */
		static public function init()
		{
			self::$_env["start_time"] = microtime(true);
			try {
				self::$APPS_DIR = O_DOC_ROOT . "/Apps/";
				self::initEnv();
				self::initFw();
			}
			catch (Exception $e) {
				self::log( $e->getMessage() . " / in " . $e->getFile() . ":" . $e->getLine(), LOG_EMERG );
			}
		}

		/**
		 * Processes application, shows result
		 */
		static public function process()
		{
			try {
				if (!self::$APPS_DIR)
					self::init();
				self::selectApp();

				// TODO: get locale from registry
				setlocale( LC_ALL, self::get("_locale") );

				if (self::get( "_mode" ) == "development") {
					set_error_handler( Array (__CLASS__, "errorException"), E_ALL );
				}

				self::processAppUrls();

				// Prepare and echo response
				return self::makeResponse();
			}
			catch (Exception $e) {
				$errTpl = self::get( "_err_tpl" );
				$tpl = new $errTpl( $e );
				if ($tpl instanceof O_Html_Template) {
					$tpl->display();
					return true;
				}
			}
		}

		/**
		 * Processes application urls configuration file
		 */
		static public function processAppUrls()
		{
			if(!is_file(self::$APPS_DIR."/".self::$APP_NAME."/Conf/Urls.phps")) return false;
			include self::$APPS_DIR."/".self::$APP_NAME."/Conf/Urls.phps";
			self::$_context["url_dispatcher"]();
		}

		/**
		 * Throws exception for every php errer
		 * @param int $code
		 * @param string $msg
		 */
		static public function errorException( $code, $msg )
		{
			self::log("Code error $code: $msg", LOG_WARNING);
			throw new O_Ex_CodeError( $msg, $code );
		}

		/**
		 * Makes responce by registry
		 */
		static public function makeResponce()
		{
			// Create O_Command and process it
			$cmd_name = self::get( "*command" );
			if (!$cmd_name) {
				$url = self::get( "~process_url" );
				// Remove extension
				if (self::get( "_pages_extension" )) {
					$ext = self::get( "_pages_extension" );
					if (strlen( $url ) > strlen( $ext ) && substr( $url, -strlen( $ext ) ) == $ext) {
						$url = substr( $url, 0, -strlen( $ext ) );
					}
				}
				// Remove slashes
				$url = trim( $url, "/" );
				if (!$url) {
					$cmd_name = self::get("_default_command");
				} else {
					$cmd_name = str_replace( " ", "", ucwords( str_replace( "-", " ", $url ) ) );
					$cmd_name = str_replace( array (".", "/"), array (" ", " "), $cmd_name );
					$cmd_name = str_replace( " ", "_", ucwords( $cmd_name ) );
				}
			}

			$plugin = self::get( "*plugin" );
			$plugin = $plugin && $plugin != "-" ? "_" . $plugin : "";

			if (!self::get( "*command_full" )) {
				$cmd_class = self::get( "_class_prefix" ) . $plugin . "_Cmd_" . $cmd_name;
				$tpl_class = self::get( "_class_prefix" ) . $plugin . "_Tpl_" . $cmd_name;
			} else {
				$cmd_class = $cmd_name;
			}
			if (!class_exists( $cmd_class, true ) && !class_exists( $tpl_class, true ) && $cmd_name != self::get("_default_command")) {
				$cmd_name = self::get("_default_command");
				$cmd_class = self::get( "_class_prefix" ) . $plugin . "_Cmd_" . $cmd_name;
				$tpl_class = self::get( "_class_prefix" ) . $plugin . "_Tpl_" . $cmd_name;
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

		/**
		 * Initiates basic values of environment vars
		 */
		static private function initEnv()
		{
			// Saving url without query string to process it correctly
			$url = $_SERVER[ 'REQUEST_URI' ];
			if (strpos( $url, "?" ))
				$url = substr( $url, 0, strpos( $url, '?' ) );
			self::$_env[ "request_url" ] = $url;

			// Saving HTTP_HOST value
			self::$_env[ "http_host" ] = $_SERVER[ 'HTTP_HOST' ];
			// Request method
			self::$_env[ "request_method" ] = $_SERVER[ 'REQUEST_METHOD' ];

			// Base URL
			self::$_env[ "base_url" ] = '/';
		}

		/**
		 * Loads framework config from file
		 */
		static private function initFw()
		{
			if (is_file( self::$APPS_DIR . "/" . self::CONFIG_FILE_FW )) {
				$src = self::$APPS_DIR . "/" . self::CONFIG_FILE_FW;
			} elseif (is_file( __DIR__ . "/" . self::CONFIG_FILE_FW )) {
				$src = __DIR__ . "/" . self::CONFIG_FILE_FW;
			} else {
				throw new O_Ex_Critical( "Cannot find framework configuration file." );
			}
			Conf\Parser::parseConfFile( $src, self::$_fw );
		}

		/**
		 * Loads and interp. app config
		 */
		static private function initApp()
		{
			// Try to find app or die
			if (!self::$APP_NAME)
				self::selectApp();
			if (!self::$APP_NAME)
				throw new O_Ex_Critical( "Cannot select valid application." );

			// Parse app own configs
			if (is_file( self::$APPS_DIR . "/" . self::$APP_NAME . "/" . self::CONFIG_FILE_APP )) {
				Conf\Parser::parseConfFile( self::$APPS_DIR . "/" . self::$APP_NAME . "/" . self::CONFIG_FILE_APP, self::$_app );
			}
			// Mix in fw conf
			if (is_file( self::$APPS_DIR . "/" . self::$APP_NAME . "/" . self::CONFIG_FILE_APP_FW )) {
				Conf\Parser::parseConfFile( self::$APPS_DIR . "/" . self::$APP_NAME . "/" . self::CONFIG_FILE_APP_FW, self::$_fw );
			}

			// Find application prefix
			$app_prefix = isset( self::$_app[ "prefix" ] ) ? self::$_app[ "prefix" ] : null;
			if (!$app_prefix)
				throw new O_Ex_Config( "Application without class prefix cannot be processed." );

			// Get application extension
			$app_ext = isset( self::$_app[ "ext" ] ) ? self::$_app[ "ext" ] : null;
			if (!$app_ext)
				$app_ext = ClassManager::DEFAULT_EXTENSION;

			// Register application classes
			ClassManager::registerPrefix( $app_prefix, self::$APPS_DIR . "/" . self::$APP_NAME, $app_ext );
			return true;
		}

		/**
		 * Selects current application, prepares basic app conf:
		 * mode
		 * self::$APP_NAME
		 * prefix
		 * ext
		 * ~base_url
		 */
		static public function selectApp()
		{
			if (self::$APP_NAME)
				return true;
				// Find application in central applications conditions
			if (is_file( self::$APPS_DIR . "/Conditions.conf" )) {
				$configs = Conf\Parser::parseConfFile( self::$APPS_DIR . "/Conditions.conf" );
				foreach ($configs as $appName => $cond) {
					if (self::processConditions( $cond, $appName ))
						return true;
				}
			}
			// Look into applications directories
			$d = opendir( self::$APPS_DIR . "" );
			while ($f = readdir( $d )) {
				if ($f == "." || $f == "..")
					continue;
				if (!is_dir( self::$APPS_DIR . "/" . $f ) || !is_file( self::$APPS_DIR . "/" . $f . "/Conf/Conditions.conf" ))
					continue;
				$cond = Conf\Parser::parseConfFile( self::$APPS_DIR . "/" . $f . "/Conf/Conditions.conf" );
				if (self::processConditions( $cond, $f ))
					return true;
			}
			throw new O_Ex_Critical( "Neither app-selecting config nor app config found." );

		}

		/**
		 * Sets application params to initiate single application
		 *
		 * @param string $name
		 * @param string $prefix
		 * @param string $ext
		 * @param string $mode
		 * @param string $baseUrl
		 */
		static public function setApp( $name, $prefix = null, $ext = null, $mode = null, $baseUrl = "/" )
		{
			if (self::$APP_NAME)
				throw new O_Ex_Critical( "Application have been already set." );
			self::$APP_NAME = $name;
			self::$_app = Array ("name" => $name, "prefix" => $prefix, "ext" => $ext, "mode" => $mode);
			self::$_env[ "base_url" ] = $baseUrl;

			if (!self::$_app[ "mode" ]) {
				$cond = Conf\Parser::parseConfFile( self::$APPS_DIR . "/" . self::$APP_NAME . "/Conf/Conditions.conf" );
				self::$_app[ "prefix" ] = Utils::first( $cond[ "prefix" ], self::$_app[ "prefix" ] );
				self::$_app[ "ext" ] = Utils::first( $cond[ "ext" ], self::$_app[ "ext" ], ClassManager::DEFAULT_EXTENSION );
				foreach ($cond[ "conditions" ] as $mode => $params) {
					if (self::processCondition( $params )) {
						self::$_app[ "mode" ] = $mode;
						break;
					}
				}
				if (!self::$_app[ "mode" ]) {
					throw new O_Ex_Critical( "Cannot find valid mode for application processing." );
				}
			}

			return self::initApp();
		}

		/**
		 * Processes app-selection pattern and registry
		 *
		 * @param array $cond
		 * @return bool
		 */
		static private function processCondition( Array $cond )
		{
			if ($cond[ "pattern" ] != "any") {
				// FIXME
				$interpreter = new Conf\Interpreter( );
				if (!$interpreter->processArray( $cond[ "pattern" ] ))
					return false;
			}
			if (is_array( $cond[ "registry" ] )) {
				Utils::mixInArray( self::$_app, $cond[ "registry" ] );
			}
			return true;
		}

		/**
		 * Processes several app-selecting conditions
		 *
		 * @param array $cond application root conditions array
		 * @param string $appName
		 * @return bool
		 */
		static private function processConditions( Array $cond, $appName )
		{
			foreach ($cond[ "conditions" ] as $mode => $params) {
				if (self::processCondition( $params )) {
					self::$_app[ "mode" ] = $mode;
					self::$_app[ "prefix" ] = $cond[ "prefix" ];
					self::$_app[ "ext" ] = Utils::first( $cond[ "ext" ], ClassManager::DEFAULT_EXTENSION );
					self::$_app[ "name" ] = $appName;
					self::$APP_NAME = $appName;
					return self::initApp();
				}
			}
			return false;
		}

		/**
		 * Adds message to system log
		 *
		 * @param string $message
		 * @param int $level the lesser, the more important
		 */
		static public function log( $message, $level = LOG_INFO )
		{
			if ($level <= LOG_CRIT) {
				fwrite( fopen( "php://stderr", "r" ), $message . "\n" );
			}
		}
	}

	class ClassManager {
		const DEFAULT_EXTENSION = "php";

		private static $prefixes = Array ();
		private static $callbacks = Array ();
		private static $requested = Array ();
		private static $defaultFolder;
		private static $notReadable = Array ();
		private static $loaded = Array ();

		/**
		 * Adds classname prefix to source folder assotiation
		 *
		 * @param string $prefix E.g. "O"
		 * @param string $source_folder E.g. "src/my/O"
		 * @param string $extension
		 */
		static public function registerPrefix( $prefix, $source_folder, $extension = self::DEFAULT_EXTENSION )
		{
			if ($source_folder[ strlen( $source_folder ) - 1 ] != "/")
				$source_folder .= "/";
			self::$prefixes = Array ("folder" => $source_folder, "ext" => $extension);
		}

		/**
		 * Registers callback to be called when class will be loaded
		 *
		 * @param callback $callback
		 * @param string $class
		 */
		static public function registerClassLoadedCallback( $callback, $class )
		{
			if (!isset( self::$callbacks[ $class ] ))
				self::$callbacks[ $class ] = Array ();
			self::$callbacks[ $class ][] = $callback;
		}

		/**
		 * Includes class source file -- autoload implementation
		 *
		 * @param string $class
		 */
		static public function load( $class )
		{
			$file = "";
			foreach (self::$prefixes as $prefix => $params) {
				if (strpos( $class, $prefix ) === 0) {
					$file = $params[ "folder" ] . str_replace( array ('\\', '_'), array ('/', '/'), substr( $class, strlen( $prefix ) + 1 ) ) . "." . $params[ "ext" ];
					break;
				}
			}

			self::$requested[ $class ] = $file;

			if (!$file) {
				$file = self::$defaultFolder . str_replace( array ('\\', '_'), array ('/', '/'), $class ) . "." . self::DEFAULT_EXTENSION;
			}
			if (!is_readable( $file )) {
				self::$notReadable[ $class ] = $file;
				$packages = get( "_packages" );
				foreach ($packages as $file => $pattern) {
					if ((is_array( $pattern ) && in_array( $class, $pattern )) || strpos( $class, $pattern ) === 0) {
						$file = self::$prefixes[ "O" ][ "folder" ] . str_replace( " ", "/", $file ) . "." . self::$prefixes[ "O" ][ "ext" ];
						if (is_readable( $file )) {
							include $file;
						}
					}
				}
			}

			else {
				include $file;
				self::$loaded[ $class ] = $file;
				O_Registry::set( "fw/classmanager/loaded/$class", $file );
				if (class_exists( $class )) {
					$callbacks = self::$callbacks[ $class ];
					if (count( $callbacks ))
						foreach ($callbacks as $callback)
							call_user_func( $callback );
				}
			}
		}

		static public function init()
		{
			// Register autoloader and Orena Framework source files
			spl_autoload_register( __CLASS__ . "::load" );
			self::registerPrefix( "O", __DIR__, "phps" );
			self::$defaultFolder = O_DOC_ROOT . "/O/inc/";
			set_include_path( O_DOC_ROOT . "/O/inc" . PATH_SEPARATOR . get_include_path() );
			;
		}
	}
	ClassManager::init();
}