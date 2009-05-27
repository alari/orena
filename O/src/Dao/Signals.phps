<?php
/**
 * Provides support for O_Dao_ActiveRecord signals fired when the object is changed, created or deleted.
 *
 * The field or ActiveRecord class, to fire signals, must have "-signal" configuration key.
 * Listener can be attached to classname, event type, signal name, or to any combination of them.
 * Signal names are given in "-signal" directive as optional parameters, e.g. "-signal name1 name2".
 *
 * Signal handlers are stored in registry:
 * "app/dao-listeners/$event/$signal/$class" = $callback
 * use "-" subkey to add signal handler for all events or signal types or classes.
 *
 * @author Dmitry Kourinski
 */
class O_Dao_Signals {
	
	const EVENT_REMOVE = "remove";
	const EVENT_SET = "set";
	const EVENT_CREATE = "create";
	const EVENT_DELETE = "delete";
	
	const REGISTRY_KEY = "app/dao-listeners";

	/**
	 * Adds listener for signal. Attention: field must contain "-signal" directive to fire signals.
	 *
	 * @param callback $callback interface: function(mixed $fieldValue, O_Dao_ActiveRecord $object, const $event)
	 * @param const $event
	 * @param string $signal Keystring from "-signal $signal" directive.
	 * @param string $class
	 */
	static public function bind( $callback, $event = null, $signal = null, $class = null )
	{
		$event = $event ? (string)$event : "-";
		$signal = $signal ? (string)$signal : "-";
		$class = $class ? (string)$class : "-";
		$signals = explode( " ", $signal );
		if (!count( $signals ))
			$signals = array ("-");
		foreach ($signals as $s) {
			if (!O_Registry::get( self::REGISTRY_KEY . "/$event/$s/$class" ) || !in_array( 
					$callback, O_Registry::get( self::REGISTRY_KEY . "/$event/$s/$class" ) ))
				O_Registry::add( self::REGISTRY_KEY . "/$event/$s/$class", $callback );
		}
	}

	/**
	 * Fires signal that something had happen with the object
	 *
	 * @param const $event
	 * @param string $signal
	 * @param string $class
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 */
	static public function fire( $event, $signal, $class, O_Dao_ActiveRecord $obj, $fieldValue = null )
	{
		foreach (self::getListeners( $event, $signal, $class ) as $callback) {
			if (!is_array( $callback ) && strpos( $callback, "::" ))
				$callback = explode( "::", $callback );
			call_user_func_array( $callback, array ($fieldValue, $obj, $event) );
		}
	}

	/**
	 * Returns listener callbacks that will be called if such signal reported
	 *
	 * @param string $event
	 * @param string $signal
	 * @param string $class
	 * @return array
	 */
	static public function getListeners( $event, $signal, $class )
	{
		$listeners = Array ();
		
		$events = Array ("-");
		if ($event && $event != "-")
			$events[] = (string)$event;
		
		$classes = Array ("-");
		if ($class && $class != "-")
			$classes[] = (string)$class;
		
		$signals = Array ("-");
		if ($signal) {
			$signal = explode( " ", $signal );
			foreach ($signal as $s)
				if (!in_array( $s, $signals ))
					$signals[] = $s;
		}
		
		$av_listeners = O_Registry::get( self::REGISTRY_KEY );
		
		foreach ($events as $e) {
			foreach ($signals as $s) {
				foreach ($classes as $c) {
					if (isset( $av_listeners[ $e ][ $s ][ $c ] ) && is_array( 
							$av_listeners[ $e ][ $s ][ $c ] )) {
						$listeners = array_merge( $listeners, $av_listeners[ $e ][ $s ][ $c ] );
					}
				}
			}
		}
		
		$listeners = array_unique( $listeners );
		$null = array_search( null, $listeners );
		if ($null)
			unset( $listeners[ $null ] );
		return $listeners;
	}

	/**
	 * Removes all listeners by the mask
	 *
	 * @param string $callback
	 * @param string $event
	 * @param string $signal
	 * @param string $class
	 */
	static public function unbind( $callback = null, $event = null, $signal = null, $class = null )
	{
		if (!$callback && !$event && !$signal && !$class) {
			O_Registry::set( self::REGISTRY_KEY, array () );
			return;
		}
		
		$av_listeners = O_Registry::get( self::REGISTRY_KEY );
		
		$signals = Array ();
		if ($signal) {
			$signal = explode( " ", $signal );
			foreach ($signal as $s)
				if (!in_array( $s, $signals ))
					$signals[] = $s;
		}
		
		foreach ($av_listeners as $e => &$listeners_ev) {
			if ($event && $e != $event)
				continue;
			foreach ($listeners_ev as $s => &$listeners_sg) {
				if (count( $signals ) && !in_array( $s, $signals ))
					continue;
				foreach ($listeners_sg as $c => &$listeners_cl) {
					if ($class && $c != $class)
						continue;
					if ($callback) {
						$remove = array_search( $callback, $listeners_cl );
						if ($remove !== false)
							$listeners_cl[ $remove ] = $remove;
					} else {
						$listeners_cl = Array ();
					}
				}
			}
		}
		
		O_Registry::set( self::REGISTRY_KEY, $av_listeners );
	}
}