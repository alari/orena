<?php

class O_Meta {
	/**
	 * Returns raw data for class/method/property/function
	 *
	 * @param string|object $class string or object
	 * @param string $method if starts with $, property
	 * @return array of annotations (name, params)
	 */
	static public function getRaw($class, $method=null) {
		list($class, $method, $object, $key) = self::getRequested($class, $method);

		$r = self::retrieve($key);
		if(is_array($r)) return $r;

		return self::store($key, self::parseReflection(self::getReflection($class, $method, $object)));
	}

	/**
	 * Returns raw data for class/method/property/function
	 *
	 * @param string|object $class
	 * @param string $method
	 */
	static public function getMergedRaw($class, $method=null) {
		list($class, $method, $object, $key) = self::getRequested($class, $method);
		if(!$class) throw new O_Ex_WrongArgument("Cannot get merged meta for function.");

		$key .= "-merged";
		$r = self::retrieve($key);
		if(is_array($r)) return $r;

		$refl = self::getReflection($class, $method, $object);
		if(!$refl) return self::store($key, null);
		if($refl instanceof ReflectionProperty || $refl instanceof ReflectionMethod) {
			$class = $refl->getDeclaringClass()->getName();
		}

		$base = Array();

		$parent = get_parent_class($class);
		if($parent) {
			$base = self::getMergedRaw($parent, $method);
			if(!$base) $base = Array();
		}

		$current = self::getRaw($class, $method);
		if(!$current) {
			$current = Array();
		} else {
			foreach($current as &$v) {
				$v["class"] = $class;
			}
		}

		return self::store($key, array_merge($base, $current));
	}

	/**
	 * Returns array of callable handlers
	 *
	 * @param string $class
	 * @param string $method
	 */
	static public function getHandlers($class, $method, $registryOrArray="_annotation") {
		$raw = self::getMergedRaw($class, $method);
		$handlers = Array();
		foreach($raw as $k=>$v) {
			if(is_array($registryOrArray)){
				if(!array_key_exists($v["name"], $registryOrArray)) continue;
				$h = $registryOrArray[$v["name"]];
			} else {
				$h = O($registryOrArray."/".$v["name"]);
			}
			if(!$h) continue;
			$handlers[] = Array("handler"=>$h, "params"=>$v["params"], "class"=>$v["class"]);
		}
		return $handlers;
	}

	/**
	 * Returns array of argiments
	 *
	 * @param mixed $class
	 * @param string $method
	 */
	static public function getArguments($class, $method=null) {
		list($class, $method, $object, $key) = self::getRequested($class, $method);
		$key .= "-args";
		if(!$method) {
			$key .= $object ? "-invoke" : "-construct";
		}
		$r = self::retrieve($key);
		if(is_array($r)) return $r;

		$refl = self::getReflection($class, $method, $object);
		// Getting reflection for constructor or invokation
		try {
			if($refl instanceof ReflectionClass) {
				$refl = $refl->getConstructor();
			} elseif($refl instanceof ReflectionObject && !$method) {
				$refl = $refl->getMethod("__invoke");
			}
		} catch(ReflectionException $e) {
			throw new O_Ex_WrongArgument("Cannot get arguments (with no method/property name) for class without constructor and for object without __invoke()");
		}

		$meta = Array();

		// Parameters for function or method
		if($refl instanceof ReflectionFunctionAbstract) {
			$parameters = $refl->getParameters();
			foreach($parameters as $r) {
				$meta[$r->getName()] = Array("isOptional" => $r->isOptional(), "default"=> $r->isOptional() ? $r->getDefaultValue() : null);
			}
			return self::store($key, $meta);
		}
		// Parameters for a property
		if($refl instanceof ReflectionProperty) {
			$meta["value"] = array("isOptional"=>false, "default"=>null);
			return self::store($key, $meta);
		}
	}

	/**
	 * Call function, method, constructor, set property with all annotations processed
	 *
	 * @param callback
	 * @param $_
	 * @return mixed
	 */
	static public function call() {
		$args = func_get_args();
		$callback = array_shift($args);
		list($class, $method, $object, ) = self::getRequested($callback);
echo "($class,$method,".(!$object?:"+").")";
		$call = new O_Meta_Decorators($class, $method, $object);

		$call->setArgsInfo(self::getArguments($callback));
		$call->setUserArgs($args);
		$call->setHandlers(self::getHandlers($class, $method, O_Meta_Decorators::REGISTRY));
		return $call();
	}

	/**
	 * Returns reflection object
	 *
	 * @param string|object $class
	 * @param string $method
	 */
	static public function getReflection($class, $method=null, $object=null) {
		list($class, $method, $object, ) = self::getRequested($object?$object:$class, $method);

		try {
			// Property of an object
			if($object && $method && $method[0] == '$') {
				$refl = new ReflectionObject($object);
				return $refl->getProperty(substr($method,1));
			}
			// Plain function
			if(!$class) {
				return new ReflectionFunction($method);
			}
			// Class method
			if($class && $method) {
				return new ReflectionMethod($class, $method);
			}
			// Object
			if($object) {
				return new ReflectionObject($object);
			}
			// Class
			if(!$method) {
				return new ReflectionClass($class);
			}
			// Class property
			if($method[0] == '$') {
				return new ReflectionProperty($class, substr($method, 1));
			}
		} catch(ReflectionException $e) {
			return null;
		}
		return null;
	}

	/**
	 * Returns requested params
	 *
	 * @param string|array|object $class
	 * @param string|closure $method
	 * @return array(class, method, cache_key)
	 */
	static private function getRequested($class, $method=null) {
		$object = null;
		if(is_array($class)) {
			list($class, $method) = $class;
		}
		if(is_object($class)) {
			$object = $class;
			$class = get_class($object);
		} elseif(strpos($class, "::")) {
			list($class, $method) = explode("::", $class, 2);
		}
		if(!$class && !$method) throw new O_Ex_WrongArgument("Cannot retrieve meta for nothing.");
		$key = O("_cache_prefix").":".($class?$class:"fn").":".($method ? $method : "#");
		return Array($class, $method, $object, $key);
	}

	/**
	 * Returns annotations from doc comment
	 *
	 * @param Reflector $refl
	 * @return array
	 */
	static public function parseReflection(Reflector $refl) {
		$doc = $refl->getDocComment();
		$meta = Array();
		preg_match_all("#@([A-Z][:\\_a-zA-Z0-9]+)(\\((.*?)\\))?#m", $doc, $matches);

		$names = $matches[1];
		$params = $matches[3];

		foreach($names as $k=>$v) {
			// Parse arguments for annotation
			preg_match_all('#(([-_\$\+/:a-z0-9]+)\s*=\s*)?(("([^"]*)")|[^ ,]+)\s*(,|$)#im', $params[$k], $m);
			$p = Array();
			foreach(array_keys($m[0]) as $pk) {
				$p[$m[2][$pk] ? $m[2][$pk] : $pk] = $m[5][$pk] ? $m[5][$pk] : $m[3][$pk];;
			}
			$meta[] = array("name"=>$v, "params"=>$p);
		}

		return $meta;
	}

	/**
	 * Returns value from cache
	 *
	 * @param string $key
	 * @return mixed
	 */
	static private function retrieve($key) {
		return !function_exists("apc_fetch") || mt_rand(0, 100) > 92 ?: apc_fetch($key);
	}

	/**
	 * Stores value into cache
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return mixed
	 */
	static private function store($key, $value) {
		!function_exists("apc_store") ?: apc_store($key, $value);
		return $value;
	}
}