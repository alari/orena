<?php
class Ex_Cmd_Post extends O_Command {

	public function process()
	{
		$tpl = $this->getTemplate();
		$tpl->post = O_Dao_ActiveRecord::getById( O_Registry::get( "app/posts/id" ), "Ex_Mdl_Post" );
		
		$form = new O_Dao_Renderer_FormProcessor( );
		$form->setClass( "Ex_Mdl_Comment" );
		$form->setCreateMode();
		
		if ($form->handle()) {
			$record = $form->getActiveRecord();
			$record->time = time();
			$tpl->post->comments[] = $record;
			$record->save();
			
			$form->removeActiveRecord();
			return $this->redirect( $_SERVER[ 'REQUEST_URI' ] );
		}
		$tpl->form = $form;
		return $tpl;
	}

}