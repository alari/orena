<?php
/**
 * Shortcut class for rendering and handling Active Records.
 *
 * @see O_Dao_Renderer_FormProcessor
 * @see O_Dao_Renderer_Shower
 *
 * @copyright Dmitry Kourinski
 */
class O_Dao_Renderer {
	const CALLBACK_SHOW = "O_Dao_Renderer_ShowCallbacks";
	const CALLBACK_EDIT = "O_Dao_Renderer_EditCallbacks";
	const CALLBACK_CHECK = "O_Dao_Renderer_CheckCallbacks";
	
	const KEY_SHOW = "show";
	const KEY_EDIT = "edit";
	const KEY_CHECK = "check";
	
	const TYPE_DEF = "def";
	const TYPE_LOOP = "loop";

	/**
	 * Shows one active record.
	 *
	 * @param O_Dao_ActiveRecord $record
	 * @param O_Html_Layout $layout
	 * @param string $type
	 */
	static public function show( O_Dao_ActiveRecord $record, O_Html_Layout $layout = null, $type = self::TYPE_DEF )
	{
		if (!$type)
			$type = self::TYPE_DEF;
		$renderer = new O_Dao_Renderer_Shower( );
		$renderer->setActiveRecord( $record );
		if ($layout)
			$renderer->setLayout( $layout );
		if ($type)
			$renderer->setType( $type );
		$renderer->display();
	}

	/**
	 * Shows all query records in a loop.
	 *
	 * @param O_Dao_Query $query
	 * @param O_Html_Layout $layout
	 * @param string $type
	 */
	static public function showLoop( O_Dao_Query $query, O_Html_Layout $layout = null, $type = self::TYPE_LOOP )
	{
		if (!$type)
			$type = self::TYPE_LOOP;
		foreach ($query as $obj) {
			self::show( $obj, $layout, $type );
		}
	}

}