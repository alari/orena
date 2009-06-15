<?php
class O_Dict_Collection {
	protected $dicts = Array ();

	protected static $instances = Array ();

	const DEFAULT_DICT_SET = "default";
	const DEFAULT_DICT_CLASS = "O_Dict_Storage_IniFile";

	/**
	 * Returns Locale instance -- with dictionaries configured
	 *
	 * @param string $name
	 * @param array $params
	 * @return O_Dict_Collection
	 */
	static public function getInstance( $name = self::DEFAULT_DICT_SET, $params = null )
	{
		if (!isset( self::$instances[ $name ] )) {
			if (!$params)
				$params = O_Registry::get( "app/dict/" . $name );
			if (!is_array( $params ) || !count( $params )) {
				throw new O_Ex_Config( "Locale is used but not described in configs: '$name'" );
			}
			self::$instances[ $name ] = new self( $params );
		}
		return self::$instances[ $name ];
	}

	/**
	 * Creates new dictionary. Accessed only by factory project
	 *
	 * @param Array $params
	 */
	protected function __construct( $params )
	{
		foreach ($params as $k => $v) {
			$lang = isset( $v[ "lang" ] ) ? $v[ "lang" ] : null;
			$class = isset( $v[ "class" ] ) ? $v[ "class" ] : self::DEFAULT_DICT_CLASS;
			$params = $v;
			if (!class_exists( $class, true )) {
				throw new O_Ex_Config( "Dictionary class not given for $k dictionary." );
			}
			array_unshift( $this->dicts, new $class( $lang, $params ) );
		}
		if (!count( $this->dicts )) {
			throw new O_Ex_Config( "No dictionaries described for locale." );
		}
	}

	/**
	 * Returns phrase by its technical name (and any number of params)
	 *
	 * @param string $name
	 * @param array $params
	 * @return string
	 */
	public function getPhrase( $name, Array $params = Array() )
	{
		$phrase = null;
		if (count( $params ) == 1 && is_integer( current( $params ) )) {
			$num = current( $params );
			if (!$num)
				$n_name .= "%none";
			if (1 == $num)
				$n_name .= "%singular";
			if ($num > 1)
				$n_name .= "%plural";
			$phrase = self::getPhrase( $n_name );
		}
		if (!$phrase) {
			foreach ($this->dicts as $v) {
				$phrase = $v->getPhrase( $name );
				if ($phrase)
					break;
			}
			if (!$phrase)
				return $name;
		}
		if (count( $params )) {
			array_unshift( $params, $phrase );
			return call_user_func_array( "sprintf", $params );
		}
		return $phrase;
	}

	/**
	 * Adds additional dictionary to locale, which would override all others
	 *
	 * @param O_Dict_Storage $dict
	 */
	public function addDictionary( O_Dict_Storage $dict )
	{
		array_unshift( $this->dicts, $dict );
	}

	/**
	 * Returns all bundled dictionaries
	 *
	 * @return O_Dict_Storage[]
	 */
	public function getDictionaries()
	{
		return $this->dicts;
	}

}