<?php

class O {
	const VAR_CONTEXT = "_context";
	const VAR_ENV = "_env";
	const VAR_APP = "_app";
	const VAR_FW = "_fw";
	const VAR_LOCAL = "_local";

	const CONFIG_FILE_FW = "Orena.fw.conf";
	const CONFIG_FILE_APP = "Conf/Registry.conf";
	const CONFIG_FILE_APP_FW = "Conf/Orena.fw.conf";

	static private $APPS_DIR;
	static private $APP_NAME;

	static private $_app = Array();
	static private $_context = Array();
	static private $_fw = Array();
	static private $_env = Array();
	static private $_local = Array();
	static private $_local_level = 0;

	/**
	 * Returns variable by its simple name
	 *
	 * @param string $name (*cont ~env `fw _app %local)
	 * @return mixed
	 */
	static public function get($name) {
		$varType = self::getVarType($name);
		$name = substr($name, 1);
		if($varType == self::VAR_LOCAL) {
			return self::getLocal($name);
		}
		$res = self::getFromArray($name, self::$$varType);
		// Return framework value for app request
		if($res === null && $varType == self::VAR_APP) {
			return self::getFromArray($name, self::$_fw);
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
	static public function set($name, $value, $add=false) {
		$varType = self::getVarType($name);
		$name = substr($name, 1);
		if($varType != self::VAR_CONTEXT && $varType != self::VAR_LOCAL) {
			self::log("You should set only context variables from scripts. (Setting '$name')", LOG_NOTICE);
		}
		if($varType == self::VAR_LOCAL) {
			return self::setLocal($name, $value, $add);
		}
		self::setIntoArray($name, self::$$varType, $value, $add);
	}

	/**
	 * Adds message to system log
	 *
	 * @param string $message
	 * @param int $level the lesser, the more important
	 */
	static public function log($message, $level=LOG_INFO) {
		if($level <= LOG_CRIT) {
			fwrite(fopen("php://stderr", "r"), $message."\n");
		}
	}

	/**
	 * Returns local variable
	 *
	 * @param string $name
	 * @return mixed
	 */
	static private function getLocal($name) {
		$i = self::$_local_level;
		for(; $i>=0; --$i) {
			$res = self::getFromArray($name, self::$_local[$i]);
			if($res !== null) return $res;
		}
		return null;
	}

	/**
	 * Sets local variable
	 *
	 * @param string $name
	 * @param mixed $value
	 * @param bool $add
	 */
	static private function setLocal($name, $value, $add=false) {
		$i = self::$_local_level;
		for(; $i>=0; --$i) {
			$res = self::getFromArray($name, self::$_local[$i]);
			if($res !== null) {
				self::setIntoArray($name, self::$_local[$i], $value, $add);
				return;
			}
		}
		self::setIntoArray($name, self::$_local[self::$_local_level], $value, $add);
	}

	/**
	 * Initiates local variable in current level
	 *
	 * @param string $name
	 */
	static private function varLocal($name) {
		self::setIntoArray($name, self::$_local[self::$_local_level], "");
	}

	/**
	 * Changes level for local variables
	 *
	 * @param int $level
	 */
	static private function setLocalLevel($level) {
		if($level == self::$_local_level) return;
		if($level > self::$_local_level) {
			$i = self::$_local_level+1;
			for(; $i<=$level; ++$i) self::$_local[$i] = Array();
		} else {
			self::$_local = array_slice(self::$_local, 0, $level+1);
		}
		self::$_local_level = $level;
	}

	/**
	 * Returns variable type
	 *
	 * @param string $name (*cont ~env `fw _app %local)
	 * @return const
	 */
	static public function getVarType($name) {
		if(strlen($name) < 3) throw new O_Ex_Critical("Var name '$name' is too short.");
		if($name[0] == '*') {
			return self::VAR_CONTEXT;
		} elseif($name[0] == '~') {
			return self::VAR_ENV;
		} elseif($name[0] == '`') {
			return self::VAR_FW;
		} elseif($name[0] == '_') {
			return self::VAR_APP;
		} elseif($name[0] == '%') {
			return self::VAR_LOCAL;
		}
		return null;
	}

	/**
	 * Initiates basic values of environment vars
	 */
	static private function initEnv() {
		// Saving url without query string to process it correctly
		$url = $_SERVER[ 'REQUEST_URI' ];
		if (strpos( $url, "?" ))
			$url = substr( $url, 0, strpos( $url, '?' ) );
		self::$_env["request_url"] = $url;

		// Saving HTTP_HOST value
		self::$_env["http_host"] = $_SERVER[ 'HTTP_HOST' ];
		// Request method
		self::$_env["request_method"] = $_SERVER[ 'REQUEST_METHOD' ];

		// Base URL
		self::$_env["base_url"] = '/';
	}

	/**
	 * Loads framework config from file
	 */
	static private function initFw() {
		if(is_file( self::$APPS_DIR."/".self::CONFIG_FILE_FW )) {
			$src = self::$APPS_DIR."/".self::CONFIG_FILE_FW;
		} elseif(is_file(__DIR__."/".self::CONFIG_FILE_FW)){
			$src = __DIR__."/".self::CONFIG_FILE_FW;
		} else {
			throw new O_Ex_Critical( "Cannot find framework configuration file." );
		}
		self::parseConfFile( $src, self::$_fw );
	}

	/**
	 * Loads and interp. app config
	 */
	static private function initApp() {
		// Try to find app or die
		if(!self::$APP_NAME) self::selectApp();
		if(!self::$APP_NAME) throw new O_Ex_Critical("Cannot select valid application.");

		// Parse app own configs
		if(is_file(self::$APPS_DIR."/".self::$APP_NAME."/".self::CONFIG_FILE_APP)) {
			self::parseConfFile(self::$APPS_DIR."/".self::$APP_NAME."/".self::CONFIG_FILE_APP, self::$_app);
		}
		// Mix in fw conf
		if(is_file(self::$APPS_DIR."/".self::$APP_NAME."/".self::CONFIG_FILE_APP_FW)) {
			self::parseConfFile(self::$APPS_DIR."/".self::$APP_NAME."/".self::CONFIG_FILE_APP_FW, self::$_fw);
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
		O_ClassManager::registerPrefix( $app_prefix, self::$APPS_DIR."/" . self::$APP_NAME, $app_ext );
	}

	/**
	 * Selects current application, prepares basic app conf:
	 * mode
	 * self::$APP_NAME
	 * prefix
	 * ext
	 * ~base_url
	 */
	static private function selectApp() {
		;
	}

	static public function setApp($name, $prefix = null, $ext = null, $mode = null, $baseUrl = "/") {
		if(self::$APP_NAME) throw new O_Ex_Critical("Application have been already set.");
		self::$APP_NAME = $name;
		self::$_app = Array(
			"name" => $name,
			"prefix"=>$prefix,
			"ext"=>$ext,
			"mode"=>$mode);
		self::$_env["base_url"] = $baseUrl;

		if(!self::$_app["mode"]) {
			$cond = self::parseConfFile(self::$APPS_DIR."/".self::$APP_NAME."/Conf/Conditions.conf");
			self::$_app["prefix"] = self::first($cond["prefix"], self::$_app["prefix"]);
			self::$_app["ext"] = self::first($cond["ext"], self::$_app["ext"], O_ClassManager::DEFAULT_EXTENSION);
			foreach($cond["conditions"] as $mode=>$params) {
				if($params["pattern"] == "any") {
					self::$_app["mode"] = $mode;
					break;
				}
				// FIXME: check pattern
			}
			if(!self::$_app["mode"]) {
				throw new O_Ex_Critical("Cannot find valid mode for application processing.");
			}
		}

		self::initApp();
	}

	static private function processCondition(Array $cond) {
		if($cond["pattern"] == "any")
		foreach($cond["pattern"] as $name=>$params) {

		}
	}

	static public function processLine($k, $v) {
		$parts = explode(" ", $k);
		if(isset($parts[1])) switch($parts[1]) {
			case "like":
				break;
			case "=":
				break;
			case "!=":
				break;
		}
	}

	/**
	 * Mixes $mix into $base array
	 *
	 * @param Array $base
	 * @param Array $mix
	 */
	static public function mixInArray(Array &$base, Array $mix)
    {
        foreach($base as $k => $v) {
            if(!array_key_exists($k,$mix)) continue;
            if(is_array($v) && is_array($mix[$k])){
            	self::mixInArray($base[$k], $mix[$k]);
            }else{
                $base[$k] = $mix[$k];
            }
        }
        foreach($mix as $k=>$v) {
        	if(array_key_exists($k, $base)) continue;
        	$base[$k] = $v;
        }
    }

    /**
     * Returns value from nested array, key levels separated with /
     *
     * @param string $nestedKey
     * @param array $array
     * @return null
     */
    static public function getFromArray($nestedKey, Array $array) {
    	if(!strpos($nestedKey, "/")) return $array;
		$keys = explode ( "/", $nestedKey );
		$value = $array;
		foreach ( $keys as $k ) {
			if (array_key_exists( $k, $value )) {
				$value = $value [$k];
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
    static public function setIntoArray($nestedKey, Array &$array, $value, $add=false) {
    	if(!strpos($nestedKey, "/")) $array = $value;
		$keys = explode ( "/", $nestedKey );
		foreach ( $keys as $i => $k ) {
			if (isset ( $array [$k] )) {
				$array = &$array [$k];
			} elseif ($i < count ( $keys ) - 1) {
				$array [$k] = Array ();
				$array = &$array [$k];
			} else {
				if ($add) {
					if (! isset ( $array [$k] ) || ! is_array ( $array [$k] ))
						$array [$k] = Array ();
					$array [$k] [] = $value;
					return;
				} else {
					$array [$k] = $value;
					return;
				}
			}
		}
		if ($add)
			$array [] = $value;
		else
			$array = $value;
    }

	/**
	 * Parses yaml-like config file, stores its contents in $mixInto array
	 *
	 * @param string $src
	 * @param array $mixInto = null
	 * @return Array if $mixInto is not specified
	 */
	static public function parseConfFile($src, &$mixInto = null) {
		O_Profiler::start();
		if (! is_readable ( $src )) {
			throw new O_Ex_Config ( "Config file not found ($src)" );
		}
		$f = fopen ( $src, 'r' );

		$links = Array ();
		if (! $mixInto) {
			$result = Array ();
			$links [0] = & $result;
		} else {
			$links [0] = & $mixInto;
		}

		$prev_level = 0;
		// Lines counter for error displaying
		$i = 0;

		while ( $l = fgets ( $f ) ) {
			++$i;
			$level = strlen ( $l ) - strlen ( $l = ltrim ( $l ) );
			// Don't process empty strings and comments (started with #)
			if (! $l || $l [0] == "#") {
				continue;
			}
			if ($level - $prev_level > 1) {
				throw new O_Ex_Config ( "Markup error in config file ($src:$i)." );
			}
			$prev_level = $level;

			$l = rtrim ( $l );

			// Line has keypart
			$l = str_replace("\\:", "\t", $l);
			if (strpos ( $l, ":" )) {
				list ( $k, $v ) = explode ( ":", $l, 2 );
				$k = str_replace("\t", ":", $k);
				$v = str_replace("\t", ":", $v);
				$k = rtrim ( $k );
				$v = ltrim ( $v );
				if ($v) {
					$links [$level] [$k] = $v;
				} else {
					$links [$level + 1] = & $links [$level] [$k];
				}
			} else {
				$links [$level] [] = $l;
			}
		}

		fclose ( $f );

		O_Profiler::stop();

		if (! $mixInto)
			return $result;
		return null;
	}

	/**
	 * Returns first argument
	 *
	 * @return mixed
	 */
	static public function first() {
		foreach(func_get_args() as $arg) {
			if($arg) return $arg;
		}
		return null;
	}
}

class O_ConfParser {
	protected $src;

	public function __construct($src) {
		if (! is_readable ( $src )) {
			throw new O_Ex_Config ( "Config file not found ($src)" );
		}
		$this->src = $src;
	}

	protected function shouldIgnoreLine($level, $line) {
		// Don't process empty strings and comments (started with #)
		if(!$line || $line[0] == '#') return true;
		return false;
	}

	public function parse(&$mixInto = null) {
		$f = fopen($this->src, "r");
		$result = Array();
		if($mixInto) $result = $mixInto;

		$links = Array ();
		$links[0] = $result;

		$prev_level = 0;
		// Lines counter for error displaying
		$i = 0;

		while ( $l = fgets ( $f ) ) {
			++$i;
			$level = strlen ( $l ) - strlen ( $l = ltrim ( $l ) );

			if($this->shouldIgnoreLine($level, $l)) continue;

			if ($level - $prev_level > 1) {
				throw new O_Ex_Config ( "Markup error in config file ($this->src:$i)." );
			}
			$prev_level = $level;

			$l = rtrim ( $l );

			// Line has keypart
			$l = str_replace("\\:", "\t", $l);
			if (strpos ( $l, ":" )) {
				list ( $k, $v ) = explode ( ":", $l, 2 );
				$k = str_replace("\t", ":", $k);
				$v = str_replace("\t", ":", $v);
				$k = rtrim ( $k );
				$v = ltrim ( $v );
				if ($v) {
					$links [$level] [$k] = $v;
				} else {
					$links [$level + 1] = & $links [$level] [$k];
				}
			} else {
				$links [$level] [] = $l;
			}
		}

		fclose ( $f );
		if(!$mixInto) return $result;
		return null;
	}
}