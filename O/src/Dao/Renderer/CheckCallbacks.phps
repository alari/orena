<?php

class O_Dao_Renderer_CheckCallbacks {

	static public function htmlPurifier( &$value )
	{
		// TODO: add purifier configuration
		$purifier = new HTMLPurifier( );
		$value = $purifier->purify( $value );
		echo "purified";
	}

}