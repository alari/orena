<?php
class Ex_Cmd_Form extends O_Command {
	public function process() {

		$form = new O_Dao_Renderer_FormProcessor;
		$form->setClass("Ex_Mdl_Post");
		if(O_Registry::get("app/posts/id")) {
			$form->setActiveRecord(O_Dao_ActiveRecord::getById(O_Registry::get("app/posts/id"), "Ex_Mdl_Post"));
		} else {
			$form->setCreateMode();
		}
			if($form->handle()) {
				if(!O_Registry::get("app/posts/id")) {
					$form->getActiveRecord()->time = time();
					$form->getActiveRecord()->save();
				}
				Header("Location: /example/".(O_Registry::get("app/posts/id") ? "post/".O_Registry::get("app/posts/id") : ""));
				exit;
			}

		$tpl = $this->getTemplate();
		$tpl->form = $form;
		return $tpl;
	}

}