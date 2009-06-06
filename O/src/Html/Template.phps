<?php
/**
 * Templates abstraction.
 *
 * Notice that you could simply tune up default templates via defining "app/layout_class"
 * registry key.
 *
 * @author Dmitry Kourinski
 */
abstract class O_Html_Template extends O_Locale_Access {
	/**
	 * Classname of layout assotiated with current template
	 *
	 * @var string
	 */
	protected $layoutClass;
	/**
	 * Layout object
	 *
	 * @var O_Html_Layout
	 */
	private $layoutObject;

	/**
	 * Constructor -- sets default layout class, if it's not specified at all
	 *
	 */
	public function __construct()
	{
		if (!$this->layoutClass)
			$this->layoutClass = O_Registry::get( "app/layout_class" );
	}

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
			if (!class_exists( $class, true ))
				$class = "O_Html_Layout";
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