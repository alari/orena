<?php
/**
 * User pattern with ACL.
 *
 * Resourse context for acl is stored in resourse's "acl" registry section.
 * @see O_Acl_User::getAccessByNode()
 *
 * Classname is stored in "app/classnames/user" registry.
 *
 * @author Dmitry Kurinskiy
 *
 * @field role -has one {classnames/acl_role} -inverse users
 */
class O_Acl_User extends O_Base_User implements O_Acl_iUser {

	private $acl_cache = Array();

	/**
	 * Returns bool for access rule, null if no rule specified
	 *
	 * @param string $action
	 * @param O_Dao_ActiveRecord $resourse
	 * @return bool or null
	 */
	public function can( $action, O_Dao_ActiveRecord $resourse = null )
	{
		$cache_key = $action.($resourse?"/".get_class($resourse).":".$resourse["id"]:"");
		if(array_key_exists($cache_key, $this->acl_cache)){
			return $this->acl_cache[$cache_key];
		}

		// Resourse acl logic delegation
		if($resourse instanceof O_Acl_iResourse) {
			$access = $resourse->aclUserCan($action, $this);
			if(!is_null($access)) {
				return $this->acl_cache[$cache_key] = $access;
			}
		}

		// Role overrides resourse context
		if ($this->role && !is_null( $access = $this->role->can( $action ) )) {
			return $this->acl_cache[$cache_key] = $access;
		}

		// Getting context role for resourse
		if ($resourse) {
			$registry = O_Registry::get( "acl", $resourse );
			if(is_array($registry)) {
				$access = null;
				foreach ($registry as $key=>$params) {
					$access = $this->getAccessByParams( $action, $key, $params, $resourse );
					if (!is_null( $access )){
						return $this->acl_cache[$cache_key] = $access;
					}
				}
			}
		}

		// No rules available et al
		return $this->acl_cache[$cache_key] = null;
	}

	/**
	 * Returns access by rules given in registry
	 *
	 * Nodes:
	 * delegate: target -- use resourse stored in resourse's .target to get access rights
	 * role: name -- set access rules as for this role
	 * user-in field: -- process inner instructions if user is in .field of resourse
	 * (user|resourse) (related)field(operator)value: ... -- take an object to process
	 *
	 * @param string $action
	 * @param string $key
	 * @param string|array $params
	 * @param O_Dao_ActiveRecord $resourse
	 * @return bool or null
	 */
	private function getAccessByParams( $action, $key, $params, O_Dao_ActiveRecord $resourse )
	{
		$is_true = 0;
		if(strpos($key, " ")) {
			list($key, $subkey) = explode(" ", $key, 2);
		}
		if(!is_array($params)) $params = trim($params);

		switch ($key) {
			case "delegate" :
				$res = $resourse->$params;
				return $this->can( $action, $res );
			break;
			case "role" :
				return O_Acl_Role::getByName( $params )->can( $action );
			break;
			case "user-in" :
				$value = $resourse->$subkey;
				// It's an user object
				if ($value instanceof $this) {
					if ($value->id == $this->id) {
						$is_true = 1;
					}
					// It's a relation with many users
				} elseif ($value instanceof O_Dao_Query) {
					if($value->has($this)){
						$is_true = 1;
					}
				}
			break;
			case "user" :
			case "resourse" :
				$obj = $key == "user" ? $this : $resourse;
				$type = null;
				foreach(Array("!=", "==", "=", ">", "<") as $possible_type){
					if(strpos($subkey, $possible_type)) {
						$type = $possible_type;
						list($field, $value) = explode($possible_type, $subkey, 2);
						break;
					}
				}
				if(!$type) {
					throw new O_Ex_Config("Wrong notation for $key ACL directive.");
				}
				$field = rtrim($field);
				$value = ltrim($value);

				if($field[0] == "(" && strpos($field, ")")) {
					list($related, $field) = explode(")", substr($field, 1),2);
					$obj = $obj->{trim($related)};
					$field = ltrim($field);
				}
				$field = $obj[ $field ];

				switch ($type) {
					case ">":
						$is_true = $field > $value;
					break;
					case "<":
						$is_true = $field < $value;
					break;
					case "!=":
						$is_true = $field != $value;
					break;
					default :
						$is_true = $field == $value;
				}
			break;
			default :
				return null;
		}
		if ($is_true) {
			$access = null;
			foreach ($params as $k=>$p) {
				$access = $this->getAccessByParams( $action, $k, $p, $resourse );
				if (!is_null( $access ))
					return $access;
			}
		}
		return null;
	}
}