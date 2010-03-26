<?php
/**
 * Class to store request environment information.
 *
 * class:(classname)/params
 * app:
 * fw:
 * db:
 * env:
 *
 * @author Dmitry Kurinskiy
 */
class O_Registry {
	const VAR_CONTEXT = "_context";
	const VAR_ENV = "_env";
	const VAR_APP = "_app";
	const VAR_FW = "_fw";

	private static $_app = Array ();
	private static $_context = Array ();
	private static $_fw = Array ();
	private static $_env = Array ();

	private static $symbols = Array(
		"*"=>self::VAR_CONTEXT,
		"~"=>self::VAR_ENV,
		"_"=>self::VAR_APP,
		"`"=>self::VAR_FW
	);
	private static $prefixes = Array(
		"app/current" => self::VAR_CONTEXT,
		"app" => self::VAR_APP,
		"env" => self::VAR_ENV,
		"fw" => self::VAR_FW
	);

	/**
	 * Returns registry value or array of values, runtime or default
	 *
	 * @param string $key
	 * @params string|object $class Returns value from app/class/$classname registry key
	 * @return mixed
	 */
	static public function get($key, $class = null) {
		O_Profiler::start();
		if (is_object ( $class ))
			$class = get_class ( $class );
		if ($class)
			$key = "app/class/" . $class . "/$key";

		$varType = self::getVarType($key);
		if(!$varType) return $key;
		$r = O_Utils::getFromArray($key, self::$$varType);
		if($r === null && $varType == self::VAR_APP) {
			$r = O_Utils::getFromArray($key, self::$_fw);
		}
		O_Profiler::stop();
		return $r;
	}

	/**
	 * Returns variable type, modifies key
	 *
	 * @param string $key
	 */
	static public function getVarType(&$key) {
		if(array_key_exists($key[0], self::$symbols)) {
			$type = self::$symbols[$key[0]];
			$key = substr($key, 1);
			return $type;
		}
		foreach(self::$prefixes as $k=>$type) {
			if(strpos($key, $k) === 0) {
				$key = substr($key, strlen($k));
				if($key && $key[0] === '/') $key = substr($key, 1);
				return $type;
			}
		}
		return null;
	}

	/**
	 * Sets runtime registry value, overrides defaults
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	static public function set($key, $value) {
		self::setOrAdd ( $key, $value );
	}

	/**
	 * Adds value at the bottom of key array values
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	static public function add($key, $value) {
		self::setOrAdd ( $key, $value, true );
	}

	/**
	 * Handler for add and set methods
	 *
	 * @param string|array $key
	 * @param mixed $value
	 * @param bool $add
	 */
	static public function setOrAdd($key, $value, $add = false) {
		O_Profiler::start();
		$varType = self::getVarType( $key );
		if (!$varType)
			return null;
		if ($varType != self::VAR_CONTEXT) {
			//self::log( "You should set only context variables from scripts. (Setting '$key')", LOG_NOTICE );
		}
		O_Utils::setIntoArray( $key, self::$$varType, $value, $add );
		O_Profiler::stop();
	}

	/**
	 * Parses yaml-like config file, stores its contents in $rootkey
	 *
	 * @param string $src
	 * @param string $rootkey = null
	 * @return Array if $rootkey is not specified
	 */
	static public function parseFile($src, $rootkey = null) {
		if(!$rootkey) return O_Conf_Parser::parseConfFile($src);
		$varType = self::getVarType($rootkey);
		return O_Conf_Parser::parseConfFile($src, self::$$varType);
	}

	/**
	 * Mixes values into registry root
	 *
	 * @param array $values
	 * @param string $rootkey
	 */
	static public function mixIn(Array $values, $rootkey) {
		$varType = self::getVarType($rootkey);
		O_Utils::mixInArray(self::$$varType, $values);
	}
}

function O($name, $value=null, $add=false) {
	if($value === null) return O_Registry::get($name);
	return O_Registry::setOrAdd($name, $value, $add);
}
function O_cl($name, $class, $value=null, $add=false) {
	if($value === null) return O_Registry::get($name, $class);
	$name = "_class/".(is_object($class)?get_class($class):$class)."/".$name;
	return O_Registry::setOrAdd($name, $value, $add);
}
function O_add($name, $value) {
	return O_Registry::add($name, $value);
}