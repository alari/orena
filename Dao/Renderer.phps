<?php
class Dao_Renderer {

	/**
	 * Renders loop query / without pager
	 *
	 * @param Dao_Query $query
	 * @param Html_Layout $layout
	 */
	static public function showLoop( Dao_Query $query, Html_Layout $layout = null )
	{
		// TODO: add more complex logic for loop renderer, e.g. envelop
		foreach ($query as $record) {
			$renderer = new Html_DaoRenderer_Show( $record, $layout, "loop" );
			$renderer->display();
		}
	}

	/**
	 * Echoes an object in HTML
	 *
	 * @param Dao_ActiveRecord $obj
	 */
	static public function show( Dao_ActiveRecord $record, Html_Layout $layout = null )
	{
		$renderer = new Html_DaoRenderer_Show( $record, $layout );
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