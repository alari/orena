<?php
class O_Acl_Session extends O_Base_Session {
	/**
	 * Checks current user access
	 *
	 * @param string $action
	 * @param O_Dao_ActiveRecord $resourse
	 * @param string $id
	 * @return bool or null
	 */
	static public function can( $action, O_Dao_ActiveRecord $resourse = null, $id = null )
	{
		return self::getUser( $id ) instanceof O_Acl_iUser && self::getUser( $id )->can( $action, $resourse );
	}
}