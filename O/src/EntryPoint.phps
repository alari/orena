<?php
// We need to require it manually
require_once 'ClassManager.phps';
/**
 * Processes request -- from url and host parsing to response echoing.
 *
 * To build your project based on this, type in your entry-point file:
 * <code>
 * require_once "O/src/EntryPoint.phps";
 * O_EntryPoint::processRequest();
 * </code>
 *
 * This depends on configuration files:
 * ./Apps/Orena.fw.xml -- framework registry configuration (to be used instead of default one, located in ./O/src/)
 * ./Apps/Orena.apps.xml -- application selection
 * ./Apps/{APP_NAME}/Application.xml -- concrete application config, include registry and url-parsing
 *
 * Configuration files formats could be seen in ./O/static/dtd/
 *
 * @author Dmitry Kourinski
 */
class O_EntryPoint {
	static $appConfigXml;

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
		try {
			O_Registry::set( "start-time", microtime( true ) );
			
			// Preparing environment
			self::prepareEnvironment();
			
			// At first we parse framework registry config
			self::processFwConfig();
			
			// Then we handle applications to select what to run
			self::selectApp();
			
			// Parsing application registry
			self::processAppConfig();
			
			// TODO: get locale from registry
			setlocale( LC_ALL, "ru_RU.UTF8" );
			
			if (O_Registry::get( "app/mode" ) == "development") {
				set_error_handler( Array (__CLASS__, "errorException"), E_ALL );
			}
			
			// Prepare and echo response
			return self::makeResponse();
		}
		catch (Exception $e) {
			$errTpl = O_Registry::get( "app/err_tpl" );
			$tpl = new $errTpl( $e );
			if ($tpl instanceof O_Html_Template) {
				$tpl->display();
				return true;
			}
		}
	}

	/**
	 * Internal errors handler
	 *
	 * @param int $code
	 * @param string $msg
	 */
	static public function errorException( $code, $msg )
	{
		throw new O_Ex_CodeError( $msg, $code );
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
		O_Registry::set( "app/env/http_host", $_SERVER[ 'HTTP_HOST' ] );
		// Request method
		O_Registry::set( "app/env/request_method", $_SERVER[ 'REQUEST_METHOD' ] );
		
		// Setting registry inheritance
		O_Registry::setInheritance( "fw", "app" );
		
		// Adding request params to app/env/request registry
		O_Registry::set( "app/env/params", array_merge( $_POST, $_GET ) );
		
		// Base URL
		O_Registry::set( "app/env/base_url", "/" );
	}

	/**
	 * Parses and processes application selecting according with current environment.
	 *
	 * Uses configuration file allocated in "./Apps/Orena.apps.xml"
	 * Sets "app/env/base_url" for application prefix.
	 * Sets "app/env/process_url" for future use inside application.
	 * Sets "app/name", "app/class_prefix", "app/mode" registry keys.
	 *
	 * @throws O_Ex_Critical
	 */
	static public function selectApp()
	{
		
		$app_name = null;
		if (!is_file( "./Apps/Orena.apps.xml" )) {
			$d = opendir( "./Apps" );
			while ($f = readdir( $d )) {
				if ($f != "." && $f != "..") {
					if (is_dir( "./Apps/" . $f ) && is_file( "./Apps/" . $f . "/App.xml" )) {
						self::$appConfigXml = simplexml_load_file( "./Apps/" . $f . "/App.xml" );
						$app_prefix = (string)self::$appConfigXml[ "prefix" ];
						$app_ext = (string)self::$appConfigXml[ "ext" ];
						if (!$app_ext)
							$app_ext = O_ClassManager::DEFAULT_EXTENSION;
						
						if (!$app_prefix)
							throw new O_Ex_Config( "Application without name or class prefix cannot be processed." );
						
						foreach (self::$appConfigXml->xpath( "//Condition" ) as $cond) {
							if (self::processAppSelectionCondition( $cond )) {
								
								$app_name = $f;
								O_ClassManager::registerPrefix( $app_prefix, "./Apps/" . $app_name, $app_ext );
								O_Registry::set( "app/class_prefix", $app_prefix );
								O_Registry::set( "app/name", $app_name );
								O_Registry::set( "app/mode", (string)$cond[ "mode" ] );
								break;
							}
						}
					}
				}
			}
		} else {
			$xml_apps = simplexml_load_file( "./Apps/Orena.apps.xml" );
			foreach ($xml_apps as $app) {
				if ($app->getName() == "App") {
					$app_name = self::processAppSelection( $app );
					if ($app_name) {
						break;
					}
				} else
					throw new O_Ex_Config( "App-selection file should contain only app blocks!" );
			}
		}
		if ($app_name) {
			O_Registry::set( "app/env/process_url", substr( O_Registry::get( "app/env/request_url" ), strlen( O_Registry::get( "app/env/base_url" ) ) ) );
			return;
		}
		throw new O_Ex_Critical( "Neither app-selecting config nor app config found." );
	}

	/**
	 * Processes current application config.
	 *
	 * Gets application name from registry key "app/name"
	 * Parses config allocated in "./Apps/$app_name/App.xml"
	 *
	 * @throws O_Ex_Critical
	 */
	static private function processAppConfig()
	{
		$app_name = O_Registry::get( "app/name" );
		if (self::$appConfigXml instanceof SimpleXMLElement) {
			$xml_current = & self::$appConfigXml;
		} else {
			if (!is_file( "./Apps/" . $app_name . "/App.xml" ))
				throw new O_Ex_Critical( "Can't find application config file ($app_name)." );
			
			$xml_current = simplexml_load_file( "./Apps/" . $app_name . "/App.xml" );
		}
		foreach ($xml_current as $node) {
			self::processAppConfigPart( $node );
		}
		
		self::$appConfigXml = null;
		
		// Processing class uses
		$uses = O_Registry::get( "app/uses" );
		if (is_array( $uses ))
			foreach ($uses as $class)
				class_exists( $class );
	}

	/**
	 * Parses framework config, puts it into "fw" registry rootkey.
	 *
	 * @throws O_Ex_Critical
	 */
	static public function processFwConfig()
	{
		$src = is_file( "./Apps/Orena.fw.xml" ) ? "./Apps/Orena.fw.xml" : "./O/src/Orena.fw.xml";
		if (!is_file( $src ))
			throw new O_Ex_Critical( "Cannot find framework configuration file." );
		$xml_fw = simplexml_load_file( $src );
		foreach ($xml_fw as $registry) {
			if ($registry->getName() == "Registry") {
				self::processRegistry( $registry, "fw" );
			} else
				throw new O_Ex_Config( "Framework configuration file should contain only registry values!" );
		}
	}

	/**
	 * According with current application settings, processes command or template and echoes response
	 *
	 * @return bool True on success, false on 404 error (will be also echoed)
	 */
	static public function makeResponse()
	{
		// Create O_Command and process it
		$cmd_name = O_Registry::get( "app/command_name" );
		if (!$cmd_name) {
			$url = O_Registry::get( "app/env/process_url" );
			// Remove extension
			if (O_Registry::get( "app/pages_extension" )) {
				$ext = O_Registry::get( "app/pages_extension" );
				if (strlen( $url ) > strlen( $ext ) && substr( $url, -strlen( $ext ) ) == $ext) {
					$url = substr( $url, 0, -strlen( $ext ) );
				}
			}
			// Remove slashes
			$url = trim( $url, "/" );
			if (!$url) {
				$cmd_name = "Default";
			} else {
				$cmd_name = str_replace( " ", "", ucfirst( str_replace( "-", " ", $url ) ) );
				$cmd_name = str_replace( array (".", "/"), array (" ", " "), $url );
				$cmd_name = str_replace( " ", "_", ucfirst( $cmd_name ) );
			}
		}
		
		$plugin_name = O_Registry::get( "app/plugin_name" );
		$plugin_name = $plugin_name && $plugin_name != "-" ? "_" . $plugin_name : "";
		
		if (!O_Registry::get( "app/command_full" )) {
			$cmd_class = O_Registry::get( "app/class_prefix" ) . $plugin_name . "_Cmd_" . $cmd_name;
			$tpl_class = O_Registry::get( "app/class_prefix" ) . $plugin_name . "_Tpl_" . $cmd_name;
		} else {
			$cmd_class = $cmd_name;
		}
		if (!class_exists( $cmd_class, true ) && !class_exists( $tpl_class, true ) && $cmd_name != "Default") {
			$cmd_name = "Default";
			$cmd_class = O_Registry::get( "app/class_prefix" ) . $plugin_name . "_Cmd_" . $cmd_name;
			$tpl_class = O_Registry::get( "app/class_prefix" ) . $plugin_name . "_Tpl_" . $cmd_name;
		}
		
		if (class_exists( $cmd_class, true )) {
			$cmd = new $cmd_class( );
			if ($cmd instanceof O_Command) {
				/* @var $cmd O_Command */
				$cmd->run();
				return true;
			}
		}
		
		// Else create O_Html_Template
		if (class_exists( $tpl_class, true )) {
			$tpl = new $tpl_class( );
			if ($tpl instanceof O_Html_Template) {
				$tpl->display();
				return true;
			}
		}
		throw new O_Ex_PageNotFound( "Page Not Found", 404 );
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
			// Condition based on mode name and plugin name
			case "If" :
				if ((string)$node[ "mode" ] && (string)$node[ "mode" ] != O_Registry::get( "app/mode" ))
					break;
				if ((string)$node[ "plugin" ] && (string)$node[ "plugin" ] != O_Registry::get( "app/plugin_name" ))
					break;
				foreach ($node as $n)
					self::processAppConfigPart( $n, $pockets );
			
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
			case "Command" :
				if (O_Registry::get( "app/command_name" ))
					break;
				if ((string)$node[ "plugin" ])
					O_Registry::set( "app/plugin_name", (string)$node[ "plugin" ] );
				if ((string)$node[ "type" ]) {
					O_Registry::set( "app/command_name", "O_Cmd_" . ((string)$node[ "type" ]) );
					O_Registry::set( "app/command_full", 1 );
				} else {
					O_Registry::set( "app/command_name", (string)$node[ "name" ] );
				}
			break;
			// Set plugin into "app/plugin_name" registry
			case "Plugin" :
				O_Registry::set( "app/plugin_name", (string)$node[ "name" ] );
			break;
			case "Condition" :
			break;
			default :
				throw new O_Ex_Config( "Unknown node in application configuration file." );
		}
	}

	/**
	 * Processes one App node from application selecting config file
	 *
	 * @param SimpleXMLElement $app
	 * @return string application name or false
	 * @throws O_Ex_Config
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
			throw new O_Ex_Config( "Application without name or class prefix cannot be processed." );
		
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
				throw new O_Ex_Config( "App section should contain only conditions." );
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
	 * @throws O_Ex_Config
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
						throw new O_Ex_Config( "App-selecting Url condition must have 'base' or 'pattern' attribute." );
					}
					if (preg_match( "#^$pattern$#i", O_Registry::get( "app/env/request_url" ) ))
						continue;
					
					return false;
				break;
				// Checks if hostname is equal with "value" attribute or matches "pattern"
				case "Host" :
					$value = (string)$condPart[ "value" ];
					if ($value && (O_Registry::get( "app/env/http_host" ) == $value || O_Registry::get( "app/env/http_host" ) == "www." . $value))
						continue;
					$pattern = (string)$condPart[ "pattern" ];
					if ($pattern && preg_match( "#^$pattern$#i", O_Registry::get( "app/env/http_host" ) ))
						continue;
					return false;
				break;
				default :
					throw new O_Ex_Config( "Wrong node in app-selection condition: " . $condPart->getName() );
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
		} elseif (!$value) {
			$value = $registry;
		}
		// Load object into registry
		if ((string)$registry[ "class" ]) {
			$class = (string)$registry[ "class" ];
			if (!class_exists( $class, true )) {
				throw O_Ex_Config( "Wrong class given in registry." );
			}
			$method = (string)$registry[ "method" ];
			$value = $method ? call_user_func( array ($class, $method), $value ) : O_Dao_ActiveRecord::getById( $value, $class );
			// Add to default acl checker
			if ((string)$registry[ "can" ]) {
				O_Registry::add( "app/cmd/can", $registry[ "can" ] . "#" . $rootkey . "/" . $key );
			}
		}
		if ($registry[ "type" ] == "add") {
			O_Registry::add( $rootkey . "/" . $key, $value );
		} else {
			O_Registry::set( $rootkey . "/" . $key, $value );
		}
	}

}