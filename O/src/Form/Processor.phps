<?php

class O_Form_Processor {
	const FORM_CALLBACK = "O_Form_Callbacks";
	const FORM_KEY = "edit";
	
	private static $instancesCounter = 0;
	
	protected $instanceId;
	protected $class;
	protected $record;
	protected $values = Array ();
	protected $errors = Array ();
	protected $relationQueries = Array ();

	public function __construct( $classOrRecord )
	{
		$this->instanceId = "form-o-" . (++self::$instancesCounter);
		if (is_object( $classOrRecord )) {
			$this->record = $classOrRecord;
			$this->class = get_class( $classOrRecord );
		} else {
			$this->class = $classOrRecord;
		}
	}

	public function getBuilder( Array $values )
	{
		$builder = new O_Form_Builder( 
				O_UrlBuilder::get( O_Registry::get( "app/env/process_url" ) ) );
		$builder->setInstanceId( $this->instanceId );
		
		foreach (O_Dao_TableInfo::getFieldsByKey( $this->class, self::FORM_KEY ) as $name => $params) {
			// Find a callback for field renderer
			$callback = $this->getCallbackByParams( $params, self::FORM_CALLBACK );
			$fieldInfo = O_Dao_TableInfo::get( $this->class )->getFieldInfo( $name );
			
			if (!$callback) {
				if (isset( $this->relationQueries[ $name ] )) {
					$callback = self::FORM_CALLBACK . "::selectRelation";
					$params = $this->relationQueries[ $name ];
				} elseif ($fieldInfo->isFile()) {
					$callback = self::FORM_CALLBACK . "::file";
					$params = "";
				} else {
					$callback = self::FORM_CALLBACK . "::simple";
					$params = "";
				}
			} else {
				$params = $callback[ "params" ];
				$callback = $callback[ "callback" ];
				
				if (isset( $this->relationQueries[ $name ] )) {
					$_params = $this->relationQueries[ $name ];
					$_params[ "params" ] = $params;
					$params = $_params;
				}
			}
			
			// Prepare field value and title
			$value = isset( $this->values[ $name ] ) ? $this->values[ $name ] : ($this->record ? $this->record->$name : null);
			$title = $fieldInfo->getParam( self::FORM_KEY . ":title" );
			if (!$title)
				$title = $fieldInfo->getParam( "title" );
			if (!$title)
				$title = $name;
			
			$edit_params = new O_Dao_Renderer_Edit_Params( $name, $this->class, $params, 
					$this->record );
			if ($this->layout)
				$edit_params->setLayout( $this->layout );
			$edit_params->setValue( $value );
			$edit_params->setTitle( $title );
			if (isset( $this->errors[ $name ] ))
				$edit_params->setError( $this->errors[ $name ] );
			
			$row = call_user_func( $callback, $edit_params );
			$builder->addRow( $row );
		}
		
		return $builder;
	}

	/**
	 * Finds callback by its type and parameters stored in field info
	 *
	 * @param string $params
	 * @param const $callback_type
	 * @return array("callback","params")
	 */
	protected function getCallbackByParams( $params, $callback_type )
	{
		if ($params === 1)
			return "";
		
		$subparams = "";
		if (strpos( $params, " " )) {
			list ($callback, $subparams) = explode( " ", $params, 2 );
		} else {
			$callback = $params;
		}
		
		if (!strpos( $callback, "::" )) {
			$callback = $callback_type . "::" . $callback;
		}
		
		if (!is_callable( $callback ))
			return "";
		
		return array ("callback" => $callback, "params" => $subparams);
	}

	/**
	 * Sets a query to select field value from
	 *
	 * @param string $fieldName
	 * @param O_Dao_Query $query
	 * @param string $displayField Field name to display in selector
	 * @param bool $multiply
	 */
	public function setRelationQuery( $fieldName, O_Dao_Query $query, $displayField = "id", $multiply = false )
	{
		$this->relationQueries[ $fieldName ] = array ("query" => $query, 
														"displayField" => $displayField, 
														"multiply" => $multiply);
	}

	/**
	 * Shows all the form fields
	 *
	 */
	protected function showFormContents()
	{
		foreach ($this->getFieldsToProcess( O_Dao_Renderer::KEY_EDIT ) as $name => $params) {
			// Find a callback for field renderer
			$callback = $this->getCallbackByParams( $params, 
					O_Dao_Renderer::CALLBACK_EDIT );
			$fieldInfo = O_Dao_TableInfo::get( $this->class )->getFieldInfo( $name );
			
			if (!$callback) {
				if (isset( $this->relationQueries[ $name ] )) {
					$callback = O_Dao_Renderer::CALLBACK_EDIT . "::selectRelation";
					$params = $this->relationQueries[ $name ];
				} elseif ($fieldInfo->isFile()) {
					$callback = O_Dao_Renderer::CALLBACK_EDIT . "::file";
					$params = "";
				} else {
					$callback = O_Dao_Renderer::CALLBACK_EDIT . "::simple";
					$params = "";
				}
			} else {
				$params = $callback[ "params" ];
				$callback = $callback[ "callback" ];
				
				if (isset( $this->relationQueries[ $name ] )) {
					$_params = $this->relationQueries[ $name ];
					$_params[ "params" ] = $params;
					$params = $_params;
				}
			}
			
			// Prepare field value and title
			$value = isset( $this->values[ $name ] ) ? $this->values[ $name ] : ($this->record ? $this->record->$name : null);
			$title = $fieldInfo->getParam( O_Dao_Renderer::KEY_EDIT . ":title" );
			if (!$title)
				$title = $fieldInfo->getParam( "title" );
			if (!$title)
				$title = $name;
				
			// Make HTML injections, display field value via callback
			if (isset( $this->htmlBefore[ $name ] ))
				echo $this->htmlBefore[ $name ];
			
			$edit_params = new O_Dao_Renderer_Edit_Params( $name, $this->class, $params, 
					$this->record );
			if ($this->layout)
				$edit_params->setLayout( $this->layout );
			$edit_params->setValue( $value );
			$edit_params->setTitle( $title );
			if (isset( $this->errors[ $name ] ))
				$edit_params->setError( $this->errors[ $name ] );
			
			call_user_func( $callback, $edit_params );
			if (isset( $this->htmlAfter[ $name ] ))
				echo $this->htmlAfter[ $name ];
		}
		// Hidden fields
		foreach ($this->hiddenFields as $name => $value) {
			echo "<input type=\"hidden\" name=\"$name\" value=\"$value\"/>";
		}
		if ($this->record)
			echo "<input type=\"hidden\" name=\"id\" value=\"{$this->record->id}\"/>";
		echo "<input type=\"hidden\" name=\"o:sbm-form\" value=\"+1\"/>";
		if ($this->isAjax)
			echo "<input type=\"hidden\" name=\"o:sbm-ajax\" value=\"+1\"/>";
	}

	/**
	 * Returns true if current request is form submission
	 *
	 * @return bool
	 */
	public function isFormRequest()
	{
		if (O_Registry::get( "app/env/request_method" ) != "POST")
			return false;
		if (O_Registry::get( "app/env/params/o:sbm-form" ) != "+1")
			return false;
		if ($this->isAjax && !O_Registry::get( "app/env/params/o:sbm-ajax" ))
			return false;
		return true;
	}

}