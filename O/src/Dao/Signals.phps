<?php
/**
 * Provides support for O_Dao_ActiveRecord signals fired when the object is changed, created or deleted.
 *
 * The field or ActiveRecord class, to fire signals, must have "-signal" configuration key.
 * Listener can be attached to classname, event type, signal name, or to any combination of them.
 * Signal names are given in "-signal" directive as optional parameters, e.g. "-signal name1 name2".
 *
 * @author Dmitry Kourinski
 */
class O_Dao_Signals {
	/**
	 * Array of listeners callbacks
	 *
	 * @var array
	 */
	private static $listeners = Array ();

	const EVENT_REMOVE = "remove";
	const EVENT_SET = "set";
	const EVENT_CREATE = "create";
	const EVENT_DELETE = "delete";

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
			if (!isset( self::$listeners[ $event ][ $s ][ $class ] ) || !in_array( $callback,
					self::$listeners[ $event ][ $s ][ $class ] ))
				self::$listeners[ $event ][ $s ][ $class ][] = $callback;
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

		foreach ($events as $e) {
			foreach ($signals as $s) {
				foreach ($classes as $c) {
					if (isset( self::$listeners[ $e ][ $s ][ $c ] ) && is_array( self::$listeners[ $e ][ $s ][ $c ] )) {
						$listeners = array_merge( $listeners, self::$listeners[ $e ][ $s ][ $c ] );
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
			self::$listeners = Array ();
			return;
		}

		$signals = Array ();
		if ($signal) {
			$signal = explode( " ", $signal );
			foreach ($signal as $s)
				if (!in_array( $s, $signals ))
					$signals[] = $s;
		}

		foreach (self::$listeners as $e => &$listeners_ev) {
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
	}
}