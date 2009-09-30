<?php
/**
 * Class to manage sendmail queue.
 *
 * @author Dmitry Kurinskiy
 */
class O_Mail_Service {

	/**
	 * Adds message to queue to be sent later
	 *
	 * @param string $to Adresate email
	 * @param string $from Sender email
	 * @param string $subject Message title
	 * @param string $message Message body
	 * @param string $add_headers Headers information
	 */
	static public function addToQueue( $to, $from, $subject, $message, $add_headers = "Content-type: text/plain; charset=utf-8" )
	{
		if ($to && $from)
			new O_Mail_Message( $to, $from, $subject, $message, $add_headers );
	}

	/**
	 * Sends message immidiately, doesn't store it anywhere
	 *
	 * @param string $to Adresate email
	 * @param string $from Sender email
	 * @param string $subject Message title
	 * @param string $message Message body
	 * @param string $add_headers Headers information
	 * @return bool true on success, false on failure
	 */
	static public function send( $to, $from, $subject, $message, $add_headers = "Content-type: text/plain; charset=utf-8" )
	{
		return mail( $to, $subject, $message, "From: $from\r\nTo: $to\r\n" . $add_headers );
	}

	/**
	 * Sends unsent messages from queue
	 *
	 * @return Array (id=>string) information about sending results
	 */
	static public function handleQueue()
	{
		$result = Array ();
		foreach (O_Dao_Query::get( "O_Mail_Message" )->test( "sent", 0 ) as $msg) {
			$result[ $msg->id ] = $msg->send() ? "Success" : "Failure (sender: $msg->from_addr, adresate: $msg->to_addr)";
		}
		return $result;
	}

}