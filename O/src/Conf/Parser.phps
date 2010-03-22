<?php

class O_Conf_Parser {
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