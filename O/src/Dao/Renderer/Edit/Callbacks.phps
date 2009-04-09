<?php

class O_Dao_Renderer_Edit_Callbacks {

	static public function simple( O_Dao_Renderer_Edit_Params $params )
	{
		?>
<div class="oo-renderer-field">
<div class="oo-renderer-title">
<?=$params->title()?>:</div>
<?
		if ($params->error()) {
			?>
<div class="oo-renderer-error">
<?=$params->error()?>
</div>
<?
		}
		?>
<input type="text" name="<?=$params->fieldName()?>"
	value="<?=htmlspecialchars( $params->value() )?>" /></div>
<?
	}

	static public function recordField( O_Dao_Renderer_Edit_Params $params )
	{
		$field = $params->params();
		$subparams = $params->params();
		if (strpos( $field, " " ))
			list ($field, $subparams) = explode( " ", $field, 2 );
		$value = $params->value();
		$title = $params->title();
		$error = $params->error();
		$params = new O_Dao_Renderer_Edit_Params( $params->fieldName(), $params->className(), $subparams, 
				$params->record() );
		if ($value instanceof O_Dao_ActiveRecord)
			$params->setValue( $value->$field );
		$params->setTitle( $title );
		$params->setError( $error );
		self::simple( $params );
	}

	/**
	 * WYSIWYG editor
	 *
	 * @param string $fieldValue
	 * @param string $title
	 * @param string $subparams
	 */
	static public function wysiwyg( O_Dao_Renderer_Edit_Params $params )
	{
		if ($params->layout()) {
			$params->layout()->addJavaScriptSrc( $params->layout()->staticUrl( "fckeditor/fckeditor.js", 1 ) );
			// TODO add additional config file, get toolbar from params with something as the default
			//
			$customConfig = O_Registry::get( "app/js/fckeditor/config_path" );
			$toolbarSet = O_Registry::get( "app/js/fckeditor/toolbar_set" );
			O_Js_Middleware::getFramework()->addDomreadyCode( 
					"
var oFCKeditor = new FCKeditor( 'oo-r-w-" . $params->fieldName() . "' );
oFCKeditor.BasePath = '" . $params->layout()->staticUrl( 'fckeditor/', 1 ) . "';" . ($customConfig ? 'oFCKeditor.Config["CustomConfigurationsPath"] = "' .
						 $customConfig . '";' : "") . ($toolbarSet ? "oFCKeditor.ToolbarSet = '" . $toolbarSet . "';" : "") .
						 "oFCKeditor.ReplaceTextarea();", $params->layout() );
		}
		?>
<div class="oo-renderer-field">
<?
		if ($params->title() !== 1) {
			?><div class="oo-renderer-title">
<?=$params->title()?>:</div><?
		}
		?>
<?

		if ($params->error()) {
			?>
<div class="oo-renderer-error">
<?=$params->error()?>
</div>
<?
		}
		?>
<textarea class="fckeditor" id="oo-r-w-<?=$params->fieldName()?>"
	name="<?=$params->fieldName()?>"><?=$params->value()?></textarea></div>
<?
	}

	/**
	 * Simple textarea
	 *
	 * @param string $fieldValue
	 * @param string $title
	 * @param string $subparams
	 */
	static public function area( O_Dao_Renderer_Edit_Params $params )
	{
		?>
<div class="oo-renderer-field">
<div class="oo-renderer-title">
<?=$params->title()?>:</div>
<?
		if ($params->error()) {
			?>
<div class="oo-renderer-error">
<?=$params->error()?>
</div>
<?
		}
		?>
<textarea class="oo-renderer-field-area"
	name="<?=$params->fieldName()?>"><?=$params->value()?></textarea></div>
<?
	}

	static public function selectRelation( O_Dao_Renderer_Edit_Params $params )
	{
		$_params = $params->params();
		$displayField = $_params[ "displayField" ];
		$size = 1;
		$multiply = $_params[ "multiply" ];
		if ($multiply)
			$size = 3;
		?>
<div class="oo-renderer-field">
<div class="oo-renderer-title">
<?=$params->title()?>:</div>
<?
		if ($params->error()) {
			?>
<div class="oo-renderer-error">
<?=$params->error()?>
</div>
<?
		}
		?>
<select class="oo-renderer-selectRelation"
	name="<?=($params->fieldName() . ($multiply ? "[]" : ""))?>"
	size="<?=$size?>">
<?
		foreach ($_params[ "query" ] as $obj) {
			?><option value="<?=$obj->id?>"><?=$obj->$displayField?></option><?
		}
		?>
</select></div>
<?
	}

}