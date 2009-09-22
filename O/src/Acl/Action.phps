<?php
/**
 * Default database model for ACL actions. Usually extending is not needed.
 *
 * @author Dmitry Kurinskiy
 *
 * @table o_acl_action
 * @field name varchar(64) not null
 * @field type enum('allow','deny') default 'allow'
 * @field roles -has many {classnames/acl_action} -inverse actions
 * @index name
 */
class O_Acl_Action extends O_Dao_ActiveRecord {
	private static $objs = array ();
	
	const TYPE_ALLOW = "allow";
	const TYPE_DENY = "deny";

	/**
	 * Creates new action
	 *
	 * @param string $name
	 * @param const $type
	 * @access private
	 */
	public function __construct( $name, $type )
	{
		$this->name = $name;
		$this->type = $type;
		parent::__construct();
		;
	}

	/**
	 * Returns object by its rule
	 *
	 * @param string $name
	 * @param const $type
	 * @return O_Acl_Action
	 */
	static public function getByRule( $name, $type = self::TYPE_ALLOW )
	{
		$class = self::getClassName();
		if (!isset( self::$objs[ $name ][ $type ] )) {
			self::$objs[ $name ][ $type ] = O_Dao_Query::get( $class )->test( "name", $name )->test( 
					"type", $type )->getOne();
			if (!self::$objs[ $name ][ $type ]) {
				self::$objs[ $name ][ $type ] = new $class( $name, $type );
			}
		}
		return self::$objs[ $name ][ $type ];
	}

	/**
	 * Returns true for allow and false for deny
	 *
	 * @return bool
	 */
	public function getAccess()
	{
		return $this->type == self::TYPE_ALLOW;
	}

	/**
	 * Returns current classname of actions DAO
	 *
	 * @return string
	 */
	static public function getClassName()
	{
		return O_Registry::get( "app/classnames/acl_action" );
	}

}