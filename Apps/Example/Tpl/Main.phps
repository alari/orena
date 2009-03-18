<?php
class Ex_Tpl_Main extends O_Html_Template {
	
	/**
	 * Pager with posts
	 *
	 * @var O_Dao_Paginator
	 */
	public $paginator;

	public function __construct()
	{
		$this->layoutClass = "O_Html_MainLayout";
		$this->layout()->title = "Recent news";
	}

	public function displayContents()
	{
		?>
<div><a href="<?=$this->url( "post/form" )?>">Добавить пост</a></div>
<?
		if ($this->paginator) {
			$this->paginator->show( $this->layout() );
		}
	}

}