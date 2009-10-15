<?php
class O_Form_Row_String extends O_Form_Row_Field {

	public function renderInner( O_Html_Layout $layout = null, $isAjax = false )
	{
		echo "<input class=\"form-string\" type=\"text\" name=\"{$this->name}\" value=\"" . htmlspecialchars( 
				$this->value ) . "\"/>";
	}
}