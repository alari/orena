<?php
// We need to require it manually
require_once 'ClassManager.phps';
/**
 * Processes request -- from url and host parsing to response echo.
 *
 * To build your project based on this, type in your entry-point file:
 * <code>
 * require_once "O/src/EntryPoint.phps";
 * O_EntryPoint::processRequest();
 * </code>
 *
 * This depends on configuration files:
 * ./Apps/Orena.fw.xml -- framework registry configuration
 * ./Apps/Orena.apps.xml -- application selection
 * ./Apps/{APP_NAME}/Application.xml -- concrete application config, include registry and url-parsing
 *
 * @copyright Dmitry Kourinski
 */
class O_EntryPoint {

	/**
	 * Processes request and echoes response.
	 *
	 * Prepares environment, parses framework configuration file, then
	 * parses application selection config, selects current application,
	 * parses its config, finds command or template to process it,
	 * processes it and echoes response.
	 *
	 * @return bool True on success
	 */
	static public function processRequest()
	{
		// TODO: add try-catch envelop for all this
		

		// Preparing environment
		self::prepareEnvironment();
		
		// At first we parse framework registry config
		self::processFwConfig();
		
		// Then we handle applications to select what to run
		self::selectApp();
		
		// Parsing application registry
		self::processAppConfig();
		
		// Prepare and echo response
		return self::makeResponse();
	}

	/**
	 * Prepares registry environment for future use.
	 *
	 * Sets current URL (without query string) to "app/env/request_url"
	 * Sets current HTTP_HOST to "app/env/http_host"
	 * Merges GET and POST parameters to "app/env/params"
	 * Sets "app" inheritance from "fw"
	 */
	static public function prepareEnvironment()
	{
		// Saving url without query string to process it correctly
		$url = $_SERVER[ 'REQUEST_URI' ];
		if (strpos( $url, "?" ))
			$url = substr( $url, 0, strpos( $url, "?" ) );
		O_Registry::set( "app/env/request_url", $url );
		
		// Saving HTTP_HOST value
		O_Registry::set( "app/env/http_host", $_SERVER[ 'REQUEST_URI' ] );
		// Request method
		O_Registry::set( "app/env/request_method", $_SERVER[ 'REQUEST_METHOD' ] );
		
		// Setting registry inheritance
		O_Registry::setInheritance( "fw", "app" );
		
		// Adding request params to app/env/request registry
		O_Registry::set( "app/env/params", array_merge( $_POST, $_GET ) );
	}

	/**
	 * Parses and processes application selecting according with current environment.
	 *
	 * Uses configuration file allocated in "./Apps/Orena.apps.xml"
	 * Sets "app/env/base_url" for application prefix.
	 * Sets "app/env/process_url" for future use inside application.
	 * Sets "app/name", "app/class_prefix", "app/mode" registry keys.
	 *
	 * @throws Exception
	 */
	static public function selectApp()
	{
		if (!is_file( "./Apps/Orena.apps.xml" ))
			throw new Exception( "Cannot find application selecting configuration file." );
		$app_name = null;
		$xml_apps = simplexml_load_file( "./Apps/Orena.apps.xml" );
		foreach ($xml_apps as $app) {
			if ($app->getName() == "App") {
				$app_name = self::processAppSelection( $app );
				if ($app_name) {
					O_Registry::set( "app/env/process_url", 
							substr( O_Registry::get( "app/env/request_url" ), 
									strlen( O_Registry::get( "app/env/base_url" ) ) ) );
					break;
				}
			} else
				throw new Exception( "App-selection file should contain only app blocks!" );
		}
	}

	/**
	 * Processes current application config.
	 *
	 * Gets application name from registry key "app/name"
	 * Parses config allocated in "./Apps/$app_name/App.xml"
	 *
	 * @throws Exception
	 */
	static private function processAppConfig()
	{
		$app_name = O_Registry::get( "app/name" );
		if (!is_file( "./Apps/" . $app_name . "/App.xml" ))
			throw new Exception( "Can't find application config file." );
		
		$xml_current = simplexml_load_file( "./Apps/" . $app_name . "/App.xml" );
		foreach ($xml_current as $node) {
			self::processAppConfigPart( $node );
		}
		
		// Processing class uses
		$uses = O_Registry::get( "app/uses" );
		if (is_array( $uses ))
			foreach ($uses as $class)
				class_exists( $class );
	}

	/**
	 * Parses framework config, puts it into "fw" registry rootkey.
	 *
	 * @throws Exception
	 */
	static public function processFwConfig()
	{
		if (!is_file( "./Apps/Orena.fw.xml" ))
			throw new Exception( "Cannot find framework configuration file." );
		$xml_fw = simplexml_load_file( "./Apps/Orena.fw.xml" );
		foreach ($xml_fw as $registry) {
			if ($registry->getName() == "Registry") {
				self::processRegistry( $registry, "fw" );
			} else
				throw new Exception( "Framework configuration file should contain only registry values!" );
		}
	}

	/**
	 * According with current application settings, processes command or template and echoes response
	 *
	 * @return bool True on success, false on 404 error (will be also echoed)
	 */
	static public function makeResponse()
	{
		// TODO: use plugin in classnames building
		

		// Create O_Command and process it
		$cmd_name = O_Registry::get( "app/command_name" );
		$cmd_class = O_Registry::get( "app/class_prefix" ) . "_Cmd_" . $cmd_name;
		
		if (class_exists( $cmd_class, true )) {
			$cmd = new $cmd_class( );
			if ($cmd instanceof O_Command) {
				/* @var $cmd O_Command */
				$cmd->run();
				return true;
			}
		}
		
		// Else create O_Html_Template
		$tpl_class = O_Registry::get( "app/class_prefix" ) . "_Tpl_" . $cmd_name;
		if (class_exists( $tpl_class, true )) {
			$tpl = new $tpl_class( );
			if ($tpl instanceof O_Html_Template) {
				$tpl->display();
				return true;
			}
		}
		
		// Else process 404 error
		// TODO: add logic to handle 404 error
		echo "<h1>Error 404</h1>";
		return false;
	
	}

	/**
	 * Processes one node from application configuration file.
	 *
	 * If it founds command name to process, sets it to "app/command_name" registry key.
	 * Bases on environment prepared by
	 * @see O_EntryPoint::prepareEnvironment()
	 *
	 * @param SimpleXMLElement $node
	 * @param array $pockets
	 */
	static private function processAppConfigPart( SimpleXMLElement $node, $pockets = null )
	{
		switch ($node->getName()) {
			// Process registry in "app" rootkey
			case "Registry" :
				self::processRegistry( $node, "app", $pockets );
			break;
			// Condition based only on mode name
			case "If" :
				if ((string)$node[ "mode" ] == O_Registry::get( "app/mode" )) {
					foreach ($node as $n)
						self::processAppConfigPart( $n, $pockets );
				}
			break;
			// Parses hostname with pattern, processes child nodes if matches
			case "Host" :
				$pattern = (string)$node[ "pattern" ];
				if (preg_match( "#^$pattern$#i", O_Registry::get( "app/env/http_host" ), $pockets )) {
					foreach ($node as $n)
						self::processAppConfigPart( $n, $pockets );
				}
			break;
			// Parses URL with pattern, processes child nodes if matches
			case "Url" :
				$url = O_Registry::get( "app/env/process_url" );
				$pattern = (string)$node[ "pattern" ];
				if (preg_match( "#^$pattern$#i", $url, $pockets )) {
					// Set command for URL, if available
					$command = (string)$node[ "command" ];
					if ($command && !O_Registry::get( "app/command_name" )) {
						O_Registry::set( "app/command_name", $command );
					}
					foreach ($node as $n) {
						self::processAppConfigPart( $n, $pockets );
					}
				}
			break;
			// Sets "app/command_name" registry key, continues processing
			// TODO: add command plugin name
			case "Command" :
				if (O_Registry::get( "app/command_name" ))
					break;
				O_Registry::set( "app/command_name", (string)$node[ "name" ] );
			break;
			default :
				throw new Exception( "Unknown node in application configuration file." );
		}
	}

	/**
	 * Processes one App node from application selecting config file
	 *
	 * @param SimpleXMLElement $app
	 * @return string application name or false
	 * @throws Exception
	 * @see O_EntryPoint::processAppSelectionCondition()
	 */
	static private function processAppSelection( SimpleXMLElement $app )
	{
		$app_name = (string)$app[ "name" ];
		$app_prefix = (string)$app[ "prefix" ];
		$app_ext = (string)$app[ "ext" ];
		if (!$app_ext)
			$app_ext = O_ClassManager::DEFAULT_EXTENSION;
		
		if (!$app_name || !$app_prefix)
			throw new Exception( "Application without name or class prefix cannot be processed." );
		
		foreach ($app as $cond) {
			if ($cond->getName() == "Condition") {
				if (self::processAppSelectionCondition( $cond )) {
					O_ClassManager::registerPrefix( $app_prefix, "./Apps/" . $app_name, $app_ext );
					O_Registry::set( "app/class_prefix", $app_prefix );
					O_Registry::set( "app/name", $app_name );
					O_Registry::set( "app/mode", (string)$cond[ "mode" ] );
					return $app_name;
				}
			} else
				throw new Exception( "App section should contain only conditions." );
		}
		return false;
	}

	/**
	 * Processes one Condition node to select application.
	 *
	 * Condition could contain any number of Url and Host childs.
	 *
	 * @param SimpleXMLElement $cond
	 * @return bool
	 * @throws Exception
	 */
	static private function processAppSelectionCondition( SimpleXMLElement $cond )
	{
		if ((string)$cond[ "pattern" ] == "any")
			return true;
		
		foreach ($cond as $condPart) {
			switch ($condPart->getName()) {
				// Checks if url starts with "base" attribute or matches "pattern"
				case "Url" :
					$base = (string)$condPart[ "base" ];
					if ($base) {
						if (strpos( O_Registry::get( "app/env/request_url" ), $base ) === 0) {
							O_Registry::set( "app/env/base_url", $base );
							continue;
						}
						return false;
					}
					$pattern = (string)$condPart[ "pattern" ];
					if (!$pattern) {
						throw new Exception( 
								"App-selecting Url condition must have 'base' or 'pattern' attribute." );
					}
					if (preg_match( "#^$pattern$#i", O_Registry::get( "app/env/request_url" ) ))
						continue;
					
					return false;
				break;
				// Checks if hostname is equal with "value" attribute or matches "pattern"
				case "Host" :
					$value = (string)$condPart[ "value" ];
					if ($value && O_Registry::get( "app/env/http_host" ) == $value)
						continue;
					$pattern = (string)$condPart[ "pattern" ];
					if ($pattern && preg_match( $pattern, O_Registry::get( "app/env/http_host" ) ))
						continue;
					return false;
				break;
				default :
					throw new Exception( "Wrong node in app-selection condition: " . $condPart->getName() );
			}
		}
		return true;
	}

	/**
	 * Processes one Registry node in any configuration file. Sets or adds value into $rootkey
	 *
	 * @param SimpleXMLElement $registry
	 * @param string $rootkey
	 * @param array $pockets
	 */
	static private function processRegistry( SimpleXMLElement $registry, $rootkey, $pockets = null )
	{
		$key = (string)$registry[ "key" ];
		$value = (string)$registry[ "value" ];
		if (!$value && is_array( $pockets )) {
			$pocket = (string)$registry[ "pocket" ];
			if (array_key_exists( $pocket, $pockets ))
				$value = $pockets[ $pocket ];
		}
		if ($registry[ "type" ] == "add") {
			O_Registry::add( $rootkey . "/" . $key, $value );
		} else {
			O_Registry::set( $rootkey . "/" . $key, $value );
		}
	}

}