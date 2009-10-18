<?php
class O_Form_Check_DateTime extends O_Form_FieldChecker {

	public function check( $createMode = false )
	{
		$arr = $this->producer->getValue();
		if (!is_array( $arr ))
			throw new O_Form_Check_Error( "Wrong value for DateTime field." );
		
		$time = mktime( (int)$arr[ "H" ], (int)$arr[ "i" ], 0, (int)$arr[ "m" ], (int)$arr[ "d" ], 
				(int)$arr[ "Y" ] );
		$this->producer->setValue( $time );
	}
}