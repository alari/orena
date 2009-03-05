<?php
/**
 * @table o_acl_user
 * @field role -has one {acl/role_class} -inverse users
 * @field session -owns one {session/class_name} -inverse user
 */
class O_Acl_User implements O_Acl_iUser {

	/**
	 * Returns bool for access rule, null if no rule specified
	 *
	 * @param string $action
	 * @param O_Dao_ActiveRecord $resourse
	 * @return bool or null
	 */
	public function can( $action, O_Dao_ActiveRecord $resourse = null )
	{
		// Getting context role for resourse
		if ($resourse) {
			$registry = O_Registry::get( "app/acl/context/" . get_class( $resourse ) );
			if (is_array( $registry )) {
				$access = null;
				foreach ($registry as $field => $role) {
					// Special rule for visitor
					if ($field == "-visitor") {
						continue;
					}
					$value = $resourse->$field;
					// It's an user object
					if ($value instanceof $this) {
						if ($value->id == $this->id) {
							$access = O_Acl_Role::getByName( $role )->can( $action );
						}
						// It's a relation with many users
					} elseif ($value instanceof O_Dao_Query) {
						if (isset( $value[ $this->id ] ) && $value[ $this->id ] instanceof $this) {
							$access = O_Acl_Role::getByName( $role )->can( $action );
						}
					}
					// We've found access rule
					if (!is_null( $access ))
						return $access;
				}
			}
		}
		// No resourse or no special rules for it
		if ($this->role)
			return $this->role->can( $action );
			
		// No rules available et al
		return null;
	}

}