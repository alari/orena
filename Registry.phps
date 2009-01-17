<?php

class Registry {
	// HARDCODE
	static private $registry = Array(
		"app/static_root" => "./static/",
		"engine/static_root" => "./static/"
	);

	static public function get($key) {
		return isset(self::$registry[$key]) ? self::$registry[$key] : null;
	}

}