<?php

class O_Dao_Renderer_ShowCallbacks {

	static public function envelop( $contents, $className )
	{
		echo "<div", $className ? ' class="' . $className . '"' : "", ">", $contents, "</div>";
	}

	static public function simple( $value )
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
	static public function wysiwyg( $fieldValue )
	{
		echo '<div id="oo-renderer-wysiwyg">', $fieldValue, "</div>";
	}

	/**
	 * Simple textarea
	 *
	 * @param string $fieldValue
	 * @param string $title
	 * @param string $subparams
	 */
	static public function area( $fieldValue )
	{
		echo '<div id="oo-renderer-area">', htmlspecialchars( $fieldValue ), "</div>";
	}

	/**
	 * Shows field values in a loop mode
	 *
	 * @param O_Dao_Query $fieldValue
	 * @param string $title
	 * @param string $subparams
	 */
	static public function loop( O_Dao_Query $fieldValue, $params, $layout )
	{
		echo $fieldValue->show($layout);
	}

	static public function count( O_Dao_Query $fieldValue, $params, $layout )
	{
		echo $params.": ".count($fieldValue), "<br/>";
	}

	static public function linkInContainer($fieldValue, $params, $layout, $record) {
		$fieldValue = "<a href=\"".$record->url()."\">$fieldValue</a>";
		self::container($fieldValue, $params, $layout);
	}


	static public function container( $fieldValue, $params, $layout )
	{
		$class = null;
		$tag = $params;
		if(strpos($params, " ")) list($tag, $class) = explode(" ", $params, 2);
		if(!$tag) $tag = "span";
		echo "<$tag".($class ? " class=\"$class\"" : "").">", $fieldValue, "</$tag>";
	}

	static public function date($fieldValue, $params) {
		if(!$params) $params = "d.m.Y H:i:s";
		echo date($params, $fieldValue), "<br/>";
	}


	static public function activeRecord( O_Dao_ActiveRecord $fieldValue, $params, $layout )
	{
		$fieldValue->show($layout, $params ? $params : O_Dao_Renderer::TYPE_DEF);
	}
}