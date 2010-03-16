<?php

class O_Dao_ApcCache {
	static public function store($name, $value) {
		if(function_exists("apc_store")) apc_store($name, $value);
	}

	static public function retrieve($name) {
		if(function_exists("apc_fetch")) {
			$f = apc_fetch($name);
			if($f) setcookie("test", "+f");
		}
	}

	static public function delete($name) {
		if(function_exists("apc_delete")) apc_delete($name);
	}

	static public function clear() {
		if(function_exists("apc_clear_cache")) apc_clear_cache("user");
	}
}