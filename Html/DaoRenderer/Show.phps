<?php
class Html_DaoRenderer_Show {
	private $layout;

	public function __construct(Html_Layout $layout=null) {
		$this->layout = $layout;
	}

	public function display(Dao_ActiveRecord $obj) {
		;
	}

	public function displayLoop(Dao_Query $query) {
		;
	}
}