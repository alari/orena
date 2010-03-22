<?php

abstract class O_Dao_Field_Bases {

	/**
	 * Reloads field's cache for the object
	 *
	 * @param int $obj_id
	 */
	public function reload( $obj_id )
	{
	}

	/**
	 * No special actions should be done on atomic field deletion
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @access private
	 */
	public function deleteThis( O_Dao_ActiveRecord $obj, $fieldValue = null )
	{
		return;
	}

	/**
	 * Adds field to CREATE query
	 *
	 * @param O_Db_Query $query
	 * @access private
	 */
	public function addFieldTypeToQuery( O_Db_Query $query )
	{
	}

	/**
	 * Returns prepared target class name
	 *
	 * @param string $targetBase
	 * @return string
	 */
	protected function getTargetByBase( $targetBase )
	{
		if ($targetBase[ 0 ] == "{" && $targetBase[ strlen( $targetBase ) - 1 ] == "}") {
			return O_Registry::get( "app/" . substr( $targetBase, 1, -1 ) );
		} elseif ($targetBase[ 0 ] == "_") {
			return O_Registry::get( "_prefix" ) . "_Mdl" . $targetBase;
		} else {
			return $targetBase;
		}
	}

}