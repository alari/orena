<?php
class O_Form_Handler {

	protected $class;
	protected $record;

	protected $type;
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
	 * Array of field errors
	 *
	 * @var Array
	 */
	protected $errors = Array ();
	protected $relationQueries = Array ();
	protected $excludeFields = Array ();
	/**
	 * We're creating the new ActiveRecord or editing the old one?
	 *
	 * @var array or 0
	 */
	protected $createMode = 0;
	/**
	 * Related form generator object
	 *
	 * @var O_Form_Generator
	 */
	protected $form;

	protected $showType;

	/**
	 * Creates a new object
	 *
	 * @param O_Dao_ActiveRecord|string $classOrRecord
	 */
	public function __construct( $classOrRecord=null )
	{
		if ($classOrRecord) {
			$this->setClassOrRecord( $classOrRecord );
		}
		$this->form = new O_Form_Generator( $classOrRecord );
		$this->type = O_Dao_Renderer::TYPE_DEF;
	}

	/**
	 * Sets a class or a record to be handled
	 *
	 * @param O_Dao_ActiveRecord|string $classOrRecord
	 */
	public function setClassOrRecord( $classOrRecord )
	{
		if ($classOrRecord instanceof O_Dao_ActiveRecord) {
			$this->record = $classOrRecord;
			$this->class = get_class( $classOrRecord );
		} else {
			$this->class = (string)$classOrRecord;
			$this->record = null;
		}
		$this->showType = O_Dao_Renderer::TYPE_DEF;
		$this->form->setClassOrRecord($classOrRecord);
	}

	/**
	 * Returns prepared form generator
	 *
	 * @return O_Form_Generator
	 */
	public function getForm($update=false)
	{
		if ($update && $this->form->isGenerated()) {
			$this->form->clear();
		}
		foreach ($this->relationQueries as $fieldName => $params) {
			$this->form->setRelationQuery( $fieldName, $params[ "query" ],
					$params[ "displayField" ] );
		}
		$this->form->generate( $this->type, $this->values, $this->errors, $this->excludeFields );
		return $this->form;
	}

	/**
	 * Sets a query to select field value from
	 *
	 * @param string $fieldName
	 * @param O_Dao_Query $query
	 * @param string $displayField Field name to display in selector
	 */
	public function setRelationQuery( $fieldName, O_Dao_Query $query, $displayField = "id" )
	{
		$this->relationQueries[ $fieldName ] = array ("query" => $query,
														"displayField" => $displayField);
	}

	/**
	 * Remove a field from form handler and builder
	 *
	 * @param unknown_type $fieldName
	 */
	public function excludeField( $fieldName )
	{
		$this->excludeFields[] = $fieldName;
	}

	/**
	 * Set editing type -- key suffix edit-$type
	 *
	 * @param string $type
	 */
	public function setType( $type )
	{
		$this->type = $type;
	}

	/**
	 * This form should be handled not like edit-form, but like creation
	 *
	 * @param array $params Parameters to be given in constructor
	 */
	public function setCreateMode()
	{
		$params = func_get_args();
		$this->createMode = $params ? $params : Array ();
	}

	public function setShowType($type) {
		$this->showType = $type;
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

	public function getRecord() {
		return $this->record;
	}


	/**
	 * Removes ActiveRecord, clears form
	 *
	 */
	public function removeRecord()
	{
		$this->values = Array ();
		$this->errors = Array ();
		$this->handled = false;
		$this->record = null;
		$this->form->clear();
	}

	/**
	 * Tries to handle the form, returns true if form was handled
	 *
	 * @return bool
	 */
	public function handle()
	{
		// handle only once
		if ($this->handled)
			return $this->handleResult;
		$this->handled = true;

		// Try to handle valid requests only
		if (!$this->getForm()->isFormRequest()) {
			return $this->handleResult = false;
		}

		// Load record, if needed
		if (!$this->record && $this->createMode === 0) {
			$this->record = O_Dao_ActiveRecord::getById( O_Registry::get( "app/env/params/id" ),
					$this->class );
			if (!$this->record) {
				$this->errors[ "_" ] = "Record not found.";
				return $this->handleResult = false;
			}
		}

		// Start transaction
		O_Db_Manager::getConnection()->beginTransaction();

		try {
			// Check and prepare values, find errors if they are
			$this->handleValues();

			// Stop processing without saving, if errors occured
			if (count( $this->errors )) {
				return $this->handleResult = false;
			}

			// Create record in database
			if ($this->createMode !== 0 && !$this->record) {
				$class = $this->class;
				if (count( $this->createMode )) {
					$refl = new ReflectionClass( $class );
					$this->record = $refl->newInstanceArgs( $this->createMode );
				} else {
					$this->record = new $class( );
				}
			}

			// Setting values for ActiveRecord
			foreach ($this->values as $name => $value) {
				$this->record->$name = $value;
			}

			// Trying to save
			try {
				$this->record->save();
			}
			catch (PDOException $e) {
				$this->errors[ "_" ] = "Duplicate entries found. Saving failed.";
				throw $e;
			}

		}
		catch (Exception $e) {
			O_Db_Manager::getConnection()->rollBack();
			if (!isset( $this->errors[ "_" ] ))
				$this->errors[ "_" ] = $e->getMessage();
			return $this->handleResult = 0;
		}

		O_Db_Manager::getConnection()->commit();
		// Succeed
		return $this->handleResult = 1;
	}

	/**
	 * Checks field values, collects errors (given by O_Form_Check_Error)
	 *
	 */
	protected function handleValues()
	{
		$tableInfo = O_Dao_TableInfo::get( $this->class );
		foreach (array_keys(
				$tableInfo->getFieldsByKey( O_Form_Generator::FORM_KEY, $this->type,
						$this->excludeFields ) ) as $name) {
			$fieldInfo = $tableInfo->getFieldInfo( $name );
			$this->values[ $name ] = O_Registry::get( "app/env/params/$name" );
			try {
				$provider = new O_Form_Check_AutoProvider( $name, $this->record, $fieldInfo,
						$this->type, $this->values[ $name ] );
				if (isset( $this->relationQueries[ $name ] )) {
					$provider->setRelationQuery( $this->relationQueries[ $name ][ "query" ] );
				}
				$provider->check( $this->createMode !== 0 );
			}
			catch (O_Form_Check_Error $e) {
				$this->errors[ $name ] = $e->getMessage();
			}
		}
	}

	/**
	 * Print response to be handled as an ajax response
	 *
	 * @param mixed $refreshOrLocation if 1 or true, refresh, else -- it's an url
	 * @param string $showOnSuccess if not set and there's no value for refreshing, this will be shown in response; otherwise object will be shown
	 * @return bool true if response was echoed, false if form wasn't handled
	 */
	public function responseAjax( $refreshOrLocation = null, $showOnSuccess = null )
	{
		if (!$this->form->isFormRequest())
			return false;
		$response = Array ("status" => "");
		if ($this->handle()) {
			$response[ "status" ] = "SUCCEED";

			if ($refreshOrLocation === 1 || $refreshOrLocation === true) {
				$response[ "refresh" ] = 1;
			} elseif ($refreshOrLocation) {
				$response[ "redirect" ] = $refreshOrLocation;

			} elseif (!$showOnSuccess) {
				ob_start();
				$this->record->show( $this->layout, $this->showType );
				$response[ "show" ] = ob_get_clean();
			} else {
				$response[ "show" ] = $showOnSuccess;
			}
		} else {
			$response[ "status" ] = "FAILED";
			$response[ "errors" ] = $this->errors;
		}
		echo json_encode( $response );
		return true;
	}

}