<?php
/**
 * @table o_mail_message
 *
 * @field to VARCHAR(1023)
 * @field from VARCHAR(1023)
 * @field subject VARCHAR(511)
 * @field add_headers TEXT
 * @field message MEDIUMTEXT
 * @field time INT
 * @field sent TINYINT DEFAULT 0 NOT NULL
 *
 * @index sent
 */
class O_Mail_Message extends O_Dao_ActiveRecord {

	public function __construct( $to, $from, $subject, $message, $add_headers = "Content-type: text/plain; charset=utf-8" )
	{
		parent::__construct();
		$this->to = $to;
		$this->from = $from;
		$this->subject = $subject;
		$this->message = $message;
		$this->add_headers = $add_headers;
		$this->save();
	}

	public function send()
	{
		$r = O_Mail_Service::send( $this->to, $this->from, $this->subject, $this->message,
				$this->add_headers );
		$this->sent = $r ? 1 : 0;
		$this->save();
	}

}