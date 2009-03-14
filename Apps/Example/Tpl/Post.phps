<?php
class Ex_Tpl_Post extends O_Html_Template {
	
	public $post;
	public $form;

	public function __construct()
	{
		$this->layoutClass = "O_Html_MainLayout";
		$this->layout()->title = "Recent news";
	}

	public function displayContents()
	{
		$this->layout()->title = "Post: " . $this->post->title;
		?>
<div><a href="<?=$this->url( "" )?>">К списку постов</a> | <a
	href="<?=$this->url( "post/form/" . $this->post->id )?>">Редактировать
пост</a></div>
<?
		$this->post->show( $this->layout() );
		
		if ($this->form)
			$this->form->show( $this->layout() );
	}

}