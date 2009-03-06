<?php
/**
 * @table o_openid_nonce
 * @field nonce varchar(255) not null
 * @field created int not null
 * @index nonce -unique
 */
class O_OpenId_Consumer_Nonce extends O_Dao_ActiveRecord {
	public function __construct($nonce) {
		$this->nonce = $nonce;
		$this->created = time();

		parent::__construct();
	}

}