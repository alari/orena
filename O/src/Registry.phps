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
			if (! $key)
				return $value;
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
	 * @param string $key
	 * @param mixed $value
	 * @param bool $add
	 */
	static private function setOrAdd($key, $value, $add = false) {
		$keys = explode ( "/", $key );
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
			$registry [$key] = array ($registry [$key], $value );
			return;
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

}