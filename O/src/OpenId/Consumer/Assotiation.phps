<?php
/**
 * @table o_openid_assotiation
 * @field url varchar(255) not null
 * @field handle varchar(255) not null
 * @field mac_func varchar(16) not null
 * @field secret varchar(255) not null
 * @field expires int not null
 * @index url -unique
 */
class O_OpenId_Consumer_Assotiation extends O_Dao_ActiveRecord {
	public function __construct($url, $handle, $macFunc, $secret, $expires) {
		$this->url = $url;
		$this->handle = $handle;
		$this->mac_func = $macFunc;
		$this->secret = $secret;
		$this->expires = $expires;

		parent::__construct();
	}

}