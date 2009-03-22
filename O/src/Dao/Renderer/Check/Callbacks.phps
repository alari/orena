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

}