<?php
/**
 * Registry for resourses:
 *
 * app/acl/context/$class/$field = $role_name
 * if $field is "-visitor" -- it's a rule for visitor
 *
 */
interface O_Acl_iUser {

	public function can( $action, O_Dao_ActiveRecord $resourse = null );
}