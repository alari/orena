<?php
class Dao_Signals {
	private static $listeners = Array ();

	const EVENT_REMOVE = "remove";
	const EVENT_SET = "set";
	const EVENT_CREATE = "create";
	const EVENT_DELETE = "delete";

	/**
	 * Adds listener for signal. Attention: field must contain "-signal" directive to fire signals.
	 *
	 * @param callback $callback interface: function(mixed $fieldValue, Dao_ActiveRecord $object, const $event)
	 * @param const $event
	 * @param string $signal Keystring from "-signal $signal" directive.
	 * @param string $class
	 */
	static public function bind( $callback, $event = null, $signal = null, $class = null )
	{
		$event = (string)$event;
		$signal = (string)$signal;
		$class = (string)$class;
		$key = $event . "-" . $signal . "-" . $class;
		if (!isset( self::$listeners[ $key ] ) || !in_array( $callback, self::$listeners[ $key ] ))
			self::$listeners[ $key ][] = $callback;
	}

	/**
	 * Fires signal that something had happen with the object
	 *
	 * @param const $event
	 * @param string $signal
	 * @param string $class
	 * @param Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 */
	static public function fire( $event, $signal, $class, Dao_ActiveRecord $obj, $fieldValue = null )
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

		$keys = Array ();

		// TODO: clean-up keys construction; parse $signal with space


		$keys[] = "--";
		$keys[] = $event . "--";
		$keys[] = "-" . $signal . "-";
		$keys[] = "--" . $class;
		$keys[] = "$event-$signal-";
		$keys[] = "$event--$class";
		$keys[] = "-$signal-$class";
		$keys[] = "$event-$signal-$class";

		$listeners = Array ();

		foreach ($keys as $key) {
			if (isset( self::$listeners[ $key ] ) && count( self::$listeners[ $key ] )) {
				foreach (self::$listeners[ $key ] as $callback) {
					if (!in_array( $callback, $listeners ))
						$listeners[] = $callback;
				}
			}
		}
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
		$cmp = Array ();
		if ($event)
			$cmp[] = $event;
		if ($signal)
			$cmp[] = $signal;
		if ($class)
			$cmp[] = $class;
		foreach (self::$listeners as $k => &$v) {
			list ($e, $s, $c) = explode( "-", $k, 3 );
			$k_cmp = Array ();
			// TODO remove copy-paste
			if ($event && !$e)
				continue;
			if ($event)
				$k_cmp[] = $e;

			if ($signal && !$s)
				continue;
			if ($signal)
				$k_cmp[] = $s;

			if ($class && !$c)
				continue;
			if ($class)
				$k_cmp[] = $c;

			if ($k_cmp != $cmp)
				continue;

			if (!$callback) {
				$v = Array ();
				continue;
			}

			$kk = array_search( $callback, $v );
			if ($kk) {
				unset( $v[ $kk ] );
			}
		}
	}

}