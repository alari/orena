<?php
/**
 * Simple profiler class
 * @author Dmitry Kurinskiy
 *
 */
class O_Profiler {
	static private $isLaunched = 1;

	static private $starts = Array();

	static private $stat = Array();

	static public function start($name=null){
		if(!self::$isLaunched) return;
		if(!$name) {
			$d = debug_backtrace();
			$d = $d[1];
			$name = $d["function"];
			if(array_key_exists("class", $d)){
				$name = $d["class"]."::".$name;
			}
		}
		if(!isset(self::$stat[$name])) {
			self::$stat[$name] = Array("calls"=>1, "time"=>0);
		} else {
			self::$stat[$name]["calls"] += 1;
		}
		self::$starts[$name] = microtime(true);
	}

	static public function stop($name=null){
		if(!self::$isLaunched) return;
		if(!$name) {
			$d = debug_backtrace();
			$d = $d[1];
			$name = $d["function"];
			if(array_key_exists("class", $d)){
				$name = $d["class"]."::".$name;
			}
		}
		$delta = microtime(true) - self::$starts[$name];
		self::$stat[$name]["time"] += $delta;
		return $delta;
	}

	static public function getStat() {
		return self::$stat;
	}
}