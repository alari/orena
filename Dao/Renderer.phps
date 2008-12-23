<?php
class Dao_Renderer {

	/**
	 * Echoes an object in HTML
	 *
	 * @param Dao_Object $obj
	 */
	static public function show( Dao_Object $obj )
	{
		$obj;
	}

	/**
	 * Echoes an object edit form
	 *
	 * @param Dao_Object $obj
	 * @param string $action
	 * @param bool $isAjax
	 * @param array $errorsArray
	 */
	static public function edit( Dao_Object $obj, $action, $isAjax = false, Array $errorsArray = Array() )
	{
		$echoEditForm = false;
		foreach (Dao_TableInfo::get( get_class( $obj ) )->getFields() as $fieldInfo) {
			if ($fieldInfo->getParam( "edit" ) || $fieldInfo->getParam( "render" )) {
				$echoEditForm = true;
				break;
			}
		}
		if (!$echoEditForm)
			throw new Exception( "Cannot render edit form for object of class class " . get_class( $obj ) . "." );
		
		?>

<form method="POST" enctype="application/x-www-form-urlencoded"
	action="<?=$action?>">

<?
		foreach (array_keys( Dao_TableInfo::get( get_class( $obj ) )->getFields() ) as $fieldName) {
			self::editField( $obj, $fieldName, 
					isset( $errorsArray[ $fieldName ] ) ? $errorsArray[ $fieldName ] : null, $isAjax );
		}
		?>
<input type="hidden" name="id" value="<?=$obj->id?>" /> <input
	type="submit" /></form>

<?
	}

	/**
	 * Renders one field of edit form
	 *
	 * @param Dao_Object $obj
	 * @param string $fieldName
	 * @param string $errorMessage
	 * @param bool $isAjax
	 */
	static private function editField( Dao_Object $obj, $fieldName, $errorMessage, $isAjax )
	{
		$fieldInfo = Dao_TableInfo::get( get_class( $obj ) )->getFieldInfo( $fieldName );
		$param = $fieldInfo->getParam( "edit" );
		if (!$param)
			$param = $fieldInfo->getParam( "render" );
		if (!$param)
			return;
		
		$title = $fieldInfo->getParam( "title" );
		if (!$title)
			$title = ucfirst( str_replace( "_", " ", $fieldName ) );
		
		if ($param === 1) {
			// Auto-generate renderer by field type
			if (!$fieldInfo->isAtomic())
				throw new Exception( "Cannot autogenerate renderer for non-atomic field!" );
				// Finally it should produce $callback and $subparams
			// TODO: add logic to autogenerate callback for atomic fields
			return;
		} else {
			
			$subparams = "";
			if (strpos( $param, " " )) {
				list ($callback, $subparams) = explode( " ", $param, 2 );
			} else {
				$callback = $param;
			}
		
		}
		
		if (!strpos( $callback, "::" )) {
			$callback = __CLASS__ . "::editor" . ucfirst( $callback );
		}
		
		call_user_func_array( $callback, array ($obj, $fieldName, $title, $subparams, $errorMessage, $isAjax) );
	}

	static public function editorWysiwyg( Dao_Object $obj, $fieldName, $title, $subparams, $errorMessage, $isAjax )
	{
		?>

<?=$title?>:
<br />
<textarea cols="100" rows="40"><?=$obj->$fieldName?></textarea><?=$subparams . $errorMessage?>
<?}}