<?php
/**
 * Interface for ACL users: "can" method with resource context,
 *
 * Registry for resources:
 *
 * app/acl/$class/$field = $role_name
 * if $field is "-visitor" -- it's a rule for visitor
 *
 * @author Dmitry Kurinskiy
 */
interface O_Acl_iUser {

	public function can( $action, O_Dao_ActiveRecord $resource = null );
}