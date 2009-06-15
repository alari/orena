<?php

class O_Dict_Storage_DbTable extends O_Dict_Storage {
	
	protected $phrases;
	protected $techPhrases;
	protected $initiated;
	protected $techInitiated;
	protected $tableName;

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
		$q = O_Db_Query::get( $this->tableName )->field( "phrase", $value )->test( "name", $name );
		if ($this->lang)
			$q->test( "lang", $this->lang );
		if (!$q->update())
			$q->insert();
		$this->phrases[ $name ] = $value;
	}

	/**
	 * Tries to set technical description of the phrase
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function setTechPhrase( $name, $value )
	{
		$q = O_Db_Query::get( $this->tableName )->field( "tech_phrase", $value )->test( "name", 
				$name );
		if ($this->lang)
			$q->test( "lang", $this->lang );
		if (!$q->update())
			$q->insert();
		$this->techPhrases[ $name ] = $value;
	}

	/**
	 * Loads phrases from database
	 *
	 * @param bool $tech
	 */
	private function init( $tech = 0 )
	{
		if (!isset( $this->params[ "table" ] )) {
			throw new O_Ex_Config( "Table name not specified for DB-stored dictionary." );
		}
		$this->tableName = $this->params[ "table" ];
		
		$q = O_Db_Query::get( $this->tableName );
		if (!$q->tableExists()) {
			$q->field( "phrase", "TEXT" )->field( "tech_phrase", "TEXT" )->field( "lang", 
					"VARCHAR(16) DEFAULT ''" );
			$q->field( "name", "VARCHAR(64) NOT NULL" );
			$q->index( "name,lang", "UNIQUE" );
			$q->create();
			$q->clearFields();
		}
		$q->field( "name" );
		if ($tech) {
			foreach ($q->field( "tech_phrase" )->select() as $l) {
				$this->techPhrases[ $l[ "name" ] ] = $l[ "tech_phrase" ];
			}
			$this->techInitiated = 1;
		} else {
			foreach ($q->field( "phrase" )->select() as $l) {
				$this->techPhrases[ $l[ "name" ] ] = $l[ "phrase" ];
			}
			$this->initiated = 1;
		}
	}
}