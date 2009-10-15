<?php

class O_Dao_Renderer_Edit_Callbacks {

	/**
	 * Simple atomic field
	 *
	 * @param O_Dao_Renderer_Edit_Params $params
	 */
	static public function simple( O_Dao_Renderer_Edit_Params $params )
	{
		$row = new O_Form_Row_String( $params->fieldName(), $params->params() );
		$row->setTitle( $params->title() );
		$row->setError( $params->error() );
		$row->setValue( $params->value() );
		$row->render( $params->layout() );
	}

	/**
	 * Timestamp-datetime field
	 *
	 * @param O_Dao_Renderer_Edit_Params $params
	 */
	static public function timestamp( O_Dao_Renderer_Edit_Params $params )
	{
		$row = new O_Form_Row_DateTime( $params->fieldName(), $params->params() );
		$row->setTitle( $params->title() );
		$row->setError( $params->error() );
		$row->setValue( $params->value() );
		$row->render( $params->layout() );
	}

	/**
	 * File type field
	 *
	 * @param O_Dao_Renderer_Edit_Params $params
	 */
	static public function file( O_Dao_Renderer_Edit_Params $params )
	{
		$row = new O_Form_Row_File( $params->fieldName(), $params->params() );
		$row->setTitle( $params->title() );
		$row->setError( $params->error() );
		$row->setValue( $params->value() );
		$row->render( $params->layout() );
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

		$row = new O_Form_Row_Select( $params->fieldName(), $params->params() );
		$row->setTitle( $params->title() );
		$row->setError( $params->error() );
		$row->setValue( $params->value() );
			$row->setOptions($fieldInfo->getParam( "enum", 1 ));
		$row->render( $params->layout() );
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
		$row = new O_Form_Row_Wysiwyg( $params->fieldName(), $params->params() );
			if($params->title() != 1) $row->setTitle( $params->title() );
		$row->setError( $params->error() );
		$row->setValue( $params->value() );
		$row->render( $params->layout() );
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
		$row = new O_Form_Row_Text( $params->fieldName(), $params->params() );
		$row->setTitle( $params->title() );
		$row->setError( $params->error() );
		$row->setValue( $params->value() );
		$row->render( $params->layout() );
	}

	/**
	 * Select relation from query via select tag
	 *
	 * @param O_Dao_Renderer_Edit_Params $params
	 */
	static public function selectRelation( O_Dao_Renderer_Edit_Params $params )
	{
		$row = new O_Form_Row_Select( $params->fieldName(), $params->params() );
		$row->setTitle( $params->title() );
		$row->setError( $params->error() );
		$row->setValue( $params->value() );

			$_params = $params->params();
			if ($_params[ "multiply" ]) {
				$row->setMultiply();
			}
			$row->setOptions( $_params[ "query" ], $_params[ "displayField" ] );

		$row->render( $params->layout() );
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

		$row = new O_Form_Row_BoxList( $params->fieldName(), $params->params() );
			$row->setVariants( $_params[ "query" ], $_params[ "displayField" ] );
		$row->setTitle( $params->title() );
		$row->setError( $params->error() );
		$row->setValue( $params->value() );
			if ($multiply)
				$row->setMultiply();

		$row->render($params->layout());
	}

}
