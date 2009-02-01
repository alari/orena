<?php
/**
 * @field plugin_field text
 */
class Test_Models_CorePlugin implements O_Dao_iPlugin {

	static protected function i_notInjection( $a )
	{
		return $a;
	}

	static public function i_notInjection2()
	{
		return 1;
	}

	static public function i_injection( $obj )
	{
		return $obj->id;
	}
}