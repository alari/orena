<?php
/**
 * Mootools framework middleware.
 *
 * @see O_Js_Middleware
 * @see O_Js_iFramework
 *
 * @author Dmitry Kurinskiy
 */
class O_Js_Mootools implements O_Js_iFramework {
	private $dependerUsed = null;

	public function __construct() {
		$this->dependerUsed = O_Registry::get("app/js/use_depender");
	}

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
			$this->addSrc($layout);
			if($this->dependerUsed) {
				$code = "Depender.require({scripts:['DomReady'],callback:function(){ $code }});";
			}
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
		if($this->dependerUsed) {
			$layout->addJavaScriptSrc( $layout->staticUrl("mootools/depender/php/build.php?client=true&require=Om",1) );
		} else {
			$layout->addJavaScriptSrc( $layout->staticUrl( "mootools/core.js", 1 ) );
			$layout->addJavaScriptSrc( $layout->staticUrl( "mootools/more.js", 1 ) );
		}
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
		if($this->dependerUsed) {
			return "Om.getHtml('$url','$elementId',$params);";
		} else {
			return "new Request.HTML({url:'$url',method:'POST',update:$('$elementId')}).post($params);";
		}
	}

	public function ajaxForm($instanceId) {
?>
<script language="JavaScript" type="text/javascript">
<?if($this->dependerUsed) echo "Om.use('Om.FormSender', function(){Om.attachFormSender('$instanceId');});"; else {?>
var _getEl = function(){
	el = $('<?=$instanceId?>');
	if(!el) {
		_getEl.delay(50);
		return;
	}
	el.getElements('input[type=submit]').addEvent("click", function(e){
		 e.stop();
		 el.getElements('input[type=submit]').setProperty("disabled", true);

	 	el.getElements('textarea.fckeditor').each(function(_el){
			_el.value = FCKeditorAPI.GetInstance(_el.id). GetXHTML( 1 );
	 	 });

	 	new Request.JSON({url:el.getAttribute('action'), onSuccess:function(response){
			if(response.status == 'SUCCEED') {
				if(response.refresh == 1) {
					window.location.reload(true);
				} else if(response.show) {
					el.getParent().set('html', response.show);
				} else if(response.redirect) {
					window.location.href = response.redirect;
				}
			} else {
				el.getElements('.form-row-error').dispose();
				for(field in response.errors) {
					erre = el.getElement('[name='+field+']').getParent();
					if(!erre) erre = el.getChildren().getLast();
					err = new Element('div', {class:'form-row-error'});
					err.set('html', response.errors[field]);
					err.inject(erre, 'after');
				}
				el.getElements('input[type=submit]').setProperty("disabled", false);
			}
	 	 }}).post(el);
	 });
};
_getEl();
<?}?>
 </script>
<?
	}


}