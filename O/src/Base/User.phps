<?php
/**
 * Default user pattern. Contains no info about auth method, but required by any O_ session class.
 *
 * Extend it for further use.
 * Classname is stored in "app/classnames/user" registry.
 *
 * @author Dmitry Kurinskiy
 *
 * @table o_base_user
 * @field session -owns many {classnames/session} -inverse user
 */
class O_Base_User extends O_Dao_ActiveRecord {

}