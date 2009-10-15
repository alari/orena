<?php

abstract class O_Form_Row {
	protected $title;
	protected $remark;
	protected $error;
	protected $isVertical = null;
	protected $cssClass = "form-row";

	abstract public function renderInner( O_Html_Layout $layout = null, $isAjax = false );

	protected function render( O_Html_Layout $layout = null, $isAjax = false )
	{
		if ($this->isVertical !== null) {
			$this->cssClass .= " form-row-" . ($this->isVertical ? "v" : "h");
		}
		echo "<div class=\"{$this->cssClass}\">";
		if ($this->title) {
			echo "<div class=\"form-row-title\">$this->title</div>";
		}
		
		echo "<div class=\"form-row-content\">";
		$this->renderInner( $layout, $isAjax );
		echo "</div>";
		
		if ($this->error) {
			echo "<div class=\"form-row-error\">$this->error</div>";
		}
		if ($this->remark) {
			echo "<div class=\"form-row-remark\">$this->remark</div>";
		}
		echo "</div>";
	}

}