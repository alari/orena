<?php

class Registry {
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
	 * @return mixed
	 */
	static public function get( $key )
	{
		$keys = explode( "/", $key );
		$value = self::$registry;
		foreach ($keys as $i => $k) {
			if (isset( $value[ $k ] )) {
				$value = $value[ $k ];
				continue;
			}
			// Value not found, trying to get it from parents
			for ($j = count( $keys ); $j > 0; $j--) {
				$_key = join( "/", array_slice( $keys, 0, $j ) );
				if (isset( self::$inheritance[ $_key ] )) {
					$key = self::$inheritance[ $_key ] . ($j + 1 < count( $keys ) ? "/" . join( "/",
							array_slice( $keys, $j ) ) : "");
					return self::get( $key );
				} else
					continue;
			}
			if (!$key)
				return $value;
			throw new Exception( "Unknown registry key: $key, $i." );
		}
		return $value;
	}

	/**
	 * Sets runtime registry value, overrides defaults
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	static public function set( $key, $value )
	{
		$keys = explode( "/", $key );
		$registry = &self::$registry;
		foreach ($keys as $i => $k) {
			if (isset( $registry[ $k ] )) {
				$registry = &$registry[ $k ];
			} elseif ($i < count( $keys ) - 1) {
				$registry[ $k ] = Array ();
				$registry = &$registry[ $k ];
			} else {
				$registry[ $k ] = $value;
			}
		}
	}

	/**
	 * Adds inheritance aliasing for registry keys
	 *
	 * @param string $base Key to inherit from
	 * @param string $inherit Key to inherit to
	 */
	static public function setInheritance( $base, $inherit )
	{
		self::$inheritance[ $inherit ] = $base;
	}

}