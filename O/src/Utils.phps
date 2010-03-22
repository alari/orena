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
			if (!$nestedKey)
				return $array;
			if (!strpos( $nestedKey, "/" ))
				return array_key_exists($nestedKey, $array) ? $array[$nestedKey] : null;
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
			if(!$nestedKey) {
				$array = $value;
				return;
			}

			if (!strpos( $nestedKey, "/" )) {
				$array[$nestedKey] = $value;
				return;
			}

			$keys = explode( "/", $nestedKey );
			$set = &$array;
			foreach ($keys as $i => $k) {
				if (isset( $set[ $k ] )) {
					$set = &$set[ $k ];
				} elseif ($i < count( $keys ) - 1) {
					$set[ $k ] = Array ();
					$set = &$set[ $k ];
				} else {
					if ($add) {
						if (!isset( $set[ $k ] ) || !is_array( $set[ $k ] ))
							$set[ $k ] = Array ();
						$set[ $k ][] = $value;
						return;
					} else {
						$set[ $k ] = $value;
						return;
					}
				}
			}
			if ($add)
				$set[] = $value;
			else
				$set = $value;
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