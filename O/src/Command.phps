<?php
/**
 * Main command abstraction.
 * Provides authentication control, exceptions catching middleware, response displaying.
 *
 * To use acl via registry:
 * Way #1: add "can" attribute to any registry node with O_Dao_ActiveRecord inside, type
 * action name in it.
 * Way #2: add registry to key "app/cmd/can", format for values: $action#$resourse_registry,
 * resourse registry key is not required.
 *
 * @author Dmitry Kurinskiy
 */
abstract class O_Command extends O_Dict_Access {

	/**
	 * Runs the command, processes result
	 *
	 */
	final public function run()
	{
		try {
			if (!$this->isAuthenticated())
				throw new O_Ex_AccessDenied( );
			$result = $this->process();
		}
		catch (Exception $e) {
			$result = $this->catchEx( $e );
		}
		if ($result instanceof O_Html_Template) {
			$result->display();
			return;
		}
		echo $result;
	}

	/**
	 * To be overriden: command exceptions catcher
	 *
	 * @param Exception $e
	 */
	protected function catchEx( Exception $e )
	{
		throw $e;
	}

	/**
	 * Checks if user is authenticated to access this command
	 *
	 */
	protected function isAuthenticated()
	{
		$can = O_Registry::get( "app/cmd/can" );
		if (is_array( $can )) {
			foreach ($can as $acl) {
				list ($action, $resourse) = strpos( $acl, "#" ) ? explode( "#", $acl, 2 ) : array (
																									$acl, 
																									null);
				if ($resourse)
					$resourse = O_Registry::get( $resourse );
				if (!$resourse instanceof O_Dao_ActiveRecord)
					$resourse = null;
				if (!O_Acl_Session::can( $action, $resourse ))
					return false;
			}
		}
		return true;
	}

	/**
	 * Processes the request
	 *
	 */
	abstract public function process();

	/**
	 * Redirects user to other url
	 *
	 * @param string $href
	 * @return null
	 */
	public function redirect( $href = null )
	{
		$href = O_UrlBuilder::get( 
				is_null( $href ) ? O_Registry::get( "app/env/process_url" ) : $href );
		Header( "Location: $href" );
		return null;
	}

	/**
	 * Returns request param by its name, or default value if param is not specified
	 *
	 * @param string $name
	 * @param mixed $defaultValue
	 * @return mixed
	 */
	public function getParam( $name, $defaultValue = null )
	{
		$v = O_Registry::get( "app/env/params/$name" );
		return is_null( $v ) ? $defaultValue : $v;
	}

	/**
	 * Returns array of request params
	 *
	 * @return Array
	 */
	public function getParams()
	{
		return O_Registry::get( "app/env/params" );
	}

	/**
	 * Finds and returns template for current command
	 *
	 * @param string $tpl Template name to return; equal to command name by default
	 * @param bool $omitPrefix Strip command namespace prefix for template
	 * @return O_Html_Template
	 * @throws Exception
	 */
	public function getTemplate( $tpl = null, $omitPrefix = false )
	{
		if ($tpl && $omitPrefix) {
			$class = $tpl;
		} else {
			preg_match( "#([_a-z]+_)Cmd(_[_a-z]+)#i", get_class( $this ), $matches );
			$class = $matches[ 1 ] . "Tpl" . ($tpl ? "_" . $tpl : $matches[ 2 ]);
		}
		if (!class_exists( $class ))
			throw new O_Ex_PageNotFound( "Cannot find template class for current command." );
		$tpl = new $class( );
		if ($tpl instanceof O_Html_Template)
			return $tpl;
		throw new O_Ex_Critical( "Invalid template called for current command." );
	}

}