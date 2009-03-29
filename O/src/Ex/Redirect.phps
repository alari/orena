<?php
class O_Ex_Redirect extends O_Ex_Error {

	public function __construct( $url = "/" )
	{
		Header( "Location: " . O_UrlBuilder::get( $url ) );
		exit();
	}

}