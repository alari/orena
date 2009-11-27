<?php
/**
 * Class to store request environment information.
 *
 * @author Dmitry Kurinskiy
 */
class O_Registry {
	/**
	 * Parsed ini-files -- registry defaults
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
		
		if (! isset ( self::$registry [$key] )) {
			foreach ( self::$inheritance as $_key => $_replace_key ) {
				if (strpos ( $key, $_key ) === 0) {
					$key = $_replace_key . substr ( $key, 0, strlen ( $_key ) );
					if (isset ( self::$registry [$key] ))
						return self::$registry [$key];
				}
			}
		} else
			return self::$registry [$key];
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
	 * @param string $key
	 * @param mixed $value
	 * @param bool $add
	 */
	static private function setOrAdd($key, $value, $add = false) {
		$registry = &self::$registry;
		if (! $add) {
			$registry [$key] = $value;
			return;
		}
		if (isset ( $registry [$key] )) {
			$v = $registry [$key];
			if (is_array ( $v )) {
				$registry [$key] [] = $value;
				return;
			}
			$registry [$key] = array ($registry [$key], $value );
			return;
		}
		$registry [$key] = array ($value );
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

}