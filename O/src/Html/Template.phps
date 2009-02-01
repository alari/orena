<?php
abstract class O_Html_Template {
	/**
	 * Classname of layout assotiated with current template
	 *
	 * @var string
	 */
	protected $layoutClass = "O_Html_Layout";
	/**
	 * Layout object
	 *
	 * @var O_Html_Layout
	 */
	private $layoutObject;

	/**
	 * Echoes template main contents
	 *
	 */
	abstract public function displayContents();

	/**
	 * Returns layout object assotiated with this template
	 *
	 * @return O_Html_Layout
	 */
	public function getLayout()
	{
		if (!$this->layoutObject) {
			$class = $this->layoutClass;
			$this->layoutObject = new $class( $this );
		}
		return $this->layoutObject;
	}

	/**
	 * Unsets current layout object so it will be reinitiated
	 *
	 */
	public function reloadLayout()
	{
		$this->layoutObject = null;
	}

	/**
	 * Echoes template page
	 *
	 */
	public function display()
	{
		$this->getLayout()->display();
	}

}