<?php
class Test_Tpl_Ajax extends O_Html_Template {
	
	public $form;

	public function __construct()
	{
		$this->layoutClass = "O_Html_MainLayout";
		$this->layout()->title = "Ajax form handling testing";
	}

	public function displayContents()
	{
		$this->form->show( $this->layout() );
	}

}