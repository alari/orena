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
			$registry = O_Registry::get( "acl", $resourse );
			if ($registry instanceof SimpleXMLElement && isset( $registry->Visitor )) {
				$visitor = $registry->Visitor[ 0 ];
				if ($visitor && (string)$visitor[ "role" ]) {
					$access = O_Acl_Role::getByName( (string)$visitor[ "role" ] )->can( $action );
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