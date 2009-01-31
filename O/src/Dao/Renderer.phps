<?php
/**
 * Renders an object, an object set as loop, or a form based on ActiveRecord class or object.
 *
 * @see Html_DaoRenderer_Loop
 * @see Html_DaoRenderer_Show
 * @see Html_DaoRenderer_Form
 *
 * @author Dmitry Kourinski
 */
class Dao_Renderer {
	
	const MODE_SHOW = "show";
	const MODE_LOOP = "loop";

	/**
	 * Renders loop query
	 *
	 * @see Html_DaoRenderer_Loop
	 * @param Dao_Query $query
	 * @param Html_Layout $layout
	 * @param string $subparams
	 */
	static public function showLoop( Dao_Query $query, Html_Layout $layout = null, $subparams = "" )
	{
		$renderer = new Html_DaoRenderer_Loop( $query, $layout, $subparams );
		$renderer->display();
	}

	/**
	 * Echoes an object in HTML
	 *
	 * @see Html_DaoRenderer_Show
	 * @param Dao_ActiveRecord $obj
	 * @param Html_Layout $layout
	 * @param string $mode
	 */
	static public function show( Dao_ActiveRecord $record, Html_Layout $layout = null, $mode = self::MODE_SHOW )
	{
		$renderer = new Html_DaoRenderer_Show( $record, $layout, $mode );
		$renderer->display();
	}

	/**
	 * Echoes rendered create form, similar to edit form
	 *
	 * @param string $class
	 * @param string $action
	 * @param Html_Layout $layout
	 * @param bool $isAjax
	 * @param array $errorsArray
	 * @param string $formTitle
	 * @see Html_DaoRenderer_Form
	 */
	static public function create( $class, $action, Html_Layout $layout = null, $isAjax = false, Array $errorsArray = Array(), $formTitle = "" )
	{
		$renderer = new Html_DaoRenderer_Form( $action, $layout );
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
	 * @param Dao_ActiveRecord $record
	 * @param string $action
	 * @param Html_Layout $layout
	 * @param bool $isAjax
	 * @param array $errorsArray
	 * @param string $formTitle
	 * @see Html_DaoRenderer_Form
	 */
	static public function edit( Dao_ActiveRecord $record, $action, Html_Layout $layout = null, $isAjax = false, Array $errorsArray = Array(), $formTitle = "" )
	{
		$renderer = new Html_DaoRenderer_Form( $action, $layout );
		$renderer->setActiveRecord( $record );
		if ($formTitle)
			$renderer->setTitle( $formTitle );
		$renderer->setAjaxMode( $isAjax );
		$renderer->setErrorsArray( $errorsArray );
		$renderer->display();
	}
}