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

	/**
	 * Returns fragment of javascript to set element contents to result of ajax post request.
	 *
	 * @param string $elementId
	 * @param string $url
	 * @param array $send_params
	 * @param O_Html_Layout $layout
	 * @return string
	 */
	public function ajaxHtml( $elementId, $url, array $send_params = array(), O_Html_Layout $layout = null );
}