<?php
class O_Tpl_Show extends O_Html_Template {
	
	public $obj;
	public $type = O_Dao_Renderer::TYPE_DEF;

	public function displayContents()
	{
		$this->obj->show( $this->layout(), $this->type );
	}
}