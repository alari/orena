<?php

class O_Utils {
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