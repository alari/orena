<?php
class O_Form_Check_Preg extends O_Form_FieldChecker {

	public function check( $createMode = false )
	{
		if (!preg_match( "#^".$this->producer->getParams()."$#i", $this->producer->getValue() ))
			throw new O_Form_Check_Error( "Wrong value for ".$this->producer->getFieldName()." (must be ".$this->producer->getParams().")." );
	}
}