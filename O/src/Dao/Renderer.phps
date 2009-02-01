<?php
/**
 * Renders an object, an object set as loop, or a form based on ActiveRecord class or object.
 *
 * @see O_Html_DaoRenderer_Loop
 * @see O_Html_DaoRenderer_Show
 * @see O_Html_DaoRenderer_Form
 *
 * @author Dmitry Kourinski
 */
class O_Dao_Renderer {
	
	const MODE_SHOW = "show";
	const MODE_LOOP = "loop";

	/**
	 * Renders loop query
	 *
	 * @see O_Html_DaoRenderer_Loop
	 * @param O_Dao_Query $query
	 * @param O_Html_Layout $layout
	 * @param string $subparams
	 */
	static public function showLoop( O_Dao_Query $query, O_Html_Layout $layout = null, $subparams = "" )
	{
		$renderer = new O_Html_DaoRenderer_Loop( $query, $layout, $subparams );
		$renderer->display();
	}

	/**
	 * Echoes an object in HTML
	 *
	 * @see O_Html_DaoRenderer_Show
	 * @param O_Dao_ActiveRecord $obj
	 * @param O_Html_Layout $layout
	 * @param string $mode
	 */
	static public function show( O_Dao_ActiveRecord $record, O_Html_Layout $layout = null, $mode = self::MODE_SHOW )
	{
		$renderer = new O_Html_DaoRenderer_Show( $record, $layout, $mode );
		$renderer->display();
	}

	/**
	 * Echoes rendered create form, similar to edit form
	 *
	 * @param string $class
	 * @param string $action
	 * @param O_Html_Layout $layout
	 * @param bool $isAjax
	 * @param array $errorsArray
	 * @param string $formTitle
	 * @see O_Html_DaoRenderer_Form
	 */
	static public function create( $class, $action, O_Html_Layout $layout = null, $isAjax = false, Array $errorsArray = Array(), $formTitle = "" )
	{
		$renderer = new O_Html_DaoRenderer_Form( $action, $layout );
		$renderer->setActiveRecordClass( $class );
		if ($formTitle)
			$renderer->setTitle( $formTitle );
		$renderer->setAjaxMode( $isAjax );
		$renderer->setErrorsArray( $errorsArray );
		$renderer->display();
	}

	/**
	 * Echoes rendered edit form
	 *
	 * @param O_Dao_ActiveRecord $record
	 * @param string $action
	 * @param O_Html_Layout $layout
	 * @param bool $isAjax
	 * @param array $errorsArray
	 * @param string $formTitle
	 * @see O_Html_DaoRenderer_Form
	 */
	static public function edit( O_Dao_ActiveRecord $record, $action, O_Html_Layout $layout = null, $isAjax = false, Array $errorsArray = Array(), $formTitle = "" )
	{
		$renderer = new O_Html_DaoRenderer_Form( $action, $layout );
		$renderer->setActiveRecord( $record );
		if ($formTitle)
			$renderer->setTitle( $formTitle );
		$renderer->setAjaxMode( $isAjax );
		$renderer->setErrorsArray( $errorsArray );
		$renderer->display();
	}
}