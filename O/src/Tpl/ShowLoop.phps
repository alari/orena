<?php
class O_Tpl_ShowLoop extends O_Html_Template {
	
	public $paginator;
	public $type = O_Dao_Renderer::TYPE_LOOP;

	public function displayContents()
	{
		$this->paginator->show( $this->layout(), $this->type );
	}
}