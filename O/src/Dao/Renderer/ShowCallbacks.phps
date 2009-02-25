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
	static public function loop( O_Dao_Query $fieldValue )
	{
		echo "LOOP";
	}

	static public function activeRecord( O_Dao_ActiveRecord $fieldValue )
	{
		echo "LOOP";
	}
}