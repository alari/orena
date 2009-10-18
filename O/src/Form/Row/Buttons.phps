<?php
class O_Form_Row_Buttons extends O_Form_Row {

	protected $reset;
	protected $submits = Array ();

	public function renderInner( O_Html_Layout $layout = null, $isAjax = false )
	{
		echo "<div class=\"form-row-buttons\">";
		foreach ($this->submits as $sbm) {
			echo "<input class=\"form-button\" type=\"submit\" value=\"" . htmlspecialchars(
					$sbm[ 0 ] ) . "\"" . ($sbm[ 1 ] ? " name=\"{$sbm[1]}\"" : "") . "/>";
		}
		if ($this->reset) {
			echo "<input class=\"form-button\" type=\"reset\" value=\"" . htmlspecialchars(
					$this->reset ) . "\"/>";
		}
		echo "</div>";
	}

	public function addSubmit( $title, $name = null )
	{
		$this->submits[] = array ($title, $name);
	}

	public function addReset( $title )
	{
		$this->reset = $title;
	}

}