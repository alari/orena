<?php

class O_UrlBuilder {
	/**
	 * Returns full URL to command
	 *
	 * @param string $url
	 * @return string
	 */
	static public function get($url) {
		$r = O_Registry::get("app/env/base_url");
		if($r[strlen($r)-1] != "/") {
			O_Registry::set("app/env/base_url", $r."/");
		}
		if(!$url) return $r;
		if($url[0] == "/") {
			return $r.substr($url, 1);
		}
		return $r.$url;
	}

	/**
	 * Returns full URL to static file
	 *
	 * @param string $url
	 * @param bool $fw If set to true, framework static root is used
	 * @return string
	 */
	static public function getStatic($url, $fw=false) {
		if ($url[ 0 ] != "/" && $url[ 0 ] != "." && strpos( $url, "http://" ) !== 0)
			return O_Registry::get( ($fw ? "fw" : "app") . "/html/static_root" ) . $url;
		return $url;
	}


}