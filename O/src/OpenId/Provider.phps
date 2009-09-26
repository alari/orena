<?php
class O_OpenId_Provider extends Auth_OpenID_Server {
	static protected $instance;

	static public function getInstance($storage, $url) {
		return self::$instance ? self::$instance : new self($storage, $url);
	}

}