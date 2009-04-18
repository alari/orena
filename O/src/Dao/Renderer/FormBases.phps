<?php
abstract class O_Dao_Renderer_FormBases extends O_Dao_Renderer_Commons {
	/**
	 * Form title
	 *
	 * @var string
	 */
	protected $formTitle;
	/**
	 * Value of submit button
	 *
	 * @var string
	 */
	protected $submitButtonValue;
	/**
	 * Value of reset button, or false to disable it
	 *
	 * @var string
	 */
	protected $resetButtonValue = null;
	/**
	 * Type for shower.
	 *
	 * @see O_Dao_Renderer_FormProcessor::responseAjax()
	 * @var string
	 */
	protected $showType = O_Dao_Renderer::TYPE_DEF;
	/**
	 * Array of hidden fields
	 *
	 * @var Array
	 */
	protected $hiddenFields = Array ();
	/**
	 * Url to send form to
	 *
	 * @var string
	 */
	protected $actionUrl;
	/**
	 * Form is processing via ajax or not
	 *
	 * @var bool
	 */
	protected $isAjax = false;
	/**
	 * Array of queries relations are selected from
	 *
	 * @var Array
	 */
	protected $relationQueries = Array ();
	/**
	 * We're creating the new ActiveRecord or editing the old one?
	 *
	 * @var array or 0
	 */
	protected $createMode = 0;

	/**
	 * Array of field errors
	 *
	 * @var Array
	 */
	protected $errors = Array ();
	/**
	 * Array of field values
	 *
	 * @var Array
	 */
	protected $values = Array ();

	/**
	 * Was the form handled or not
	 *
	 * @var bool
	 */
	protected $handled = false;
	/**
	 * Result of form handling
	 *
	 * @var bool
	 */
	protected $handleResult;

	/**
	 * This form should be handled not like edit-form, but like creation
	 *
	 * @param array $params Parameters to be given in constructor
	 */
	public function setCreateMode( array $params = array() )
	{
		$this->createMode = $params;
	}

	/**
	 * Tries to handle form via AJAX
	 *
	 * @param bool $isAjax
	 */
	public function setAjaxMode( $isAjax = true )
	{
		$this->isAjax = (bool)$isAjax;
	}

	/**
	 * URL for form action. Default is currents
	 *
	 * @param string $url
	 */
	public function setActionUrl( $url )
	{
		$this->actionUrl = $url;
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
		$this->relationQueries[ $fieldName ] = array ("query" => $query, "displayField" => $displayField,
														"multiply" => $multiply);
	}

	/**
	 * Sets reset button value
	 *
	 * @param bool $value Set to false to disable the button
	 */
	public function setResetButtonValue( $value )
	{
		$this->resetButtonValue = $value;
	}

	/**
	 * Sets submit button value
	 *
	 * @param string $value
	 */
	public function setSubmitButtonValue( $value )
	{
		$this->submitButtonValue = $value;
	}

	/**
	 * Sets form title
	 *
	 * @param string $title
	 */
	public function setFormTitle( $title )
	{
		$this->formTitle = $title;
	}

	/**
	 * Sets show type (used in responseAjax())
	 *
	 * @param string $type
	 */
	public function setShowType( $type )
	{
		$this->showType = $type;
	}

	/**
	 * Returns form text from model for create or edit form
	 *
	 * @param string $key
	 * @param string $default
	 * @return string
	 */
	protected function getFormText( $key, $default )
	{
		$tableInfo = O_Dao_TableInfo::get( $this->class );
		$value = $tableInfo->getParam( O_Dao_Renderer::KEY_EDIT . ":" . $key );
		if ($this->createMode !== 0 && $tableInfo->getParam( O_Dao_Renderer::KEY_EDIT . ":create-" . $key )) {
			return $tableInfo->getParam( O_Dao_Renderer::KEY_EDIT . ":create-" . $key );
		} elseif ($value)
			return $value;
		return $default;
	}

	/**
	 * Adds hidden field to form
	 *
	 * @param string $fieldName
	 * @param string $fieldValue
	 */
	public function addHiddenField( $fieldName, $fieldValue )
	{
		$this->hiddenFields[ $fieldName ] = $fieldValue;
	}

	/**
	 * Shows js after form to make it ajax-driven
	 *
	 */
	protected function showAjaxJs()
	{
		if ($this->layout)
			O_Js_Middleware::getFramework()->addSrc( $this->layout );
			//FIXME: move javascript to framework instance!
		//TODO: add classnames to registry
		?>
<script language="JavaScript" type="text/javascript">
var el = $('<?=$this->instanceId?>');
(function(){
while(!el) {
	el = $('<?=$this->instanceId?>');
}
el.getElement('input[type=submit]').addEvent("click", function(e){
	 e.stop();
 	$(this).disabled = true;

 	el.getElements('textarea[class=fckeditor]').each(function(el){
		el.value = FCKeditorAPI.GetInstance(el.id). GetXHTML( 1 );
 	 });

 	new Request.JSON({url:el.getAttribute('action'), onSuccess:function(response){
		if(response.status == 'SUCCEED') {
			if(response.refresh == 1) {
				window.location.reload(true);
			} else if(response.show) {
				el.getParent().set('html', response.show);
			} else if(response.redirect) {
				window.location.href = response.redirect;
			}
		} else {
			el.getElements('.oo-renderer-error').dispose();
			for(field in response.errors) {
				erre = el.getElement('[name='+field+']');
				if(!erre) erre = el.getElement('input[type=submit]');
				err = new Element('span', {class:'oo-renderer-error'});
				err.set('html', response.errors[field]);
				err.inject(erre, 'after');
			}
			el.getElement('input[type=submit]').disabled = false;
		}
 	 }}).post(el);
 });
}).delay(150);
 </script>
<?

	}

	/**
	 * Returns error message for given field
	 *
	 * @param string $field
	 * @return string
	 */
	public function getError( $field )
	{
		return array_key_exists( $field, $this->errors ) ? $this->errors[ $field ] : null;
	}

	/**
	 * Returns array of errors for fields
	 *
	 * @return array
	 */
	public function getErrors()
	{
		return $this->errors;
	}

	/**
	 * Removes ActiveRecord, clears form
	 *
	 */
	public function removeActiveRecord()
	{
		$this->values = Array ();
		$this->errors = Array ();
		$this->handled = false;
		parent::removeActiveRecord();
	}

	/**
	 * Sets an ActiveRecord class to process
	 *
	 * @param string $class
	 */
	public function setClass( $class )
	{
		parent::setClass( $class );
		if ($this->record)
			$this->instanceId .= "-" . $this->record->id;
	}

	/**
	 * Prepares form texts
	 *
	 */
	protected function prepareFormTexts()
	{
		// Form title
		if (!$this->formTitle) {
			$this->formTitle = $this->getFormText( "title", "Form" );
		}
		// Submit button text
		if (!$this->submitButtonValue) {
			$this->submitButtonValue = $this->getFormText( "submit", "Save changes" );
		}
		// Reset button text
		if (is_null( $this->resetButtonValue )) {
			$this->resetButtonValue = $this->getFormText( "reset", null );
		}
	}

	/**
	 * Displays form as HTML
	 *
	 * @param O_Html_Layout $layout
	 */
	public function show( O_Html_Layout $layout = null )
	{
		if ($layout)
			$this->setLayout( $layout );

		$this->prepareFormTexts();

		?>
<div>
<form method="POST" enctype="application/x-www-form-urlencoded"
	accept-charset="utf-8" action="<?=$this->actionUrl?>"
	id="<?=$this->instanceId?>">
<fieldset class="oo-renderer"><legend><?=$this->formTitle?></legend>

<?

		$this->showFormContents();

		?>

<input type="submit"
	value="<?=htmlspecialchars( $this->submitButtonValue )?>" />
<?
		if ($this->resetButtonValue) {
			?><input type="reset"
	value="<?=htmlspecialchars( $this->resetButtonValue )?>" /><?
		}
		?></fieldset>
</form>
</div>

<?
		if ($this->isAjax) {
			$this->showAjaxJs();
		}
	}

	/**
	 * Returns callback for field check
	 *
	 * @param O_Dao_FieldInfo $fieldInfo
	 * @return array
	 */
	protected function getCheckCallback( O_Dao_FieldInfo $fieldInfo )
	{
		$fullkey = $this->type ? O_Dao_Renderer::KEY_CHECK . "-" . $this->type : "";
		$key = O_Dao_Renderer::KEY_CHECK;
		$params = null;
		if ($fullkey && $fieldInfo->getParam( $fullkey )) {
			$params = $fieldInfo->getParam( $fullkey );
		} elseif ($fieldInfo->getParam( $key )) {
			$params = $fieldInfo->getParam( $key );
		}
		if (!$params)
			return false;
		return $this->getCallbackByParams( $params, O_Dao_Renderer::CALLBACK_CHECK );
	}

	/**
	 * Checks field values, collects errors (given by O_Dao_Renderer_Check_Exception)
	 *
	 */
	protected function checkValues()
	{
		foreach ($this->getFieldsToProcess( O_Dao_Renderer::KEY_EDIT ) as $name => $params) {
			$fieldInfo = O_Dao_TableInfo::get( $this->class )->getFieldInfo( $name );
			$this->values[ $name ] = O_Registry::get( "app/env/params/$name" );

			try {
				// Checker callback is called after finding relations -- by default
				if ($fieldInfo->getRelationTarget() && !$fieldInfo->getParam( "check:before" )) {
					$this->checkRelationValue( $name, $fieldInfo );
				}

				// Callback checker
				$callback = $this->getCheckCallback( $fieldInfo );
				if ($callback) {
					$params = new O_Dao_Renderer_Check_Params( $name, $this->class, $callback[ "params" ],
							$this->record );
					$params->setNewValueRef( $this->values[ $name ] );

					$callback = $callback[ "callback" ];

					call_user_func( $callback, $params );
				}

				// Required value test
				if (!$this->values[ $name ] && $fieldInfo->getParam( "required" )) {
					throw new O_Dao_Renderer_Check_Exception(
							$fieldInfo->getParam( "required" ) === 1 ? "Field value is required!" : $fieldInfo->getParam(
									"required" ) );
				}

				// Checker callback already was called -- check:before param was set
				if ($fieldInfo->getRelationTarget() && $fieldInfo->getParam( "check:before" )) {
					$this->checkRelationValue( $name, $fieldInfo );
				}
			}
			catch (O_Dao_Renderer_Check_Exception $e) {
				$this->errors[ $name ] = $e->getMessage();
			}
		}
	}

	/**
	 * Loads and checks relation values according with given queries
	 *
	 * @param string $name
	 * @param O_Dao_FieldInfo $fieldInfo
	 */
	private function checkRelationValue( $name, O_Dao_FieldInfo $fieldInfo )
	{
		// Prepare available values
		$availableValues = null;
		if (isset( $this->relationQueries[ $name ] )) {
			$availableValues = $this->relationQueries[ $name ][ "query" ]->getAll();
		}
		// multiply relation
		if (is_array( $this->values[ $name ] )) {
			// Array of values for *-to-one relation
			if (!$fieldInfo->isRelationMany()) {
				throw new O_Dao_Renderer_Check_Exception( "Wrong values for relation." );
			}
			// Prepare result value
			$value = Array ();
			foreach ($this->values[ $name ] as $id) {
				if (is_array( $availableValues ) && !isset( $availableValues[ $id ] )) {
					throw new O_Dao_Renderer_Check_Exception( "Not a valid value for relation: obj not found." );
				}
				$value[ $id ] = O_Dao_ActiveRecord::getById( $id, $fieldInfo->getRelationTarget() );
			}
			$this->values[ $name ] = $value;
			// single relation
		} else {
			if ($this->values[ $name ]) {
				if (is_array( $availableValues ) && !isset( $availableValues[ $this->values[ $name ] ] )) {
					throw new O_Dao_Renderer_Check_Exception( "Not a valid value for relation." );
				}
				$this->values[ $name ] = O_Dao_ActiveRecord::getById( $this->values[ $name ],
						$fieldInfo->getRelationTarget() );
			}
		}
	}

	/**
	 * Shows all the form fields
	 *
	 */
	protected function showFormContents()
	{
		foreach ($this->getFieldsToProcess( O_Dao_Renderer::KEY_EDIT ) as $name => $params) {
			// Find a callback for field renderer
			$callback = $this->getCallbackByParams( $params, O_Dao_Renderer::CALLBACK_EDIT );
			if (!$callback) {
				if (isset( $this->relationQueries[ $name ] )) {
					$callback = O_Dao_Renderer::CALLBACK_EDIT . "::selectRelation";
					$params = $this->relationQueries[ $name ];
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
			$fieldInfo = O_Dao_TableInfo::get( $this->class )->getFieldInfo( $name );
			$title = $fieldInfo->getParam( O_Dao_Renderer::KEY_EDIT . ":title" );
			if (!$title)
				$title = $fieldInfo->getParam( "title" );
			if (!$title)
				$title = $name;

			// Make HTML injections, display field value via callback
			if (isset( $this->htmlBefore[ $name ] ))
				echo $this->htmlBefore[ $name ];

			$edit_params = new O_Dao_Renderer_Edit_Params( $name, $this->class, $params, $this->record );
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