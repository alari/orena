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
		//curl_setopt($curl, CURLOPT_HTTPHEADER, Array("Content-type: application/atom+xml"));
		$ret = curl_exec( $curl );
		if (!$ret) {
			return self::error( curl_error( $curl ) );
		}

		if (strpos( $ret, "<?xml" ) !== 0) {
			return self::setError( "Invalid response: $ret" );
		}

		$return = Array ();

		preg_match( "#<id>([^<]+)</id>#", $ret, $m );

		$return[ "id" ] = isset( $m[ 1 ] ) ? $m[ 1 ] : "";
		$return[ "xml" ] = $ret;
		$return[ "post_url" ] = "";
		$return[ "edit_url" ] = "";

		preg_match_all( "#<link ([^>]+)/>#", $ret, $m );
		foreach ($m[ 1 ] as $link) {
			preg_match_all( "#(rel|type|href)=([\\\"|'])([^\\\"' ]+)(\\2)#", $link, $l );
			$params = Array ();
			foreach (array_keys( $l[ 0 ] ) as $k) {
				$params[ $l[ 1 ][ $k ] ] = $l[ 3 ][ $k ];
			}
			if (isset( $params[ "rel" ] ) && isset( $params[ "type" ] ) && isset(
					$params[ "href" ] )) {
				if ($params[ "rel" ] == "alternate" && $params[ "type" ] == "text/html")
					$return[ "post_url" ] = $params[ "href" ];
				if ($params[ "rel" ] == "service.edit" && strpos( $params[ "type" ], "atom+xml" ))
					$return[ "edit_url" ] = $params[ "href" ];
			}
		}
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
<entry xmlns="http://www.w3.org/2005/Atom">
<title><?=htmlspecialchars( $title )?></title>
<?
		if ($id) {
			?><id><?=$id?></id><?
		}
		if ($no_comments) {
			?>
<allowComments xmlns="http://www.movabletype.org/atom/ns#">0</mt:allowComments><?
		}
		?>
<link rel="alternate" type="text/html" href="<?=$url?>" />
<published><?=$published?></published>
<updated><?=$updated?></updated>
<content type="html">
<?=htmlspecialchars($data)?>
</content>
</entry><?
		return ob_get_clean();
	}

	static private function setError( $errmsg )
	{
		self::$error = $errmsg;
		return false;
	}

	static public function getError()
	{
		return self::$error;
	}

}