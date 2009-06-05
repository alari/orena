<?php

class O_Locale_Dictionary_IniFile extends O_Locale_Dictionary {
	
	protected $phrases;
	protected $techPhrases;
	protected $initiated;
	protected $techInitiated;

	/**
	 * Returns phrase by its name
	 *
	 * @param string $name
	 * @return string
	 */
	public function getPhrase( $name )
	{
		if (!$this->initiated)
			$this->init();
		return array_key_exists( $name, $this->phrases ) ? $this->phrases[ $name ] : "";
	}

	/**
	 * Returns all phrases in dictionary
	 *
	 * @return Array name=>value
	 */
	public function getPhrases()
	{
		if (!$this->initiated)
			$this->init();
		return $this->phrases;
	}

	/**
	 * Returns technical description of the phrase (suitable for making dict-modify GUI)
	 *
	 * @param string $name
	 * @return string
	 */
	public function getTechPhrase( $name )
	{
		if (!$this->techInitiated)
			$this->init( 1 );
		return array_key_exists( $name, $this->techPhrases ) ? $this->techPhrases[ $name ] : "";
	}

	/**
	 * Tries to set phrase
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function setPhrase( $name, $value )
	{
		throw new O_Ex_Critical( "Cannot override phrase in ini-based dictionary." );
	}

	/**
	 * Tries to set technical description of the phrase
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function setTechPhrase( $name, $value )
	{
		throw new O_Ex_Critical( "Cannot override phrase in ini-based dictionary." );
	}

	/**
	 * Loads phrases from file. Builds filename from params["filebase"].(?"$lang.").(?"tech.")."ini"
	 *
	 * @example O/Locale/Storage/ru.ini would be made from "O/Locale/Storage/" when current lang is "ru"
	 * @param unknown_type $tech
	 */
	private function init( $tech = 0 )
	{
		if (!isset( $this->params[ "filebase" ] )) {
			throw new O_Ex_Config( "File is not specified for ini-based dictionary." );
		}
		$file = $this->params[ "filebase" ];
		if ($this->lang)
			$file .= $lang . ".";
		if ($tech)
			$file .= "tech.";
		$file .= "ini";
		if (!is_file( $file ) || !is_readable( $file )) {
			throw new O_Ex_Config( "Dictionary file not found: $file" );
		}
		
		if ($tech) {
			$this->techPhrases = parse_ini_file( $file, false );
			$this->techInitiated = 1;
		} else {
			$this->phrases = parse_ini_file( $file, false );
			$this->initiated = 1;
		}
	}
}