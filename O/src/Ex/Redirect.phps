<?php
class O_Ex_Redirect extends O_Ex_Error {

	public function __construct( $url = null )
	{
		if (is_null( $url ))
			$url = O_UrlBuilder::get( O_Registry::get( "env/process_url" ) );
		elseif (strpos( $url, "/" ) !== 0 && strpos( $url, "http://" ) !== 0)
			$url = O_UrlBuilder::get( $url );
		Header( "Location: " . $url );
		exit();
	}

}