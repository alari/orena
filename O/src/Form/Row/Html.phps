<?php
class O_Form_Row_Html extends O_Form_Row {
	
	protected $content;

	public function setContent( $content )
	{
		$this->content = $content;
	}

	public function renderInner( O_Html_Layout $layout = null, $isAjax = false )
	{
		echo $this->content;
	}
}