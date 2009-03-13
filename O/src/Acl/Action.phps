<?php
/**
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
	 * Returns object by its rule
	 *
	 * @param string $name
	 * @param const $type
	 * @return O_Acl_Action
	 */
	static public function getByRule( $name, $type = self::TYPE_ALLOW )
	{
		if (!isset( self::$objs[ $name ][ $type ] )) {
			self::$objs[ $name ][ $type ] = O_Dao_Query::get( __CLASS__ )->test( "name", $name )->test( "type", $type )->getOne();
			if (!self::$objs[ $name ][ $type ]) {
				self::$objs[ $name ][ $type ] = new self( );
				self::$objs[ $name ][ $type ]->name = $name;
				self::$objs[ $name ][ $type ]->type = $type;
				self::$objs[ $name ][ $type ]->save();
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

}