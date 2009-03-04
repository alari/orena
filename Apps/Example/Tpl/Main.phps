<?php
class Ex_Tpl_Main extends O_Html_Template {

	public $posts;

	public function __construct()
	{
		$this->layoutClass = "O_Html_MainLayout";
		$this->layout()->title = "Recent news";
	}

	public function displayContents()
	{
?>
<div>
<a href="<?=$this->url("post/form")?>">Добавить пост</a>
</div>
<?
		if($this->posts) {
			$this->posts->show();
		}
	}

}