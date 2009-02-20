<?php
/**
 * Mootools framework middleware.
 *
 * @see O_Js_Middleware
 * @see O_Js_iFramework
 *
 * @copyright Dmitry Kourinski
 */
class O_Js_Mootools implements O_Js_iFramework {
	/**
	 * Adds JS code to be executed when DOM is ready
	 *
	 * @param string $code
	 * @param O_Html_Layout $layout
	 * @return string If layout is given, code is added in its head
	 */
	public function addDomreadyCode($code, O_Html_Layout $layout = null )
	{
		$code = "$(window).addEvent('domready', function(){ $code });";
		if($layout) {
			$layout->addJavaScriptSrc($layout->staticUrl("mootools/core.js",1));
			$layout->addJavaScriptCode($code);
		}
		return $code;
	}
}