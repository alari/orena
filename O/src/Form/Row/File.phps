<?php

class O_Form_Row_File extends O_Form_Row_Field {

	public function renderInner( O_Html_Layout $layout = null, $isAjax = false )
	{
		echo "<input class=\"form-file\" type=\"file\" name=\"{$this->name}\"/>";
	}
}