<?php
class Ex_Cmd_Main extends O_Command {

	public function process()
	{
		$tpl = $this->getTemplate();
		$posts = O_Dao_Query::get( "Ex_Mdl_Post" );
		$posts->orderBy( "time DESC" );
		$tpl->paginator = $posts->getPaginator(array($this, "url"), 5, "posts/page");
		return $tpl;
	}

	public function url($page) {
		return O_UrlBuilder::get("page/".$page);
	}


}