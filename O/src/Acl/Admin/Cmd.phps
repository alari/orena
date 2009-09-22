<?php
/**
 * Command part to handle roles editing.
 *
 * @see O_Acl_Admin_Tpl
 *
 * @author Dmitry Kurinskiy
 */
class O_Acl_Admin_Cmd {

	/**
	 * Call this with current command object as a param, return a result
	 *
	 * @example return O_Acl_Admin_Cmd::process($this)
	 * @param O_Command $cmd
	 * @return mixed
	 */
	static public function process( O_Command $cmd )
	{
		Header( "Content-type: text/html; charset=utf-8" );
		
		// Find role to process, if given
		if ($cmd->getParam( "role" )) {
			$role = O_Dao_ActiveRecord::getById( $cmd->getParam( "role" ), 
					O_Registry::get( "app/classnames/acl_role" ) );
		}
		
		// Process ajax request
		if (O_Registry::get( "app/env/request_method" ) == "POST" && isset( $role ) && $role) {
			// Request to show role
			if ($cmd->getParam( "mode" ) == "show") {
				$tpl = $cmd->getTemplate();
				$tpl->role = $role;
				// Prepare actions array
				$actions = O_Db_Query::get( 
						O_Dao_TableInfo::get( O_Registry::get( "app/classnames/acl_action" ) )->getTableName() )->field( 
						"DISTINCT name" )->select()->fetchAll( PDO::FETCH_OBJ );
				foreach ($actions as &$v)
					$v = $v->name;
				$tpl->actions = $actions;
				$tpl->roles = O_Dao_Query::get( O_Registry::get( "app/classnames/acl_role" ) );
				$tpl->setLayoutClass( "O_Html_AsIsLayout" );
				return $tpl;
			}
			// Saving role: actions
			foreach ($cmd->getParam( "actions" ) as $name => $value) {
				switch ($value) {
					case O_Acl_Action::TYPE_ALLOW :
						$role->allow( $name );
					break;
					case O_Acl_Action::TYPE_DENY :
						$role->deny( $name );
					break;
					default :
						$role->clear( $name );
				}
			}
			// Saving role: parent
			if ($cmd->getParam( "parent_role" )) {
				$parent = O_Dao_ActiveRecord::getById( $cmd->getParam( "parent_role" ), 
						O_Registry::get( "app/classnames/acl_role" ) );
				if ($parent && $parent->id != $role->id)
					$role->parent = $parent;
				else
					$parent = null;
			}
			if (!isset( $parent ) || !$parent)
				$role->parent = null;
				// Saving role: visitor
			if ($cmd->getParam( "set_visitor" ) == "yes")
				$role->setAsVisitorRole();
				
			// Simpliest json response
			$response = array ();
			$response[ "status" ] = $role->save() ? "SAVED" : "NO DAO CHANGES";
			echo json_encode( $response );
			return null;
		} elseif (O_Registry::get( "app/env/request_method" ) == "POST" && $cmd->getParam( 
				"new_role" )) {
			O_Acl_Role::getByName( $cmd->getParam( "new_role" ) );
			return $cmd->redirect( O_UrlBuilder::get( O_Registry::get( "app/env/process_url" ) ) );
		}
		
		// Just return the template
		$tpl = $cmd->getTemplate();
		
		$tpl->roles = O_Dao_Query::get( O_Registry::get( "app/classnames/acl_role" ) );
		
		return $tpl;
	}

}