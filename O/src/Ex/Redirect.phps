<?php

class O_Ex_Redirect extends Exception {

	public function __construct( $url = "/" )
	{
		Header( "Location: " . O_UrlBuilder::get( $url ) );
		exit();
	}

}