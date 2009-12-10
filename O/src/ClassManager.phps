<?php
// We need to manually require registry class, because it's used by autoloader
require_once 'Registry.phps';
/**
 * Handles class autoloading, implements "selection is initialization" paradigm.
 *
 * Class manager stores information about class paths and extensions in "fw/classmanager/prefix" registry key,
 * loaded classes can be found in "fw/classmanager/loaded" key.
 *
 * @see O_Registry
 * @todo set up and document simple interface to add class prefixes by Registry config
 * @todo realize "selection is initialization" paradigm
 *
 * @author Dmitry Kurinskiy
 */
class O_ClassManager {
	const DEFAULT_EXTENSION = "php";

	/**
	 * Adds classname prefix to source folder assotiation
	 *
	 * @param string $prefix E.g. "O"
	 * @param string $source_folder E.g. "src/my/O"
	 * @param string $extension
	 */
	static public function registerPrefix( $prefix, $source_folder, $extension = self::DEFAULT_EXTENSION )
	{
		if ($source_folder[ strlen( $source_folder ) - 1 ] != "/")
			$source_folder .= "/";
		O_Registry::set( "fw/classmanager/prefix/$prefix/folder", $source_folder );
		O_Registry::set( "fw/classmanager/prefix/$prefix/ext", $extension );
	}

	/**
	 * Registers callback to be called when class will be loaded
	 *
	 * @param callback $callback
	 * @param string $class
	 */
	static public function registerClassLoadedCallback( $callback, $class )
	{
		O_Registry::add( "fw/classmanager/callback/$class", $callback );
	}

	/**
	 * Includes class source file -- autoload implementation
	 *
	 * @param string $class
	 */
	static public function load( $class )
	{
		
		$file = "";
		foreach (O_Registry::get( "fw/classmanager/prefix" ) as $prefix => $params) {
			if (strpos( $class, $prefix ) === 0) {
				$file = $params[ "folder" ] . str_replace( array ('\\', '_'), array ('/', '/'),
						substr( $class, strlen( $prefix ) + 1 ) ) . "." . $params[ "ext" ];
				break;
			}
		}
		if (!$file) {
			$file = str_replace( array ('\\', '_'), array ('/', '/'), $class ) . "." . self::DEFAULT_EXTENSION;
		}
		try {
			O_Registry::startProfiler(__METHOD__."|fopen");
			$f = @fopen( $file, "r", true );
			O_Registry::stopProfiler(__METHOD__."|fopen");
		}
		catch (Exception $e) {
			$f = 0;
		}
		if ($f) {
			fclose( $f );
			O_Registry::startProfiler(__METHOD__."|include");
			include_once $file;
			O_Registry::stopProfiler(__METHOD__."|include");
			O_Registry::set( "fw/classmanager/loaded/$class", $file );
			if (class_exists( $class )) {
				$callbacks = O_Registry::get( "fw/classmanager/callback/$class" );
				if (count( $callbacks ))
					foreach ($callbacks as $callback)
						call_user_func( $callback );
			}
		}
		
	}
}

// Register autoloader and Orena Framework source files
spl_autoload_register( "O_ClassManager::load" );
O_ClassManager::registerPrefix( "O", dirname( __FILE__ ), "phps" );
set_include_path( dirname( __FILE__ ) . "/../inc/" . PATH_SEPARATOR . get_include_path() );