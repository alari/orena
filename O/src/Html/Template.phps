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
	public function layout()
	{
		if (!$this->layoutObject) {
			$class = $this->layoutClass;
			$this->layoutObject = new $class( $this );
		}
		return $this->layoutObject;
	}

	/**
	 * Sets layout class, reloads layout
	 *
	 * @param string $classname
	 */
	public function setLayoutClass( $classname )
	{
		$this->layoutClass = $classname;
		$this->layoutObject = null;
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
		$this->layout()->display();
	}

	/**
	 * Returns command url
	 *
	 * @param string $url
	 * @return string
	 */
	public function url( $url, array $params = array() )
	{
		return $this->layout()->url( $url, $params );
	}

	/**
	 * Returns url to static file
	 *
	 * @param string $url
	 * @param bool $fw
	 * @return string
	 */
	public function staticUrl( $url, $fw = false )
	{
		return $this->layout()->staticUrl( $url, $fw );
	}
}