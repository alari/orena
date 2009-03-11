<?php
class Test_Cmd_Ajax extends O_Command {

	public function process()
	{
		$form = new O_Dao_Renderer_FormProcessor( );
		$form->setClass( "Test_Models_Core" );
		$form->setAjaxMode();
		$form->setCreateMode();
		$form->setType( "ajax" );

		if (O_Registry::get( "app/env/request_method" ) == "POST") {
			$form->responseAjax();
			return;
		}

		$tpl = $this->getTemplate();
		$tpl->form = $form;
		return $tpl;
	}
}