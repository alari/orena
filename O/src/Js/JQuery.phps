<?php
/**
 * jQuery framework middleware.
 *
 * @see O_Js_Middleware
 * @see O_Js_iFramework
 *
 * @author Dmitry Kurinskiy
 */
class O_Js_JQuery implements O_Js_iFramework {

	/**
	 * Adds JS code to be executed when DOM is ready
	 *
	 * @param string $code
	 * @param O_Html_Layout $layout
	 * @return string If layout is given, code is added in its head
	 */
	public function addDomreadyCode( $code, O_Html_Layout $layout = null )
	{
		$code = "$(document).ready(function(){ $code });";
		if ($layout) {
			$this->addSrc($layout);
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
		$layout->addJavaScriptSrc( $layout->staticUrl( "jquery/jquery-min.js", 1 ) );
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
		return "$('#$elementId').load('$url',$params);";
	}

	public function ajaxForm($instanceId) {
?>
<script language="JavaScript" type="text/javascript">
_getEl = function(){
	el = $('#<?=$instanceId?>');
	if(!el.length) {
		setTimeout('_getEl', 50);
		return;
	}

	el.submit(function(e) {
		$('input[type=submit]', el).attr("disabled", true);
		$('textarea.fckeditor', el).each(function(_el){
			_el.value = FCKeditorAPI.GetInstance(_el.id). GetXHTML( 1 );
		});
		$.post(
			el.attr("action"),
			el.serialize(),
			function(response){
				if(response.status == 'SUCCEED') {
					if(response.refresh == 1) {
						window.location.reload(true);
					} else if(response.show) {
						el.parent().html(response.show);
					} else if(response.redirect) {
						window.location.href = response.redirect;
					}
				} else {
					$('.form-row-error', el).remove();
					for(field in response.errors) {
						erre = $('[name='+field+']', el).parent();
						if(!erre) erre = el.children(':last');
						erre.after("<div class='form-row-error'>"+response.errors[field]+"</div>");
					}
					$('input[type=submit]', el).attr("disabled", false);
				}
			},
			"json"
		);
		return false;
	});
};
_getEl();
 </script>
<?
	}


}