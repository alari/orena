<?php

class O_Dao_Renderer_Check_Callbacks {

	/**
	 * The simpliest html purifier
	 *
	 * @param O_Dao_Renderer_Check_Params $params
	 */
	static public function htmlPurifier( O_Dao_Renderer_Check_Params $params )
	{
		// TODO: add purifier configuration
		$purifier = new HTMLPurifier( );
		$params->setNewValue( $purifier->purify( $params->newValue() ) );
	}

	/**
	 * Creates integer timestamp from date array
	 *
	 * @param O_Dao_Renderer_Check_Params $params
	 */
	static public function timestamp( O_Dao_Renderer_Check_Params $params )
	{
		$arr = $params->newValue();
		if (!is_array( $arr ))
			throw new O_Dao_Renderer_Check_Exception( "Wrong value for timestamp field." );
		
		$time = mktime( (int)$arr[ "H" ], (int)$arr[ "i" ], 0, (int)$arr[ "m" ], (int)$arr[ "d" ], 
				(int)$arr[ "Y" ] );
		$params->setNewValue( $time );
	}

}