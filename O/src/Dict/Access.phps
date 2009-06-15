<?php
abstract class O_Dict_Access implements ArrayAccess {

	/**
	 * The main method to access dictionaries.
	 *
	 * @param string phrase, may be in "dict:phrase" format
	 * @param [] Other phrase parameters
	 * @return string
	 */
	static public function _()
	{
		$params = func_get_args();
		$dict = O_Dict_Collection::DEFAULT_DICT_SET;
		$phrase = array_shift( $params );
		if (strpos( $phrase, ":" ))
			list ($dict, $phrase) = explode( ":", $phrase, 2 );
		return O_Dict_Collection::getInstance( $dict )->getPhrase( $phrase, $params );
	}

	/**
	 * Returns phrase from default locale
	 *
	 * @param string $name
	 * @return string
	 */
	public function offsetGet( $name )
	{
		return $this->_( $name );
	}

	/**
	 * Does nothing
	 *
	 * @param unknown_type $offset
	 * @param unknown_type $value
	 */
	public function offsetSet( $offset, $value )
	{
		throw new O_Ex_Logic( "Couldn't set phrase via layout." );
	}

	/**
	 * Checks if phrase exists in default locale
	 *
	 * @param string $name
	 * @return string
	 */
	public function offsetExists( $name )
	{
		return (bool)$this->offsetGet( $name );
	}

	/**
	 * Does nothing
	 *
	 * @param unknown_type $offset
	 */
	public function offsetUnset( $offset )
	{
		throw new O_Ex_Logic( "Couldn't remove phrase via layout." );
	}
}