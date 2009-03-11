<?php

class O_Dao_Renderer_EditCallbacks {

	static public function simple( $fieldName, $fieldValue, $title, $params, $layout, $error )
	{
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
<input type="text" name="<?=$fieldName?>"
	value="<?=htmlspecialchars( $fieldValue )?>" /></div>
<?
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
			// TODO add additional config file, get toolbar from params with somethink as the default
			//
			$customConfig = O_Registry::get( "app/js/fckeditor/config_path" );
			$toolbarSet = O_Registry::get( "app/js/fckeditor/toolbar_set" );
			O_Js_Middleware::getFramework()->addDomreadyCode( 
					"
var oFCKeditor = new FCKeditor( 'oo-r-w-$fieldName' );
oFCKeditor.BasePath = '" . $layout->staticUrl( 'fckeditor/', 1 ) . "';" .
						 ($customConfig ? 'oFCKeditor.Config["CustomConfigurationsPath"] = "' . $customConfig . '";' : "") .
						 ($toolbarSet ? "oFCKeditor.ToolbarSet = '" . $toolbarSet . "';" : "") . "oFCKeditor.ReplaceTextarea();", 
						$layout );
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
	static public function area( $fieldName, $fieldValue, $title, $params, $layout, $error )
	{
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
<textarea class="oo-renderer-field-area" name="<?=$fieldName?>"><?=$fieldValue?></textarea></div>
<?
	}

	static public function selectRelation( $fieldName, $fieldValue, $title, $params, $layout, $error )
	{
		$displayField = $params[ "displayField" ];
		$size = 1;
		$multiply = $params[ "multiply" ];
		if ($multiply)
			$size = 3;
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
<select class="oo-renderer-selectRelation"
	name="<?=($fieldName . ($multiply ? "[]" : ""))?>" size="<?=$size?>">
<?
		foreach ($params[ "query" ] as $obj) {
			?><option value="<?=$obj->id?>"><?=$obj->$displayField?></option><?
		}
		?>
</select></div>
<?
	}

}