<?php
/**
 * @field trusted_sites -owns many O_OpenId_Provider_TrustedSite -inverse user
 * @field identity varchar(64)
 * @field pwd_hash varchar(32)
 * @index identity -unique
 */
class O_OpenId_Provider_UserPlugin implements O_Dao_iPlugin {
	private static $instance;
	private static $objs = Array ();

	/**
	 * Returns instance of class
	 *
	 * @return O_OpenId_Provider_UserPlugin
	 */
	static public function getInstance()
	{
		if (!self::$instance)
			self::$instance = new self( );
		return self::$instance;
	}

	/**
	 * Returns user by openid identity url
	 *
	 * @param string $id
	 * @return object
	 */
	static public function getByIdentity( $id )
	{
		self::normalize( $id );
		if (!isset( self::$objs[ $id ] )) {
			$class = O_Registry::get( "app/classnames/user" );
			self::$objs[ $id ] = O_Dao_Query::get( $class )->test( "identity", $id )->getOne();
		}
		return self::$objs[ $id ];
	}

	/**
	 * Normalizes openid identifier
	 *
	 * @param string $id
	 */
	static public function normalize( &$id )
	{
	$id = trim($id);
        if (strlen($id) === 0) {
            return true;
        }

        // 7.2.1
        if (strpos($id, 'xri://$ip*') === 0) {
            $id = substr($id, strlen('xri://$ip*'));
        } else if (strpos($id, 'xri://$dns*') === 0) {
            $id = substr($id, strlen('xri://$dns*'));
        } else if (strpos($id, 'xri://') === 0) {
            $id = substr($id, strlen('xri://'));
        }

        // 7.2.2
        if ($id[0] == '=' ||
            $id[0] == '@' ||
            $id[0] == '+' ||
            $id[0] == '$' ||
            $id[0] == '!') {
            return true;
        }

        // 7.2.3
        if (strpos($id, "://") === false) {
            $id = 'http://' . $id;
        }

        // RFC 3986, 6.2.2.  Syntax-Based Normalization

        // RFC 3986, 6.2.2.2 Percent-Encoding Normalization
        $i = 0;
        $n = strlen($id);
        $res = '';
        while ($i < $n) {
            if ($id[$i] == '%') {
                if ($i + 2 >= $n) {
                    return false;
                }
                ++$i;
                if ($id[$i] >= '0' && $id[$i] <= '9') {
                    $c = ord($id[$i]) - ord('0');
                } else if ($id[$i] >= 'A' && $id[$i] <= 'F') {
                    $c = ord($id[$i]) - ord('A') + 10;
                } else if ($id[$i] >= 'a' && $id[$i] <= 'f') {
                    $c = ord($id[$i]) - ord('a') + 10;
                } else {
                    return false;
                }
                ++$i;
                if ($id[$i] >= '0' && $id[$i] <= '9') {
                    $c = ($c << 4) | (ord($id[$i]) - ord('0'));
                } else if ($id[$i] >= 'A' && $id[$i] <= 'F') {
                    $c = ($c << 4) | (ord($id[$i]) - ord('A') + 10);
                } else if ($id[$i] >= 'a' && $id[$i] <= 'f') {
                    $c = ($c << 4) | (ord($id[$i]) - ord('a') + 10);
                } else {
                    return false;
                }
                ++$i;
                $ch = chr($c);
                if (($ch >= 'A' && $ch <= 'Z') ||
                    ($ch >= 'a' && $ch <= 'z') ||
                    $ch == '-' ||
                    $ch == '.' ||
                    $ch == '_' ||
                    $ch == '~') {
                    $res .= $ch;
                } else {
                    $res .= '%';
                    if (($c >> 4) < 10) {
                        $res .= chr(($c >> 4) + ord('0'));
                    } else {
                        $res .= chr(($c >> 4) - 10 + ord('A'));
                    }
                    $c = $c & 0xf;
                    if ($c < 10) {
                        $res .= chr($c + ord('0'));
                    } else {
                        $res .= chr($c - 10 + ord('A'));
                    }
                }
            } else {
                $res .= $id[$i++];
            }
        }

        if (!preg_match('|^([^:]+)://([^:@]*(?:[:][^@]*)?@)?([^/:@?#]*)(?:[:]([^/?#]*))?(/[^?#]*)?((?:[?](?:[^#]*))?)((?:#.*)?)$|', $res, $reg)) {
            return false;
        }
        $scheme = $reg[1];
        $auth = $reg[2];
        $host = $reg[3];
        $port = $reg[4];
        $path = $reg[5];
        $query = $reg[6];
        $fragment = $reg[7]; /* strip it */

        if (empty($scheme) || empty($host)) {
            return false;
        }

        // RFC 3986, 6.2.2.1.  Case Normalization
        $scheme = strtolower($scheme);
        $host = strtolower($host);

        // RFC 3986, 6.2.2.3.  Path Segment Normalization
        if (!empty($path)) {
            $i = 0;
            $n = strlen($path);
            $res = "";
            while ($i < $n) {
                if ($path[$i] == '/') {
                    ++$i;
                    while ($i < $n && $path[$i] == '/') {
                        ++$i;
                    }
                    if ($i < $n && $path[$i] == '.') {
                        ++$i;
                        if ($i < $n && $path[$i] == '.') {
                            ++$i;
                            if ($i == $n || $path[$i] == '/') {
                                if (($pos = strrpos($res, '/')) !== false) {
                                    $res = substr($res, 0, $pos);
                                }
                            } else {
                                    $res .= '/..';
                            }
                        } else if ($i != $n && $path[$i] != '/') {
                            $res .= '/.';
                        }
                    } else {
                        $res .= '/';
                    }
                } else {
                    $res .= $path[$i++];
                }
            }
            $path = $res;
        }

        // RFC 3986,6.2.3.  Scheme-Based Normalization
        if ($scheme == 'http') {
            if ($port == 80) {
                $port = '';
            }
        } else if ($scheme == 'https') {
            if ($port == 443) {
                $port = '';
            }
        }
        if (empty($path)) {
            $path = '/';
        }

        $id = $scheme
            . '://'
            . $auth
            . $host
            . (empty($port) ? '' : (':' . $port))
            . $path
            . $query;
        return true;
	}

	/**
	 * Stores information about logged in user
	 *
	 * @param string $id user identity URL
	 * @return bool
	 */
	public function setLoggedInUser( $id )
	{
		call_user_func( array (O_Registry::get( "app/classnames/session" ), "setUser"),
				self::getByIdentity( $id ) );
	}

	/**
	 * Returns identity URL of logged in user or false
	 *
	 * @return mixed
	 */
	public function getLoggedInUser()
	{
		$user = call_user_func( array (O_Registry::get( "app/classnames/session" ), "getUser") );
		return $user instanceof O_Dao_ActiveRecord ? $user->identity : null;
	}

	/**
	 * Performs logout. Clears information about logged in user.
	 *
	 * @return bool
	 */
	public function delLoggedInUser()
	{
		call_user_func( array (O_Registry::get( "app/classnames/session" ), "delUser") );
	}
}