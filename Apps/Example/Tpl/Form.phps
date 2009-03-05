<?php
class Ex_Tpl_Form extends O_Html_Template {
	
	public $form;

	public function __construct()
	{
		$this->layoutClass = "O_Html_MainLayout";
		$this->layout()->title = "Add or edit news";
	}

	public function displayContents()
	{
		$this->form->show( $this->layout() );
	}

}