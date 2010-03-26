<?php
class O_Meta_Decorators{
	const REGISTRY = "_decorators";

	protected $arguments = Array();
	protected $args = Array();
	protected $return;

	protected $class;
	protected $method;
	protected $object;
	protected $handlers = Array();
	protected $isCalled = false;

	private $currentHandler = Array();

	/**
	 * Constructor for caller
	 *
	 * @param string $class
	 * @param string $method
	 * @param object $object
	 */
	public function __construct($class, $method, $object) {
		$this->class = $class;
		$this->method = $method;
		$this->object = $object;
	}

	/**
	 * Set user arguments -- after ->setArgument()!
	 *
	 * @param array $args
	 */
	public function setUserArgs(array $args = Array()) {
		if(count($this->arguments)) {
			$argn = array_keys($this->arguments);
			foreach($args as $v) {
				$n = array_shift($argn);
				if(!$n) throw new O_Ex_WrongArgument("Too many arguments.");
				$this->args[ $n ] = $v;
			}
			if(count($argn)) foreach ($argn as $n) {
				$a = $this->arguments[$n];
				if(!$a["isOptional"]) throw new O_Ex_WrongArgument("Argument $n is not optional.");
				$this->args[$n] = $a["default"];
			}
		} else {
			$this->args = $args;
		}
	}

	/**
	 * Arguments info: O_Meta::getArguments
	 *
	 * @param array $info
	 */
	public function setArgsInfo(array $info) {
		$this->arguments = $info;
	}

	/**
	 * Sets array of handlers
	 *
	 * @param array $handlers
	 */
	public function setHandlers(array $handlers=Array()) {
		$this->handlers = $handlers;
	}

	/**
	 * Returns user argument
	 *
	 * @param string $name
	 */
	public function __get($name) {
		return $this->args[$name];
	}

	/**
	 * Sets user argument before calling
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value) {
		$this->args[$name] = $value;
	}

	/**
	 * Processes call
	 *
	 * @return mixed
	 */
	public function __invoke() {
		if($this->isCalled) {
			return $this->return;
		}
		if(!count($this->handlers)) {
			return $this->call();
		}
		// Modify arguments
		array_map(array($this, "callHandler"), $this->handlers);
		// Save result
		$this->return = $this->call();
		$this->isCalled = 1;
		// Cannot modify properties after setting
		if($this->method[0] == '$') return $this->return;
		// Modify result
		array_map(array($this, "callHandler"), $this->handlers);
		// Return
		return $this->return;
	}

	private function callHandler($handler) {
		$this->currentHandler = $handler;
		$h = $this->currentHandler["handler"];
		call_user_func($h, $this);
	}

	public function getHandlerParams() {
		return $this->currentHandler["params"];
	}

	public function getHandlerClass() {
		return $this->currentHandler["class"];
	}

	public function getClass() {
		return $this->class;
	}

	public function getObject() {
		return $this->object;
	}

	public function getMethod() {
		return $this->method;
	}

	public function isCalled() {
		return $this->isCalled;
	}

	/**
	 * To get result after calling
	 *
	 * @return mixed
	 */
	public function getResult() {
		return $this->return;
	}

	/**
	 * To set result after calling
	 *
	 * @param mixed $v
	 */
	public function setResult($v) {
		$this->return = $v;
	}

	/**
	 * Calls callable
	 */
	protected function call() {
		// Construct or invoke
		if(!$this->method) {
			// Object: invoke
			if($this->object) {
				return call_user_func_array($this->object, $this->args);
			}
			// Class: construct
			$refl = new ReflectionClass($this->class);
			return $refl->newInstance($this->args);
		}
		// Plain function
		if(!$this->class) {
			return call_user_func_array($this->method, $this->args);
		}
		// Property of a class or method
		if($this->method[0] == '$') {
			$property = substr($this->method, 1);
			if($this->object) return $this->object->$property = $this->args["value"];
			$class = $this->class;
			return $class::$$property = $this->args["value"];
		}
		// Object method
		if($this->object) {
			return call_user_func_array(array($this->object, $this->method), $this->args);
		}
		// Class method
		return call_user_func_array(array($this->class, $this->method), $this->args);
	}
}