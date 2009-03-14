<?php
/**
 * Simple URL builder for static files and commands.
 *
 * @copyright Dmitry Kourinski
 */
class O_UrlBuilder {

	/**
	 * Returns full URL to command
	 *
	 * @param string $url
	 * @return string
	 */
	static public function get( $url, Array $params = Array() )
	{
		$r = O_Registry::get( "app/env/base_url" );
		if ($r[ strlen( $r ) - 1 ] != "/") {
			O_Registry::set( "app/env/base_url", $r = $r . "/" );
		}
		if (!$url) {
			$url = $r;
		} elseif ($url[ 0 ] == "/") {
			$url = $r . substr( $url, 1 );
		} else {
			$url = $r . $url;
		}
		if (count( $params )) {
			$query_string = "";
			foreach ($params as $k => $v) {
				$query_string .= ($query_string ? "&" : "") . urlencode( $k ) . "=" . urlencode( $v );
			}
			$url .= "?" . $query_string;
		}
		return $url;
	}

	/**
	 * Returns full URL to static file
	 *
	 * @param string $url
	 * @param bool $fw If set to true, framework static root is used
	 * @return string
	 */
	static public function getStatic( $url, $fw = false )
	{
		if ($url[ 0 ] != "/" && $url[ 0 ] != "." && strpos( $url, "http://" ) !== 0)
			return O_Registry::get( ($fw ? "fw" : "app") . "/html/static_root" ) . $url;
		return $url;
	}
}