<?php

class O_Dao_Renderer_Edit_Callbacks {

	/**
	 * Simple atomic field
	 *
	 * @param O_Dao_Renderer_Edit_Params $params
	 */
	static public function simple( O_Dao_Renderer_Edit_Params $params )
	{
		$row = $params->getFormRowField( "O_Form_Row_String" );
		$row->render( $params->layout() );
	}

	/**
	 * Timestamp-datetime field
	 *
	 * @param O_Dao_Renderer_Edit_Params $params
	 */
	static public function timestamp( O_Dao_Renderer_Edit_Params $params )
	{
		$row = $params->getFormRowField( "O_Form_Row_DateTime" );
		$row->render( $params->layout() );
	}

	/**
	 * File type field
	 *
	 * @param O_Dao_Renderer_Edit_Params $params
	 */
	static public function file( O_Dao_Renderer_Edit_Params $params )
	{
		$row = $params->getFormRowField( "O_Form_Row_File" );
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

		$row = $params->getFormRowField( "O_Form_Row_Select" );
		$row->setOptions( $fieldInfo->getParam( "enum", 1 ) );
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
		$row = $params->getFormRowField( "O_Form_Row_Wysiwyg" );
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
		$row = $params->getFormRowField( "O_Form_Row_Text" );
		$row->render( $params->layout() );
	}

	/**
	 * Select relation from query via select tag
	 *
	 * @param O_Dao_Renderer_Edit_Params $params
	 */
	static public function selectRelation( O_Dao_Renderer_Edit_Params $params )
	{
		$row = $params->getFormRowField( "O_Form_Row_Select" );

		$_params = $params->params();
		if ($_params[ "multiply" ]) {
			$row->setMultiple();
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

		$row = $params->getFormRowField( "O_Form_Row_BoxList" );
		$row->setOptions( $_params[ "query" ], $_params[ "displayField" ] );
		if ($_params[ "multiply" ])
			$row->setMultiple();

		$row->render( $params->layout() );
	}

}
