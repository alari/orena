<?php

class O_Dao_Renderer_Edit_Callbacks {

	/**
	 * Simple atomic field
	 *
	 * @param O_Dao_Renderer_Edit_Params $params
	 */
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
<input class="text" type="text" name="<?=$params->fieldName()?>"
	value="<?=htmlspecialchars( $params->value() )?>" /></div>
<?
	
	}

	/**
	 * Timestamp-datetime field
	 *
	 * @param O_Dao_Renderer_Edit_Params $params
	 */
	static public function timestamp( O_Dao_Renderer_Edit_Params $params )
	{
		$date = Array ("d" => "", "m" => "", "Y" => "", "H" => "", "i" => "");
		if (is_numeric( $params->value() )) {
			$date_a = explode( " ", date( "d m Y H i", $params->value() ), 5 );
			$date[ "d" ] = array_shift( $date_a );
			$date[ "m" ] = array_shift( $date_a );
			$date[ "Y" ] = array_shift( $date_a );
			$date[ "H" ] = array_shift( $date_a );
			$date[ "i" ] = array_shift( $date_a );
		} elseif (is_array( $params->value() )) {
			$date = $params->value();
		}
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
<input type="text" name="<?=$params->fieldName()?>[d]" maxlength="2"
	size="2" value="<?=$date[ "d" ]?>" />.<select
	name="<?=$params->fieldName()?>[m]"><?
		for ($j = 1; $j <= 12; $j++)
			echo "<option" . ($j == $date[ "m" ] ? " selected=\"yes\"" : "") . ">$j</option>";
		?>
	</select>.<input type="text" name="<?=$params->fieldName()?>[Y]"
	maxlength="4" size="4" value="<?=$date[ "Y" ]?>" /> &nbsp; <input
	type="text" name="<?=$params->fieldName()?>[H]" maxlength="2" size="2"
	value="<?=$date[ "H" ]?>" />:<input type="text"
	name="<?=$params->fieldName()?>[i]" maxlength="2" size="2"
	value="<?=$date[ "i" ]?>" /></div>
<?
	
	}

	/**
	 * File type field
	 *
	 * @param O_Dao_Renderer_Edit_Params $params
	 */
	static public function file( O_Dao_Renderer_Edit_Params $params )
	{
		?>
<div class="oo-renderer-field">
<div class="oo-renderer-title">
<?=$params->title()?>:</div>
<input class="file" type="file" name="<?=$params->fieldName()?>" />
<?
		if ($params->error()) {
			?>
<div class="oo-renderer-error">
<?=$params->error()?>
</div>
<?
		}
		?>
</div>
<?
	
	}

	/**
	 * For atomic fields with -enum key
	 *
	 * @param O_Dao_Renderer_Edit_Params $params
	 */
	static public function enum( O_Dao_Renderer_Edit_Params $params )
	{
		$fieldInfo = O_Dao_TableInfo::get( $params->className() )->getFieldInfo( 
				$params->fieldName() );
		?>
<div class="oo-renderer-field">
<div class="oo-renderer-title">
<?=$params->title()?>:</div>
<select name="<?=$params->fieldName()?>">
	<?
		foreach ($fieldInfo->getParam( "enum", 1 ) as $v) {
			?>
		<option <?=($v == $params->value() ? ' selected="yes"' : "")?>><?=$v?></option>
	<?
		}
		?>
</select>
<?
		if ($params->error()) {
			?>
<div class="oo-renderer-error">
<?=$params->error()?>
</div>
<?
		}
		
		?>
</div>
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
		$params = new O_Dao_Renderer_Edit_Params( $params->fieldName(), $params->className(), 
				$subparams, $params->record() );
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
			$params->layout()->addJavaScriptSrc( 
					$params->layout()->staticUrl( "fckeditor/fckeditor.js", 1 ) );
			
			$customConfig = O_Registry::get( "app/js/fckeditor/config_path" );
			$toolbarSet = $params->params();
			$height = 0;
			if (!$toolbarSet)
				$toolbarSet = O_Registry::get( "app/js/fckeditor/toolbar_set" );
			elseif (strpos( $toolbarSet, " " )) {
				list ($toolbarSet, $height) = explode( " ", $toolbarSet, 2 );
			}
			O_Js_Middleware::getFramework()->addDomreadyCode( 
					"
var oFCKeditor = new FCKeditor( 'oo-r-w-" . $params->fieldName() . "' );
oFCKeditor.BasePath = '" .
						 $params->layout()->staticUrl( 'fckeditor/', 1 ) . "';" . ($customConfig ? 'oFCKeditor.Config["CustomConfigurationsPath"] = "' .
						 $customConfig . '";' : "") . ($toolbarSet ? "oFCKeditor.ToolbarSet = '" .
						 $toolbarSet . "';" : "") . ($height ? "oFCKeditor.Height = '" . $height .
						 "';" : "") . "oFCKeditor.ReplaceTextarea();", $params->layout() );
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
<textarea class="fckeditor fck-<?=$toolbarSet?>"
	id="oo-r-w-<?=$params->fieldName()?>" name="<?=$params->fieldName()?>"><?=$params->value()?></textarea></div>
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

	/**
	 * Select relation from query via select tag
	 *
	 * @param O_Dao_Renderer_Edit_Params $params
	 */
	static public function selectRelation( O_Dao_Renderer_Edit_Params $params )
	{
		$_params = $params->params();
		$displayField = $_params[ "displayField" ];
		$size = 1;
		$multiply = $_params[ "multiply" ];
		$value = $params->value();
		if ($multiply)
			$size = 3;
		?>
<div class="oo-renderer-field">
<div class="oo-renderer-title">
<?=$params->title()?>:</div>
<select class="oo-renderer-selectRelation"
	name="<?=($params->fieldName() . ($multiply ? "[]" : ""))?>"
	size="<?=$size?>" <?=($multiply ? ' multiple="yes"' : '')?>>
<?
		foreach ($_params[ "query" ] as $obj) {
			?><option value="<?=$obj->id?>"
		<?=(isset( $value[ $obj->id ] ) || $value == $obj ? ' selected="yes"' : '')?>><?=$obj->$displayField?></option><?
		}
		?>
</select>
<?
		if ($params->error()) {
			?>
<div class="oo-renderer-error">
<?=$params->error()?>
</div>
<?
		}
		?>
</div>
<?
	}

	/**
	 * Select from a relation query via checkbox or radio
	 *
	 * @param O_Dao_Renderer_Edit_Params $params
	 */
	static public function selectRelationBox( O_Dao_Renderer_Edit_Params $params )
	{
		$_params = $params->params();
		
		$displayField = $_params[ "displayField" ];
		$multiply = $_params[ "multiply" ];
		$value = $params->value();
		if ($multiply) {
			$type = "checkbox";
			$name = $params->fieldName() . "[]";
		} else {
			$type = "radio";
			$name = $params->fieldName();
		}
		
		$echoed = 0;
		?>
<div class="oo-renderer-field">
<div class="oo-renderer-title">
<?=$params->title()?>:</div>
<div class="oo-renderer-selectRelation">
<?
		foreach ($_params[ "query" ] as $obj) {
			if ($echoed)
				echo ", ";
			else
				$echoed = 1;
			?>
	<label><input class="<?=$type?>" type="<?=$type?>" name="<?=$name?>"
	value="<?=$obj->id?>"
	<?=(isset( $value[ $obj->id ] ) ? ' checked="yes"' : '')?>> &ndash; <?=$obj->$displayField . "</label>";}?>
</div>
<?
		if ($params->error()) {
			?>
<div class="oo-renderer-error">
<?=$params->error()?>
</div>
<?
		}
		?>
</div>
<?
	}

}
