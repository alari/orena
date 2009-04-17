<?php
class O_Tpl_Create extends O_Html_Template {

	public $form;
	public $success;

	public function displayContents()
	{
		if ($this->success)
			echo $this->success;
		else
			$this->form->show( $this->layout() );
	}
}