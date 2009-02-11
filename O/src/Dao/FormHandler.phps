<?php
/**
 * Provides functionality for processing form submit for O_Dao_Renderer forms.
 *
 * @see O_Dao_Renderer::edit()
 * @see O_Dao_Renderer::create()
 *
 * @author Dmitry Kourinski
 */
class O_Dao_FormHandler {

	/**
	 * Processes create request, creates an ActiveRecord
	 *
	 * @param string $class
	 * @param string $create
	 * @return Array(errors=>array,values=>array) or null on success
	 */
	static public function create( $class )
	{
		return self::process( $class, true );
	}

	/**
	 * Processes edit request, edits an ActiveRecord
	 *
	 * @param string $class
	 * @param string $create
	 * @return Array(errors=>array,values=>array) or null on success
	 */
	static public function edit( $class )
	{
		return self::process( $class, false );
	}

	/**
	 * Processes create or edit request, creates or edits an ActiveRecord
	 *
	 * @param string $class
	 * @param string $create
	 * @return Array(errors=>array,values=>array) or null on success
	 */
	static public function process( $class, $create = null )
	{
		if ($create === null) {
			$create = O_Registry::get( "app/env/request/params/id" ) ? false : true;
		}
		if (!$create) {
			$record = O_Dao_ActiveRecord::getById( O_Registry::get( "app/env/request/params/id" ), $class );
			if (!$record instanceof $class)
				throw new Exception( "Record not found" );
		}
		$tableInfo = O_Dao_TableInfo::get( $class );
		$errorsArray = Array ();
		$fieldValues = Array ();
		foreach ($tableInfo->getFields() as $fieldName => $fieldInfo) {
			self::handleField( $class, $fieldName, $fieldInfo, $errorsArray, $fieldValues );
		}
		// If there were errors, reverting object, return errors array
		if (count( $errorsArray )) {
			return array ("errors" => $errorsArray, "values" => $fieldValues);
			// Values are also needed!
		}
		if ($create) {
			$record = new $class( );
		}
		// Editing or creating successfull, saving object, return nothing
		foreach ($fieldValues as $k => $v)
			$record->$k = $v;
		$record->save();
		return null;
	}

	/**
	 * Handles one field
	 *
	 * @param string $class
	 * @param string $fieldName
	 * @param O_Dao_FieldInfo $fieldInfo
	 * @param array $errors
	 * @param array $values
	 */
	static protected function handleField( $class, $fieldName, O_Dao_FieldInfo $fieldInfo, &$errors, &$values )
	{
		if (!$fieldInfo->getParam( "edit" ))
			return;
		$value = O_Registry::get( "app/env/request/params/" . $fieldName );
		try {
			// Calling field checker, if it's exists
			$callback = "$class::check_" . $fieldName;
			if (is_callable( $callback ))
				call_user_func( $callback, $value );
		}
		catch (O_Dao_Exceptions_FieldCheck $e) {
			// Adding to errors array
			$errors[ $fieldName ] = $e->getMessage();
			return;
		}
		$values[ $fieldName ] = $value;
	}

}