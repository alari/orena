<?php

class Test_Fragments_CustomEditor {

	static public function render( $record, $class, $fieldName, $title, $subparams, $errorMessage, $isAjax, $layout )
	{
		echo "test renderer / <input type='hidden' name='$fieldName' value='{$record->$fieldName}'/>";
	}

}