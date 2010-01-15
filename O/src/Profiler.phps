<?php
/**
 * Simple profiler class
 * @author Dmitry Kurinskiy
 *
 */
class O_Profiler {
	static private $isLaunched = 0;

	static private $starts = Array();

	static private $stat = Array();

	static private $launchTime;

	static public function start($name=null){
		if(!self::$isLaunched) return;
		$delta = microtime();
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
		$delta = microtime(true);
		if(!$name) {

		}
		$delta -= self::$starts[$name];
		self::$stat[$name]["time"] += $delta;
		return $delta;
	}

	static private function getName() {
		$delta = microtime(true);
		$d = debug_backtrace();
		$d = $d[2];
		$name = $d["function"];
		if(array_key_exists("class", $d)){
			$name = $d["class"]."::".$name;
		}
		self::$launchTime += (microtime(true)-$delta);
		return $name;
	}

	static public function getStat() {
		return self::$stat;
	}

	static public function launch() {
		self::$isLaunched = 1;
		self::$launchTime = microtime(true);
	}

	static public function getTotal() {
		return microtime(true)-self::$launchTime;
	}
}

O_Profiler::launch();