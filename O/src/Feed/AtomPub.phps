<?php

class O_Feed_AtomPub {
	private static $error;

	static public function post( $api_url, $data, $userpwd )
	{
		self::$error = null;
		if (!$data) {
			return self::setError( "No data given" );
		}

		$curl = curl_init( $api_url );
		curl_setopt( $curl, CURLOPT_POST, true );
		curl_setopt( $curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY );
		curl_setopt( $curl, CURLOPT_USERPWD, $userpwd );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $data );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		echo $ret = curl_exec( $curl );
		if (!$ret) {
			return self::error( curl_error( $curl ) );
		}

		$d = new DOMDocument( );
		if (!@$d->loadXml( $ret )) {
			return self::setError( "Cannot load xml data $ret" );
		}

		$return = Array ();

		$return[ "id" ] = $d->getElementsByTagName( "id" )->item( 0 )->textContent;
		$return[ "post_url" ] = "";
		$return[ "edit_url" ] = "";

		foreach ($d->getElementsByTagName( "link" ) as $link) {
			if ($link->getAttribute( "rel" ) == "alternate" && $link->getAttribute( "type" ) == "text/html")
				$return[ "post_url" ] = $link->getAttribute( "href" );
			if ($link->getAttribute( "rel" ) == "service.edit" && strpos(
					$link->getAttribute( "type" ), "atom+xml" ))
				$return[ "edit_url" ] = $link->getAttribute( "href" );
		}

		$return[ "xml" ] = $ret;
		return $return;
	}

	static public function update( $edit_url, $data, $userpwd )
	{
		self::$error = null;
		if (!$data) {
			return self::setError( "No data given" );
		}

		$f = tmpfile();
		fwrite( $f, $data );
		fseek( $f, 0 );

		$curl = curl_init( $edit_url );
		curl_setopt( $curl, CURLOPT_PUT, true );
		curl_setopt( $curl, CURLOPT_INFILE, $f );
		curl_setopt( $curl, CURLOPT_INFILESIZE, strlen( $data ) );

		curl_setopt( $curl, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST );
		curl_setopt( $curl, CURLOPT_USERPWD, $userpwd );

		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		$ret = curl_exec( $curl );

		if (!$ret)
			return self::setError( curl_error( $curl ) );

		return $ret;
	}

	static public function delete( $edit_url, $userpwd )
	{
		$curl = curl_init( $edit_url );
		curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, "DELETE" );

		curl_setopt( $curl, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST );
		curl_setopt( $curl, CURLOPT_USERPWD, $userpwd );

		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		$ret = curl_exec( $curl );

		if (!$ret)
			return self::setError( curl_error( $curl ) );

		return $ret;
	}

	static public function prepareEntry( $title, $url, $published, $data, $updated = null, $id = null, $no_comments = 1 )
	{
		if (!$published)
			$published = time();
		$published = date( "Y-m-d", $published ) . "T" . date( "H:i:s", $published );
		if (!$updated)
			$updated = $published;
		else
			$updated = date( "Y-m-d", $updated ) . "T" . date( "H:i:s", $updated );
		$data = str_replace( array ("\r", "\n"), array ("", ""), $data );
		ob_start();
		?>
<entry xmlns="http://purl.org/atom/ns#">
<title><?=htmlspecialchars( $title )?></title>
<?
		if ($id) {
			?><id><?=$id?></id><?
		}
		if ($no_comments) {
			?>
<mt:allowComments xmlns:mt="http://www.movabletype.org/atom/ns#">0</mt:allowComments><?
		}
		?>
<link rel="alternate" type="text/html" href="<?=$url?>" />
<published><?=$published?></published>
<updated><?=$updated?></updated>
<content type="application/xhtml+xml">
<div xmlns="http://www.w3.org/1999/xhtml">
<?=htmlspecialchars( $data )?></div>
div>
</content>
</entry><?
		return ob_get_clean();
	}

	static private function setError( $errmsg )
	{
		echo self::$error = $errmsg;
		return false;
	}

	static public function getError()
	{
		return self::$error;
	}

}