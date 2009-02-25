<?php
class O_Dao_Renderer_FormProcessor extends O_Dao_Renderer_Commons {
	
	protected $hiddenFields = Array ();
	protected $actionUrl;
	protected $successUrl;
	protected $isAjax = false;
	protected $relationQueries = Array ();
	protected $createMode = 0;
	
	protected $errors = Array ();
	protected $values = Array ();

	public function __construct()
	{
		$this->actionUrl = O_Registry::get( "app/env/process_url" );
	}

	public function setClass( $class )
	{
		$this->class = $class;
		if (!$this->record instanceof $class) {
			$this->record = null;
		}
	}

	public function setCreateMode()
	{
		$this->createMode = 1;
	}

	public function setAjax( $isAjax = true )
	{
		$this->isAjax = (bool)$isAjax;
	}

	public function setActionUrl( $url )
	{
		$this->actionUrl = $url;
	}

	public function setSuccessUrl( $url )
	{
		$this->successUrl = $url;
	}

	public function setRelationQuery( $fieldName, O_Dao_Query $query, $displayField = "id", $multiply = false )
	{
		$this->relationQueries[ $fieldName ] = array ("query" => $query, "displayField" => $displayField, 
														"multiply" => $multiply);
	}

	public function addHiddenField( $fieldName, $fieldValue )
	{
		$this->hiddenFields[ $fieldName ] = $fieldValue;
	}

	public function display()
	{
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
			}
			catch (O_Dao_Renderer_FieldCheckException $e) {
				$this->errors[ $name ] = $e->getMessage();
			}
		}
	}

	public function handle()
	{
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

	public function process()
	{
		;
	}
}