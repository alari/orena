<?php

class O_Dao_Renderer_Show_Callbacks {

	/**
	 * Div envelop container
	 *
	 * @param O_Dao_Renderer_Show_Params $params
	 */
	static public function envelop( O_Dao_Renderer_Show_Params $params )
	{
		echo "<div", $params->params() ? ' class="' . $params->params() . '"' : "", ">", $params->value(), "</div>";
	}

	/**
	 * Just echoes value
	 *
	 * @param O_Dao_Renderer_Show_Params $params
	 */
	static public function simple( O_Dao_Renderer_Show_Params $params )
	{
		echo $params->value();
	}

	/**
	 * Htmlspecialchars
	 *
	 * @param O_Dao_Renderer_Show_Params $params
	 */
	static public function htmlspecialchars( O_Dao_Renderer_Show_Params $params )
	{
		echo '<div>', htmlspecialchars( $params->value() ), "</div>";
	}

	/**
	 * Shows field values in a loop mode
	 *
	 * @param O_Dao_Renderer_Show_Params $params
	 */
	static public function loop( O_Dao_Renderer_Show_Params $params )
	{
		if ($params->value() instanceof O_Dao_Query) {
			$params->value()->show( $params->layout(), $params->params() ? $params->params() : O_Dao_Renderer::TYPE_LOOP );
		} else {
			echo "<!-- error -->";
		}
	}

	/**
	 * Counts value, with params as title
	 *
	 * @param O_Dao_Renderer_Show_Params $params
	 */
	static public function count( O_Dao_Renderer_Show_Params $params )
	{
		
		echo $params->params() . ": " . count( $params->value() ), "<br/>";
	}

	/**
	 * Calls $record->url() to build link and puts it into container
	 *
	 * @param O_Dao_Renderer_Show_Params $params
	 */
	static public function linkInContainer( O_Dao_Renderer_Show_Params $params )
	{
		$params->setValue( "<a href=\"" . $params->record()->url() . "\">" . $params->value() . "</a>" );
		self::container( $params );
	}

	/**
	 * Tag container with css class. Params like: "$tag $class"
	 *
	 * @param O_Dao_Renderer_Show_Params $params
	 */
	static public function container( O_Dao_Renderer_Show_Params $params )
	{
		$class = null;
		$tag = $params->params();
		if (strpos( $params->params(), " " ))
			list ($tag, $class) = explode( " ", $params->params(), 2 );
		if (!$tag)
			$tag = "span";
		echo "<$tag" . ($class ? " class=\"$class\"" : "") . ">", $params->value(), "</$tag>";
	}

	/**
	 * Shows date with params as format
	 *
	 * @param O_Dao_Renderer_Show_Params $params
	 */
	static public function date( O_Dao_Renderer_Show_Params $params )
	{
		$format = $params->params();
		if (!$format)
			$format = "d.m.Y H:i:s";
		echo date( $format, $params->value() ), "<br/>";
	}

	/**
	 * Shows active record object with params as type
	 *
	 * @param O_Dao_Renderer_Show_Params $params
	 */
	static public function activeRecord( O_Dao_Renderer_Show_Params $params )
	{
		if ($params->value() instanceof O_Dao_ActiveRecord)
			$params->value()->show( $params->layout(), $params->params() ? $params->params() : O_Dao_Renderer::TYPE_DEF );
	}
}