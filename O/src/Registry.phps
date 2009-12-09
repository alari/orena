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
	/**
	 * Parsed registry
	 *
	 * @var Array
	 */
	public static $registry = Array ();
	
	/**
	 * Inheritance dependencies between registry keys
	 *
	 * @var Array
	 */
	private static $inheritance = Array ();
	
	/**
	 * Returns registry value or array of values, runtime or default
	 *
	 * @param string $key like ini-file[/ini-section[/key]]
	 * @params string|object $class Returns value from app/class/$classname registry key
	 * @return mixed
	 */
	static public function get($key, $class = null) {
		if (is_object ( $class ))
			$class = get_class ( $class );
		if ($class)
			$key = "app/class/" . $class . "/$key";
		
		$keys = explode ( "/", $key );
		$value = self::$registry;
		foreach ( $keys as $k ) {
			if (isset ( $value [$k] )) {
				$value = $value [$k];
				continue;
			}
			// Value not found, trying to get it from parents
			for($j = count ( $keys ); $j > 0; $j --) {
				$_key = join ( "/", array_slice ( $keys, 0, $j ) );
				if (isset ( self::$inheritance [$_key] )) {
					$key = self::$inheritance [$_key] . ($j < count ( $keys ) ? "/" . join ( "/", array_slice ( $keys, $j ) ) : "");
					return self::get ( $key );
				} else
					continue;
			}
			if (! $key) {
				return $value;
			}
			return null;
		}
		return $value;
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
	static private function setOrAdd($key, $value, $add = false) {
		$keys = is_array($key) ? $key : explode ( "/", $key );
		$registry = &self::$registry;
		foreach ( $keys as $i => $k ) {
			if (isset ( $registry [$k] )) {
				$registry = &$registry [$k];
			} elseif ($i < count ( $keys ) - 1) {
				$registry [$k] = Array ();
				$registry = &$registry [$k];
			} else {
				if ($add) {
					if (! isset ( $registry [$k] ) || ! is_array ( $registry [$k] ))
						$registry [$k] = Array ();
					$registry [$k] [] = $value;
					return;
				} else {
					$registry [$k] = $value;
					return;
				}
			}
		}
		if ($add)
			$registry [] = $value;
		else
			$registry = $value;
	}
	
	/**
	 * Adds inheritance aliasing for registry keys
	 *
	 * @param string $base Key to inherit from
	 * @param string $inherit Key to inherit to
	 */
	static public function setInheritance($base, $inherit) {
		self::$inheritance [$inherit] = $base;
	}
	
	/**
	 * Parses yaml-like config file, stores its contents in $rootkey
	 *
	 * @param string $src
	 * @param string $rootkey = null
	 * @return Array if $rootkey is not specified
	 */
	static public function parseFile($src, $rootkey = null) {
		if (! is_readable ( $src )) {
			throw new O_Ex_Config ( "Config file not found (rootkey $rootkey)" );
		}
		$f = fopen ( $src, 'r' );
		
		$links = Array ();
		if (! $rootkey) {
			$result = Array ();
			$links [0] = & $result;
		} else {
			if (! isset ( self::$registry [$rootkey] )) {
				self::$registry [$rootkey] = Array ();
			}
			$links [0] = & self::$registry [$rootkey];
		}
		
		$prev_level = 0;
		
		while ( $l = fgets ( $f ) ) {
			$level = strlen ( $l ) - strlen ( $l = ltrim ( $l ) );
			if ($level - $prev_level > 1) {
				throw new O_Ex_Config ( "Markup error in config file." );
			}
			$prev_level = $level;
			
			$l = rtrim ( $l );
			// Don't process empty strings and comments (started with #)
			if (! $l || $l [0] == "#") {
				continue;
			}
			// Line has keypart
			// TODO: add ability to include ":" sign in the value line
			if (strpos ( $l, ":" )) {
				list ( $k, $v ) = explode ( ":", $l, 2 );
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
		
		if (! $rootkey)
			return $result;
		return null;
	}
	
	static public function mixIn(Array $values, $rootkey) {
		if(!isset(self::$registry[$rootkey])) {
			self::$registry[$rootkey] = Array();
		}
		self::mixInArray(self::$registry[$rootkey], $values);
	}
	
	static private function mixInArray(&$base,$mix)
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

}