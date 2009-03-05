<?php
class Ex_Cmd_Main extends O_Command {

	public function process()
	{
		$tpl = $this->getTemplate();
		$tpl->posts = O_Dao_Query::get( "Ex_Mdl_Post" );
		$tpl->posts->orderBy( "time DESC" );
		if (O_Registry::get( "app/posts/page" )) {
			$page = O_Registry::get( "app/posts/page" );
			$perpage = 5;
			$tpl->posts->limit( $page * $perpage, $perpage );
		}
		return $tpl;
	}

}