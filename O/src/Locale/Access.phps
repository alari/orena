<?php

abstract class O_Locale_Access implements ArrayAccess {

	static public function _() {
		$loc = O_Locale::getInstance();
		$params = func_get_args();
		return call_user_func_array( array($loc, "getPhrase"), $params );
	}


	/**
	 * Returns phrase from default locale. First argument is name
	 *
	 * @return string
	 */
	protected function getPhrase()
	{
		$loc = O_Locale::getInstance();
		$params = func_get_args();
		return call_user_func_array( array($loc, "getPhrase"), $params );
	}

	/**
	 * Returns phrase from default locale
	 *
	 * @param string $name
	 * @return string
	 */
	public function offsetGet( $name )
	{
		return O_Locale::getInstance()->getPhrase( $name );
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