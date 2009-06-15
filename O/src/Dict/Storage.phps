<?php
abstract class O_Dict_Storage {
	protected $lang;
	protected $params;

	public function __construct( $lang, $params )
	{
		$this->lang = $lang;
		$this->params = $params;
	}

	abstract public function getPhrase( $name );

	abstract public function getTechPhrase( $name );

	abstract public function setPhrase( $name, $value );

	abstract public function setTechPhrase( $name, $value );

	abstract public function getPhrases();

}