<?php
class O_Acl_Visitor extends O_Base_Visitor implements O_Acl_iUser {

	/**
	 * Returns bool if a rule specified, null elsewhere
	 *
	 * @param string $action
	 * @param O_Dao_ActiveRecord $resourse
	 * @return bool or null
	 */
	public function can( $action, O_Dao_ActiveRecord $resourse = null )
	{
		// For resourse try to get access rule from -visitor role
		if ($resourse) {
			$registry = O_Registry::get( "app/acl/context/" . get_class( $resourse ) . "/-visitor" );
			if ($registry) {
				$access = O_Acl_Role::getByName( $registry )->can( $action );
				if (!is_null( $access ))
					return $access;
			}
		}
		// Rule by visitor role
		if (O_Acl_Role::getVisitorRole()) {
			return O_Acl_Role::getVisitorRole()->can( $action );
		}
		// No rules specified for this action
		return null;
	}
}