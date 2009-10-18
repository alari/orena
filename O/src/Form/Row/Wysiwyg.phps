<?php
class O_Form_Row_Wysiwyg extends O_Form_Row_Field {
	
	protected $isVertical = true;

	public function renderInner( O_Html_Layout $layout = null, $isAjax = false )
	{
		$toolbarSet = "";
		if ($layout) {
			$layout->addJavaScriptSrc( $layout->staticUrl( "fckeditor/fckeditor.js", 1 ) );
			
			$customConfig = O_Registry::get( "app/js/fckeditor/config_path" );
			$toolbarSet = $this->params;
			$height = 0;
			if (!$toolbarSet) {
				$toolbarSet = O_Registry::get( "app/js/fckeditor/toolbar_set" );
			} elseif (strpos( $toolbarSet, " " )) {
				list ($toolbarSet, $height) = explode( " ", $toolbarSet, 2 );
			}
			O_Js_Middleware::getFramework()->addDomreadyCode( 
					"
var oFCKeditor = new FCKeditor( 'form-wysiwyg-" . $this->name . "' );
oFCKeditor.BasePath = '" .
						 $layout->staticUrl( 'fckeditor/', 1 ) . "';" . ($customConfig ? 'oFCKeditor.Config["CustomConfigurationsPath"] = "' .
						 $customConfig . '";' : "") . ($toolbarSet ? "oFCKeditor.ToolbarSet = '" .
						 $toolbarSet . "';" : "") . ($height ? "oFCKeditor.Height = '" . $height .
						 "';" : "") . "oFCKeditor.ReplaceTextarea();", $layout );
		}
		?>
<textarea
	class="fckeditor<?=($toolbarSet ? " fck-" . $toolbarSet : "")?>"
	id="form-wysiwyg-<?=$this->name?>" name="<?=$this->name?>"><?=$this->value?></textarea>
<?
	}
}