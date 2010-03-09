<?php
/**
 * Class for Visitor pseudo-user with ACL support
 *
 * Override visitor's class in registry if you want to extend it:
 * "app/classnames/visitor"
 *
 * @author Dmitry Kurinskiy
 */
class O_Acl_Visitor extends O_Base_Visitor implements O_Acl_iUser {

	/**
	 * Returns bool if a rule specified, null elsewhere
	 *
	 * @param string $action
	 * @param O_Dao_ActiveRecord $resource
	 * @return bool or null
	 */
	public function can( $action, O_Dao_ActiveRecord $resource = null )
	{
		// For resource try to get access rule from -visitor role
		if ($resource) {
			$registry = O_Registry::get( "acl", $resource );
			if (is_array($registry) && isset( $registry["visitor"] )) {
				$visitor = $registry["visitor"];
				if ($visitor) {
					$access = O_Acl_Role::getByName( $visitor )->can( $action );
					if (!is_null( $access ))
						return $access;
				}
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