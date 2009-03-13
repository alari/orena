<?php
/**
 * @table o_openid_trustedsite
 * @field user -has one {classnames/user} -inverse trusted_sites
 * @field site varchar(255)
 * @field data text
 */
class O_OpenId_Provider_TrustedSite extends O_Dao_ActiveRecord {

}