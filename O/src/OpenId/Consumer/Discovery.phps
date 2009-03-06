<?php
/**
 * @table o_openid_discovery
 * @field disc_id varchar(255) not null
 * @field real_id varchar(255) not null
 * @field server varchar(255) not null
 * @field version float default 0
 * @field expires int not null
 * @index disc_id -unique
 */
class O_OpenId_Consumer_Discovery extends O_Dao_ActiveRecord {
	public function __construct($id, $realId, $server, $version, $expires) {
		$this->disc_id = $id;
		$this->real_id = $realId;
		$this->server = $server;
		$this->version = $version;
		$this->expires = $expires;

		parent::__construct();
	}

}