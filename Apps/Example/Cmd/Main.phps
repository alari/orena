<?php
class Ex_Cmd_Main extends O_Command {

	public function process()
	{
		$tpl = $this->getTemplate();
		$posts = O_Dao_Query::get( "Ex_Mdl_Post" );
		$tpl->paginator = $posts->getPaginator( array ($this, "url"), 5, "posts/page", 
				array ("time" => "Time", "id" => "PK") );
		
		$tpl->paginator->setModeAjax();
		
		if ($tpl->paginator->isAjaxPageRequest()) {
			$tpl->paginator->show( $tpl->layout() );
			return;
		}
		
		return $tpl;
	}

	public function url( $page, $order )
	{
		return O_UrlBuilder::get( "page/" . $page . "/" . $order );
	}

}