<?php

interface O_Dao_Field_iRelation {

	/**
	 * FieldInfo of reverse field
	 *
	 * @return O_Dao_FieldInfo
	 * @access private
	 */
	public function getInverse();

	/**
	 * Returns relation target classname
	 *
	 * @return string
	 */
	public function getTargetClass();

}