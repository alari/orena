<?php
namespace O {

	use O\Conf;

	function get( $name )
	{
		return Core::get( $name );
	}

	function set( $name, $value, $add = false )
	{
		return Core::set( $name, $value, $add );
	}

	class Core {
		const VAR_CONTEXT = "_context";
		const VAR_ENV = "_env";
		const VAR_APP = "_app";
		const VAR_FW = "_fw";

		const CONFIG_FILE_FW = "Orena.fw.conf";
		const CONFIG_FILE_APP = "Conf/Registry.conf";
		const CONFIG_FILE_APP_FW = "Conf/Orena.fw.conf";

		private static $APPS_DIR;
		private static $APP_NAME;

		private static $_app = Array ();
		private static $_context = Array ();
		private static $_fw = Array ();
		private static $_env = Array ();

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
		 * @param string $name (*cont ~env `fw _app %local)
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
		 * @param string $name (*cont ~env `fw _app %local)
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
			} elseif ($name[ 0 ] == '%') {
				return self::VAR_LOCAL;
			}
			return null;
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
				$app_ext = O_ClassManager::DEFAULT_EXTENSION;

			// Register application classes
			O_ClassManager::registerPrefix( $app_prefix, self::$APPS_DIR . "/" . self::$APP_NAME, $app_ext );
		}

		/**
		 * Selects current application, prepares basic app conf:
		 * mode
		 * self::$APP_NAME
		 * prefix
		 * ext
		 * ~base_url
		 */
		static private function selectApp()
		{
			;
		}

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
				self::$_app[ "ext" ] = Utils::first( $cond[ "ext" ], self::$_app[ "ext" ], O_ClassManager::DEFAULT_EXTENSION );
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

			self::initApp();
		}

		static private function processCondition( Array $cond )
		{
			if ($cond[ "pattern" ] != "any") {
				$interpreter = new Conf\Interpreter( );
				if (!$interpreter->processArray( $cond[ "pattern" ] ))
					return false;
			}
			if(is_array($cond["registry"])) {
				Utils::mixInArray(self::$_app, $cond["registry"]);
			}
			return true;
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

	class Utils {

		/**
		 * Mixes $mix into $base array
		 *
		 * @param Array $base
		 * @param Array $mix
		 */
		static public function mixInArray( Array &$base, Array $mix )
		{
			foreach ($base as $k => $v) {
				if (!array_key_exists( $k, $mix ))
					continue;
				if (is_array( $v ) && is_array( $mix[ $k ] )) {
					self::mixInArray( $base[ $k ], $mix[ $k ] );
				} else {
					$base[ $k ] = $mix[ $k ];
				}
			}
			foreach ($mix as $k => $v) {
				if (array_key_exists( $k, $base ))
					continue;
				$base[ $k ] = $v;
			}
		}

		/**
		 * Returns value from nested array, key levels separated with /
		 *
		 * @param string $nestedKey
		 * @param array $array
		 * @return null
		 */
		static public function getFromArray( $nestedKey, Array $array )
		{
			if (!strpos( $nestedKey, "/" ))
				return $array;
			$keys = explode( "/", $nestedKey );
			$value = $array;
			foreach ($keys as $k) {
				if (array_key_exists( $k, $value )) {
					$value = $value[ $k ];
					continue;
				}
				return null;
			}
			return $value;
		}

		/**
		 * Sets value into nested array
		 *
		 * @param string $nestedKey levels separated by /
		 * @param array $array
		 * @param mixed $value
		 * @param bool $add if true, adds value to the end of array
		 */
		static public function setIntoArray( $nestedKey, Array &$array, $value, $add = false )
		{
			if (!strpos( $nestedKey, "/" ))
				$array = $value;
			$keys = explode( "/", $nestedKey );
			foreach ($keys as $i => $k) {
				if (isset( $array[ $k ] )) {
					$array = &$array[ $k ];
				} elseif ($i < count( $keys ) - 1) {
					$array[ $k ] = Array ();
					$array = &$array[ $k ];
				} else {
					if ($add) {
						if (!isset( $array[ $k ] ) || !is_array( $array[ $k ] ))
							$array[ $k ] = Array ();
						$array[ $k ][] = $value;
						return;
					} else {
						$array[ $k ] = $value;
						return;
					}
				}
			}
			if ($add)
				$array[] = $value;
			else
				$array = $value;
		}

		/**
		 * Returns first argument
		 *
		 * @return mixed
		 */
		static public function first()
		{
			foreach (func_get_args() as $arg) {
				if ($arg)
					return $arg;
			}
			return null;
		}
	}
}
namespace O\Conf {

	use O\Core;

	class Parser {

		/**
		 * Parses yaml-like config file, stores its contents in $mixInto array
		 *
		 * @param string $src
		 * @param array $mixInto = null
		 * @return Array if $mixInto is not specified
		 */
		static public function parseConfFile( $src, &$mixInto = null )
		{
			if (!is_readable( $src )) {
				throw new O_Ex_Config( "Config file not found ($src)" );
			}
			$f = fopen( $src, 'r' );

			$links = Array ();
			if (!$mixInto) {
				$result = Array ();
				$links[ 0 ] = & $result;
			} else {
				$links[ 0 ] = & $mixInto;
			}

			$prev_level = 0;
			// Lines counter for error displaying
			$i = 0;

			while ($l = fgets( $f )) {
				++$i;
				$level = strlen( $l ) - strlen( $l = ltrim( $l ) );
				// Don't process empty strings and comments (started with #)
				if (!$l || $l[ 0 ] == "#") {
					continue;
				}
				if ($level - $prev_level > 1) {
					throw new O_Ex_Config( "Markup error in config file ($src:$i)." );
				}
				$prev_level = $level;

				$l = rtrim( $l );

				// Line has keypart
				$l = str_replace( "\\:", "\t", $l );
				if (strpos( $l, ":" )) {
					list ($k, $v) = explode( ":", $l, 2 );
					$k = str_replace( "\t", ":", $k );
					$v = str_replace( "\t", ":", $v );
					$k = rtrim( $k );
					$v = ltrim( $v );
					if ($v) {
						$links[ $level ][ $k ] = $v;
					} else {
						$links[ $level + 1 ] = & $links[ $level ][ $k ];
					}
				} else {
					$links[ $level ][] = $l;
				}
			}

			fclose( $f );

			if (!$mixInto)
				return $result;
			return null;
		}
	}

	class Interpreter {
		const T_COMPARE = "t_compare";
		const T_IF = "t_if";
		const T_ELSE = "t_else";
		const T_ELIF = "t_elif";
		const T_CHOOSE = "t_choose";
		const T_TEST = "t_test";
		const T_OTHERWISE = "t_otherwise";
		const T_CALL = "t_call";
		const T_PROCEDURE = "t_procedure";
		const T_VAR = "t_var";
		const T_RETURN = "t_return";
		const T_END = "t_end";
		const T_VALUE = "t_value";
		const T_ASSIGN = "t_assign";

		private static $TOKENS_FIRST = Array ("if" => self::T_IF,
				"else" => self::T_ELSE,
				"elif" => self::T_ELIF,
				"choose" => self::T_CHOOSE,
				"test" => self::T_TEST,
				"otherwise" => self::T_OTHERWISE,
				"var" => self::T_VAR,
				"return" => self::T_RETURN,
				"end" => self::T_END,
				"procedure" => self::T_PROCEDURE,
				"call" => self::T_CALL);
		private static $TOKENS_SECOND = Array ("==" => self::T_COMPARE,
				"!=" => self::T_COMPARE,
				"~" => self::T_COMPARE,
				"<" => self::T_COMPARE,
				">" => self::T_COMPARE,
				"<=" => self::T_COMPARE,
				">=" => self::T_COMPARE,
				"is" => self::T_COMPARE,
				"=" => self::T_ASSIGN);

		private $_local = Array ();
		private $_local_level = 0;
		private $_procedures = Array ();

		public function __construct()
		{
			;
		}

		public function processArray( Array $array )
		{
			$level = $this->_local_level;
			$return = null;
			try {
				foreach ($array as $k => $v) {
					if (is_numeric( $k )) {
						$k = $v;
						$v = null;
					}
					$this->setLocalLevel( $level + 1 );
					$return = $this->processExpression( $k, $v );
					$this->setLocalLevel( $level );
				}
			}
			catch (Ex $e) {
				$this->setLocalLevel( $level );
				if ($e instanceof ExReturn) {
					return $e->getValue();
				}
				throw $e;
			}
			return $return;
		}

		private function t_compare( $l, $r, $op, $vals )
		{
			$r = $this->processExpression( $r, $vals );
			$l = $this->getVar( $l );
			switch ($op) {
				case "==" :
					return $l == $r;
				case "!=" :
					return $l != $r;
				case "<" :
					return $l < $r;
				case ">" :
					return $l > $r;
				case "<=" :
					return $l <= $r;
				case ">=" :
					return $l >= $r;
				case "~" :
					$ret = preg_match( "#^$r$#", $l, $m );
					if ($ret)
						$this->setLocal( "match", $m );
					return $ret;
				case "is" :
					if ($r == "null")
						return $l === null;
					if ($r == "not null")
						return $l !== null;
					if ($r == "present")
						return (bool)$l;
					if (is_object( $l ))
						return $l instanceof $r;
			}
			throw new ExSyntax( "Compare via '$op': don't know what to do." );
		}

		private function t_value( $name )
		{
			return $this->getVar( $name );
		}

		private function t_assign( $name, $value, $op, $vals )
		{
			if ($value == "Ar") {
				$this->setVar( $name, $vals );
			} elseif ($value == "Ad") {
				$this->setVar( $name, $vals, true );
			} elseif (!$value && is_array( $vals )) {
				$this->setVar( $name, $this->processArray( $vals ) );
			}
			$this->setVar( $name, $this->processExpression( $value, $vals ) );
		}

		private function processExpression( $expression, $value = null )
		{
			if (!$expression) {
				if (is_array( $value ))
					return $this->processArray( $value );
				else
					return null;
			}
			$params = Array ();
			$type = $this->getExpressionType( $expression, $params );
			$params[] = $value;
			if ($type) {
				return call_user_func_array( array ($this, $type), $params );
			}
			throw new ExSyntax( "Unknown expression: $expression." );
		}

		private function getExpressionType( $expr, &$params )
		{
			$first = $expr;
			$second = "";
			$other = "";
			if (strpos( $expr, " " )) {
				list ($first, $second) = explode( " ", $expr, 2 );
				if (strpos( $second, " " )) {
					list ($second, $other) = explode( " ", $second, 2 );
				}
			}
			if (array_key_exists( $first, self::$TOKENS_FIRST )) {
				$params = Array (substr( $expr, strlen( $first ) + 1 ), $first);
				return self::$TOKENS_FIRST[ $first ];
			}
			if (array_key_exists( $second, self::$TOKENS_SECOND )) {
				$params = Array ($first, $other, $second);
				return self::$TOKENS_SECOND[ $second ];
			}
			$params = Array ($expr);
			return self::T_VALUE;
		}

		/**
		 * Sets local or public variable
		 *
		 * @param string $name
		 * @param mixed $value
		 * @param bool $add
		 */
		private function setVar( $name, $value, $add = false )
		{
			if ($name[ 0 ] == "%") {
				return $this->setLocal( $name, $value, $add );
			}
			Core::set( $name, $value, $add );
		}

		/**
		 * Returns local or public variable's value
		 *
		 * @param string $name
		 */
		private function getVar( $name )
		{
			if ($name[ 0 ] == "%") {
				return $this->getLocal( $name );
			}
			return Core::get( $name );
		}

		/**
		 * Sets local variable
		 *
		 * @param string $name
		 * @param mixed $value
		 * @param bool $add
		 */
		private function setLocal( $name, $value, $add = false )
		{
			$i = $this->_local_level;
			for (; $i >= 0; --$i) {
				$res = Core::getFromArray( $name, $this->_local[ $i ] );
				if ($res !== null) {
					Core::setIntoArray( $name, $this->_local[ $i ], $value, $add );
					return;
				}
			}
			Core::setIntoArray( $name, $this->_local[ $this->_local_level ], $value, $add );
		}

		/**
		 * Returns local variable
		 *
		 * @param string $name
		 * @return mixed
		 */
		private function getLocal( $name )
		{
			$i = $this->_local_level;
			for (; $i >= 0; --$i) {
				$res = Core::getFromArray( $name, $this->_local[ $i ] );
				if ($res !== null)
					return $res;
			}
			return null;
		}

		/**
		 * Initiates local variable in current level
		 *
		 * @param string $name
		 */
		private function varLocal( $name )
		{
			Core::setIntoArray( $name, $this->_local[ $this->_local_level ], "" );
		}

		/**
		 * Changes level for local variables
		 *
		 * @param int $level
		 */
		static private function setLocalLevel( $level )
		{
			if ($level == $this->_local_level)
				return;
			if ($level > $this->_local_level) {
				$i = $this->_local_level + 1;
				for (; $i <= $level; ++$i)
					$this->_local[ $i ] = Array ();
			} else {
				$this->_local = array_slice( $this->_local, 0, $level + 1 );
			}
			$this->_local_level = $level;
		}
	}

	class Ex {
	}
	class ExError extends Ex {
	}
	class ExSyntax extends ExError {
	}
	class ExReturn extends Ex {
		private $value;

		public function __construct( $value )
		{
			$this->value = $value;
		}

		public function getValue()
		{
			return $this->value;
		}
	}
}