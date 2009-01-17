<?php
class Dao_Renderer {

	/**
	 * Echoes an object in HTML
	 *
	 * @param Dao_ActiveRecord $obj
	 */
	static public function show( Dao_ActiveRecord $obj )
	{
		$obj;
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