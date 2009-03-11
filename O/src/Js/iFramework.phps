<?php
/**
 * Allows to use different frameworks solving the simpliest JS tasks.
 *
 * @copyright Dmitry Kourinski
 */
interface O_Js_iFramework {

	/**
	 * Adds JS code to be executed when DOM is ready
	 *
	 * @param string $code
	 * @param O_Html_Layout $layout
	 * @return string If layout is given, code is added in its head
	 */
	public function addDomreadyCode( $code, O_Html_Layout $layout = null );

	/**
	 * Adds framework sources to layout
	 *
	 * @param O_Html_Layout $layout
	 */
	public function addSrc( O_Html_Layout $layout );
}