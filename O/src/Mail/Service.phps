<?php

class O_Mail_Service {

	static public function addToQueue( $to, $from, $subject, $message, $add_headers = "Content-type: text/plain; charset=utf-8" )
	{
		if ($to && $from)
			new O_Mail_Message( $to, $from, $subject, $message, $add_headers );
	}

	static public function send( $to, $from, $subject, $message, $add_headers = "Content-type: text/plain; charset=utf-8" )
	{
		return mail( $to, $subject, $message, "From: $from\r\nTo: $to\r\n" . $add_headers );
	}

	static public function handleQueue()
	{
		foreach (O_Dao_Query::get( "O_Mail_Message" )->test( "sent", 0 ) as $msg) {
			$msg->send();
		}
	}

}