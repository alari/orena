<?php
/**
 * Simple storage for outgoing mails. Commonly not used directly.
 *
 * @see O_Mail_Service
 *
 * @author Dmitry Kurinskiy
 *
 * @table o_mail_message
 *
 * @field to_addr VARCHAR(1023)
 * @field from_addr VARCHAR(1023)
 * @field subject VARCHAR(511)
 * @field add_headers TEXT
 * @field message MEDIUMTEXT
 * @field time INT
 * @field sent TINYINT DEFAULT 0 NOT NULL
 * @field sent_time INT DEFAULT 0
 *
 * @index sent
 */
class O_Mail_Message extends O_Dao_ActiveRecord {

	/**
	 * Puts the message into storage
	 *
	 * @param string $to Adresate email
	 * @param string $from Sender email
	 * @param string $subject Message title
	 * @param string $message Message body
	 * @param string $add_headers Headers information
	 */
	public function __construct( $to, $from, $subject, $message, $add_headers = "Content-type: text/plain; charset=utf-8" )
	{
		parent::__construct();
		$this->to_addr = $to;
		$this->from_addr = $from;
		$this->subject = $subject;
		$this->message = $message;
		$this->add_headers = $add_headers;
		$this->time = time();
		$this->save();
	}

	/**
	 * Sends the message. Updates sent and sent_time fields on success.
	 *
	 * @return bool true on success, false on failure
	 */
	public function send()
	{
		$r = O_Mail_Service::send( $this->to_addr, $this->from_addr, $this->subject,
				$this->message, $this->add_headers );
		$this->sent = $r ? 1 : 0;
		if ($r)
			$this->sent_time = time();
		$this->save();
		return $r;
	}

}