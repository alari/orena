<?php
abstract class O_Command {

	final public function run()
	{
		try {
			if (method_exists( $this, "isAuthenticated" ) && !$this->isAuthenticated())
				throw new O_Ex_AccessDenied( );
			$result = $this->process();
		}
		catch (Exception $e) {
			if (method_exists( $this, "catchEx" )) {
				$result = $this->catchEx( $e );
			} else {
				throw $e;
			}
		}
		if ($result instanceof O_Html_Template) {
			$result->display();
			return;
		}
		echo $result;
	}

	abstract public function process();

	public function redirect( $href = null )
	{
		// TODO: add redirect shortcut
		if (is_null( $href ))
			$href = O_UrlBuilder::get( O_Registry::get( "app/env/process_url" ) );
		Header( "Location: $href" );
		return null;
	}

	public function getParam( $name, $defaultValue = null )
	{
		$v = O_Registry::get( "app/env/params/$name" );
		return is_null( $v ) ? $defaultValue : $v;
	}

	public function getParams()
	{
		return O_Registry::get( "app/env/params" );
	}

	/**
	 * Finds and returns template for current command
	 *
	 * @param string $tpl
	 * @return O_Html_Template
	 * @throws Exception
	 */
	public function getTemplate( $tpl = null )
	{
		preg_match( "#([_a-z]+_)Cmd(_[_a-z]+)#i", get_class( $this ), $matches );
		$class = $matches[ 1 ] . "Tpl" . ($tpl ? $tpl : $matches[ 2 ]);
		if (!class_exists( $class ))
			throw new O_Ex_PageNotFound( "Cannot find template class for current command." );
		$tpl = new $class( );
		if ($tpl instanceof O_Html_Template)
			return $tpl;
		throw new O_Ex_Critical( "Invalid template called for current command." );
	}

}