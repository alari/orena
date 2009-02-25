<?php

class O_Dao_Renderer_EditCallbacks {

	static public function simple( $fieldValue, $fieldTitle, $params, $error )
	{
		echo $value;
	}

	/**
	 * WYSIWYG editor
	 *
	 * @param string $fieldValue
	 * @param string $title
	 * @param string $subparams
	 */
	static public function wysiwyg( $fieldName, $fieldValue, $title, $params, $layout, $error )
	{
		if ($layout) {
			$layout->addJavaScriptSrc( $layout->staticUrl( "fckeditor/fckeditor.js", 1 ) );
			O_Js_Middleware::getFramework()->addDomreadyCode( 
					"
var oFCKeditor = new FCKeditor( 'oo-r-w-$fieldName' );
oFCKeditor.BasePath = '" . $layout->staticUrl( 'fckeditor/', 1 ) . "';
oFCKeditor.ToolbarSet = 'Basic';
oFCKeditor.ReplaceTextarea();", $layout );
		}
		?>
<div class="oo-renderer-field">
<div class="oo-renderer-title">
<?=$title?>:</div>
<?
		if ($error) {
			?>
<div class="oo-renderer-error">
<?=$error?>
</div>
<?
		}
		?>
<textarea class="oo-renderer-field-wysiwyg" id="oo-r-w-<?=$fieldName?>"
	name="<?=$fieldName?>"><?=$fieldValue?></textarea></div>
<?
	}

	/**
	 * Simple textarea
	 *
	 * @param string $fieldValue
	 * @param string $title
	 * @param string $subparams
	 */
	static public function area( $fieldValue, $fieldTitle, $params, $error )
	{
		echo '<div id="oo-renderer-area">', htmlspecialchars( $fieldValue ), "</div>";
	}
}