<?php
/**
 * -edit callback
 * -check callback
 * -required error-string
 *
 */
class O_Dao_Renderer_FormProcessor extends O_Dao_Renderer_Commons {
	
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
	 * Url to redirect user on success
	 *
	 * @var string
	 */
	protected $successUrl;
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
	 * @var unknown_type
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
	 * Simple constructor
	 *
	 */
	public function __construct()
	{
		$this->actionUrl = O_UrlBuilder::get( O_Registry::get( "app/env/process_url" ) );
	}

	/**
	 * Sets an ActiveRecord class to process
	 *
	 * @param unknown_type $class
	 */
	public function setClass( $class )
	{
		$this->class = $class;
		if (!$this->record instanceof $class) {
			$this->record = null;
		}
	}

	/**
	 * This form should be handled not like edit-form, but like creation
	 *
	 */
	public function setCreateMode()
	{
		$this->createMode = 1;
	}

	/**
	 * Tries to handle form via AJAX
	 *
	 * @param bool $isAjax
	 * @todo implement ajax functionality
	 */
	public function setAjax( $isAjax = true )
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
	 * URL to perform redirection on success
	 *
	 * @param string $url
	 * @todo is it usable?
	 */
	public function setSuccessUrl( $url )
	{
		$this->successUrl = $url;
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
	 * Displays form as HTML
	 *
	 * @param O_Html_Layout $layout
	 */
	public function show( O_Html_Layout $layout = null )
	{
		if ($layout)
			$this->setLayout( $layout );
		
		?><form method="POST" enctype="application/x-www-form-urlencoded"
	action="<?=$this->actionUrl?>">
<fieldset class="oo-renderer"><label>Test editing</label>

<?
		foreach ($this->getFieldsToProcess( O_Dao_Renderer::KEY_EDIT ) as $name => $params) {
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
			
			$value = isset( $this->values[ $name ] ) ? $this->values[ $name ] : ($this->record ? $this->record->$name : null);
			// Make HTML injections, display field value via callback
			// TODO: add field title to display
			if (isset( $this->htmlBefore[ $name ] ))
				echo $this->htmlBefore[ $name ];
			call_user_func_array( $callback, 
					array ($name, $value, $name . "_title", $params, $this->layout, 
								isset( $this->errors[ $name ] ) ? $this->errors[ $name ] : null) );
			if (isset( $this->htmlAfter[ $name ] ))
				echo $this->htmlAfter[ $name ];
		}
		foreach ($this->hiddenFields as $name => $value) {
			echo "<input type=\"hidden\" name=\"$name\" value=\"$value\"/>";
		}
		if ($this->record)
			echo "<input type=\"hidden\" name=\"id\" value=\"{$this->record->id}\"/>";
		
		?>

<input type="submit" /></fieldset>
</form>
<?
	}

	/**
	 * Checks field values, collects errors (given by O_Dao_Renderer_FieldCheckException)
	 *
	 */
	protected function checkValues()
	{
		foreach ($this->getFieldsToProcess( O_Dao_Renderer::KEY_EDIT ) as $name => $params) {
			$fieldInfo = O_Dao_TableInfo::get( $this->class )->getFieldInfo( $name );
			$this->values[ $name ] = O_Registry::get( "app/env/params/$name" );
			
			try {
				// It's not an atomic field but a relation
				if ($fieldInfo->getRelationTarget()) {
					// Prepare available values
					$availableValues = null;
					if (isset( $this->relationQueries[ $name ] )) {
						$availableValues = $this->relationQueries[ $name ][ "query" ]->getAll();
					}
					// multiply relation
					if (is_array( $this->values[ $name ] )) {
						// Array of values for *-to-one relation
						if (!$fieldInfo->isRelationMany()) {
							throw new O_Dao_Renderer_FieldCheckException( "Wrong values for relation." );
						}
						// Prepare result value
						$value = Array ();
						foreach ($this->values[ $name ] as $id) {
							if (is_array( $availableValues ) && !isset( $availableValues[ $id ] )) {
								throw new O_Dao_Renderer_FieldCheckException( "Not a valid value for relation." );
							}
							$value[ $id ] = O_Dao_ActiveRecord::getById( $id, $fieldInfo->getRelationTarget() );
						}
						$this->values[ $name ] = $value;
						// single relation
					} else {
						if (is_array( $availableValues ) && !isset( $availableValues[ $this->values[ $name ] ] )) {
							throw new O_Dao_Renderer_FieldCheckException( "Not a valid value for relation." );
						}
						$this->values[ $name ] = O_Dao_ActiveRecord::getById( $this->values[ $name ], 
								$fieldInfo->getRelationTarget() );
					}
				}
				
				// Callback checker
				$callback = $this->getCallbackByParams( $params, O_Dao_Renderer::CALLBACK_CHECK );
				if ($callback) {
					$params = $callback[ "params" ];
					$callback = $callback[ "callback" ];
					
					call_user_func_array( $callback, 
							array ($this->values[ $name ], $this->record ? $this->record->$name : null, $params) );
				}
				
				// Required value test
				if (!$this->values[ $name ] && $fieldInfo->getParam( "required" )) {
					throw new O_Dao_Renderer_FieldCheckException( 
							$fieldInfo->getParam( "required" ) === 1 ? "Field value is required!" : $fieldInfo->getParam( 
									"required" ) );
				}
			}
			catch (O_Dao_Renderer_FieldCheckException $e) {
				$this->errors[ $name ] = $e->getMessage();
			}
		}
	}

	/**
	 * Tries to handle the form, returns true on success
	 *
	 * @return bool
	 */
	public function handle()
	{
		// Process only POST requests
		if (O_Registry::get( "app/env/request_method" ) != "POST")
			return false;
			
		// Load record, if needed
		if (!$this->record && !$this->createMode) {
			$this->record = O_Dao_ActiveRecord::getById( O_Registry::get( "app/env/params/id" ), $this->class );
			if (!$this->record) {
				$this->errors[ "_" ] = "Record not found.";
				return false;
			}
		}
		
		// Check and prepare values, found errors if they are
		$this->checkValues();
		
		// Stop processing without saving, if errors occured
		if (count( $this->errors )) {
			return false; // TODO produce error response
		}
		
		// Create record in database
		if ($this->createMode && !$this->record) {
			$class = $this->class;
			$this->record = new $class( );
		}
		
		// Setting values for ActiveRecord
		foreach ($this->values as $name => $value) {
			$fieldInfo = O_Dao_TableInfo::get( $this->class )->getFieldInfo( $name );
			// Simple assigning
			if ($fieldInfo->isAtomic() || $fieldInfo->isRelationOne()) {
				$this->record->$name = $value;
				// Removing old values, assigning new ones
			} elseif ($fieldInfo->isRelationMany()) {
				if (is_array( $value )) {
					$field = $this->record->$name;
					foreach ($field as $id => $obj) {
						if (!isset( $value[ $id ] ))
							$field->remove( $obj, $fieldInfo->isRelationOwns() );
					}
					foreach ($value as $id => $obj) {
						if (!isset( $field[ $id ] ))
							$field[] = $obj;
					}
				}
			}
		}
		
		// Succeed
		return $this->record->save();
	}

	/**
	 * Removes ActiveRecord, clears form
	 *
	 */
	public function removeActiveRecord()
	{
		$this->values = Array ();
		$this->errors = Array ();
		parent::removeActiveRecord();
	}

}