<?php
/**
 * Default implementation of user roles for ACL.
 *
 * Includes role for visitor, extending roles.
 *
 * @author Dmitry Kurinskiy
 *
 * @table o_acl_role
 * @field parent -has one {classnames/acl_role} -inverse childs
 * @field childs -has many {classnames/acl_role} -inverse parent
 * @field name varchar(64) not null
 * @field users -has many {classnames/user} -inverse role
 * @field actions -has many {classnames/acl_action} -inverse roles
 * @field visitor_role tinyint default 0
 * @index name
 */
class O_Acl_Role extends O_Dao_ActiveRecord {

	/**
	 * Cached visitor role
	 *
	 * @var O_Acl_Role
	 */
	protected static $visitor_role = null;

	/**
	 * Roles by its names
	 *
	 * @var O_Acl_Role[]
	 */
	protected static $objs = Array ();

	/**
	 * Checks role access. Returns null if no rules is set for role, bool otherwise
	 *
	 * @param string $action
	 * @return bool or null
	 */
	public function can( $action )
	{
		foreach ($this->actions as $act) {
			if ($act->name == $action) {
				return $act->getAccess();
			}
		}
		if ($this->parent)
			return $this->parent->can( $action );
		O_Acl_Action::getByRule( $action );
		return null;
	}

	/**
	 * Allows the action for role
	 *
	 * @param string $action
	 */
	public function allow( $action )
	{
		$this->clear( $action );
		$this->actions[] = call_user_func_array( array (O_Acl_Action::getClassName(), "getByRule"),
				array ($action, O_Acl_Action::TYPE_ALLOW) );
	}

	/**
	 * Denies the action for role
	 *
	 * @param string $action
	 */
	public function deny( $action )
	{
		$this->clear( $action );
		$this->actions[] = call_user_func_array( array (O_Acl_Action::getClassName(), "getByRule"),
				array ($action, O_Acl_Action::TYPE_DENY) );
	}

	/**
	 * Remove information about action from role (inherit access rule for it from parent role)
	 *
	 * @param string $action
	 */
	public function clear( $action )
	{
		foreach ($this->actions as $act) {
			if ($act->name == $action) {
				$this->actions->remove( $act );
			}
		}
	}

	/**
	 * Returns action status for current role (without parents)
	 *
	 * @param string $action
	 * @return const
	 */
	public function getActionStatus( $action )
	{
		foreach ($this->actions as $act) {
			if ($act->name == $action) {
				return $act->type;
			}
		}
		return "clear";
	}

	/**
	 * Set current role as visitor's role
	 *
	 */
	public function setAsVisitorRole()
	{
		O_Dao_Query::get( self::getClassName() )->field( "visitor_role", 0 )->update();
		$this->visitor_role = 1;
		$this->save();
		self::$visitor_role = $this;
	}

	/**
	 * Returns visitor role object
	 *
	 * @return O_Acl_Role
	 */
	static public function getVisitorRole()
	{
		if (!self::$visitor_role) {
			self::$visitor_role = O_Dao_Query::get( self::getClassName() )->test( "visitor_role",
					1 )->getOne();
		}
		return self::$visitor_role;
	}

	/**
	 * Creates new role
	 *
	 * @param string $name
	 */
	public function __construct( $name )
	{
		$this->name = $name;
		parent::__construct();
	}

	/**
	 * Returns role by its name, creates it on demand
	 *
	 * @param string $name
	 * @return O_Acl_Role
	 */
	static public function getByName( $name )
	{
		$class = self::getClassName();
		if (!isset( self::$objs[ $name ] )) {
			self::$objs[ $name ] = O_Dao_Query::get( $class )->test( "name", $name )->getOne();
			if (!self::$objs[ $name ]) {
				self::$objs[ $name ] = new $class( $name );
			}
		}
		return self::$objs[ $name ];
	}

	/**
	 * Returns current classname of roles DAO
	 *
	 * @return string
	 */
	static public function getClassName()
	{
		return O_Registry::get( "app/classnames/acl_role" );
	}

}