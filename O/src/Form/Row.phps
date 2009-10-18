<?php

abstract class O_Form_Row {
	/**
	 * Row title
	 *
	 * @var string
	 */
	protected $title;
	/**
	 * Row remark (comment)
	 *
	 * @var string
	 */
	protected $remark;
	/**
	 * Row error message
	 *
	 * @var string
	 */
	protected $error;
	/**
	 * Row layout type: vartical (true), horizontal (false), other (null)
	 *
	 * @var bool
	 */
	protected $isVertical = null;
	/**
	 * Boxing div's css classname. Also extended with form-row-v or form-row-h subclass
	 * if $isVertical is set
	 *
	 * @var string
	 */
	protected $cssClass = "form-row";

	/**
	 * Renders inner contents of a row
	 *
	 * @param O_Html_Layout $layout
	 * @param bool $isAjax
	 */
	abstract public function renderInner( O_Html_Layout $layout = null, $isAjax = false );

	/**
	 * Renders all the row contents, starting with box and including renderInner
	 *
	 * @param O_Html_Layout $layout
	 * @param bool $isAjax
	 */
	public function render( O_Html_Layout $layout = null, $isAjax = false )
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

	/**
	 * Sets the row title
	 *
	 * @param string $title
	 */
	public function setTitle( $title )
	{
		$this->title = $title;
	}

	/**
	 * Sets the row error message
	 *
	 * @param string $error
	 */
	public function setError( $error )
	{
		$this->error = $error;
	}

	/**
	 * Sets the row remark
	 *
	 * @param string $remark
	 */
	public function setRemark( $remark )
	{
		$this->remark = $remark;
	}
}