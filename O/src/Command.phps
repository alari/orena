<?php
abstract class O_Command {

	final public function run()
	{
		try {
			if (method_exists( $this, "isAuthenticated" ) && !$this->isAuthenticated())
				throw new Exception( "Access denied." );
			$result = $this->process();
		}
		catch (Exception $e) {
			// TODO: add correct errors middleware
			echo "Command error<br/><pre>", $e, "</pre>";
			return;
		}
		if ($result instanceof O_Http_Response) {
			// TODO: process simple http response
			return;
		}
		if ($result instanceof O_Html_Template) {
			$result->display();
			return;
		}
		echo $result;
	}

	abstract public function process();

	public function redirect( $href )
	{
		// TODO: add redirect shortcut
		Header( "Location: $href" );
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
			throw new Exception( "Cannot find template class for current command." );
		$tpl = new $class( );
		if ($tpl instanceof O_Html_Template)
			return $tpl;
		throw new Exception( "Invalid template called for current command." );
	}

}