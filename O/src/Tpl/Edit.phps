<?php
class O_Tpl_Edit extends O_Html_Template {
	
	public $form;
	public $success;
	public $obj;

	public function displayContents()
	{
		if ($this->success)
			echo $this->success;
		$this->form->show( $this->layout() );
	}
}