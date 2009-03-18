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
	public function addDomreadyCode( $code, O_Html_Layout $layout = null )
	{
		$code = "$(window).addEvent('domready', function(){ $code });";
		if ($layout) {
			$layout->addJavaScriptSrc( $layout->staticUrl( "mootools/core.js", 1 ) );
			$layout->addJavaScriptCode( $code );
		}
		return $code;
	}

	/**
	 * Adds framework sources to layout
	 *
	 * @param O_Html_Layout $layout
	 */
	public function addSrc( O_Html_Layout $layout )
	{
		$layout->addJavaScriptSrc( $layout->staticUrl( "mootools/core.js", 1 ) );
		$layout->addJavaScriptSrc( $layout->staticUrl( "mootools/more.js", 1 ) );
	}

	/**
	 * Returns fragment of javascript to set element contents to result of ajax post request.
	 *
	 * @param string $elementId
	 * @param string $url
	 * @param array $send_params
	 * @param O_Html_Layout $layout
	 * @return string
	 */
	public function ajaxHtml( $elementId, $url, array $send_params = array(), O_Html_Layout $layout = null )
	{
		if ($layout)
			$this->addSrc( $layout );
		$params = "";
		if (count( $send_params )) {
			foreach ($send_params as $k => $v) {
				$params .= ($params ? "," : "") . $k . ":'" . htmlspecialchars( $v ) . "'";
			}
			$params = "{" . $params . "}";
		}
		return "new Request.HTML({url:'$url',method:'POST',update:$('$elementId')}).post($params);";
	}

}