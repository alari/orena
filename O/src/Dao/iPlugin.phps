<?php
/**
 * Interface to use with O_Dao_ActiveRecord plugins.
 *
 * Such plugins provides both fields and methods injections.
 * To add fields to an existent O_Dao_ActiveRecord class, just make a comment for a plugin class in Dao style.
 * @see O_Dao_ActiveRecord
 *
 * To inject methods, add static public functions with at least one argument to the class body.
 * Those functions must get active record object as first parameter, and have name starting with "i_"
 *
 * To register plugin and enable it, use O_Registry directive
 * @see O_Registry::add()
 * Just add plugin class name to registry "app/dao/${BASE_CLASSNAME}/plugins" key.
 *
 * @see O_Dao_FieldInfo::__construct()
 *
 * Notify that you must register all plugins BEFORE base class will be loaded (and handled by O_Dao_TableInfo).
 *
 * @author Dmitry Kourinski
 */
interface O_Dao_iPlugin {
}
