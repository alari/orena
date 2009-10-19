<?php

class O_Form_Generator extends O_Form_Builder {
	const FORM_KEY = "edit";

	/**
	 * Required to generate unique Instance ID
	 *
	 * @var int
	 */
	private static $instancesCounter = 0;

	/**
	 * Handling DAO classname
	 *
	 * @var string
	 */
	protected $class;
	/**
	 * The record to handle
	 *
	 * @var O_Dao_ActiveRecord
	 */
	protected $record;
	/**
	 * Array of relation queries to select some field values from
	 *
	 * @var Array
	 */
	protected $relationQueries = Array ();
	/**
	 * Checks if a form was generated or not
	 *
	 * @var bool
	 */
	protected $isGenerated;
	/**
	 * Type suffix of edit process
	 *
	 * @var string
	 */
	protected $type;
	/**
	 * Array of fields to exclude from the form
	 *
	 * @var unknown_type
	 */
	protected $excludeFields = Array ();

	/**
	 * Creates a new instance
	 *
	 * @param O_Dao_ActiveRecord|string $classOrRecord
	 */
	public function __construct( $classOrRecord = null )
	{
		$this->instanceId = "form-o-" . (++self::$instancesCounter);
		if ($classOrRecord)
			$this->setClassOrRecord( $classOrRecord );
		parent::__construct( O_UrlBuilder::get( O_Registry::get( "app/env/process_url" ) ) );
		$this->type = O_Dao_Renderer::TYPE_DEF;
	}

	/**
	 * Remove a field from form handler and builder
	 *
	 * @param string $fieldName
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
	 * Sets current active record class
	 *
	 * @param string $class
	 */
	public function setClass( $class )
	{
		$this->class = $class;
		$this->record = null;
	}

	/**
	 * Sets active record we're generating a form with
	 *
	 * @param O_Dao_ActiveRecord $record
	 */
	public function setRecord( O_Dao_ActiveRecord $record )
	{
		$this->record = $record;
		$this->class = get_class( $record );
	}

	/**
	 * Sets classname or record object to be handled
	 *
	 * @param O_Dao_ActiveRecord|string $classOrRecord
	 */
	public function setClassOrRecord( $classOrRecord )
	{
		if ($classOrRecord instanceof O_Dao_ActiveRecord) {
			$this->record = $classOrRecord;
			$this->class = get_class( $classOrRecord );
		} else {
			$this->class = $classOrRecord;
			$this->record = null;
		}
	}

	/**
	 * Returns current active record, if available
	 *
	 * @return O_Dao_ActiveRecord
	 */
	public function getRecord()
	{
		return $this->record;
	}

	/**
	 * Generates form builder contents
	 *
	 * @param array $values
	 * @param array $errors
	 * @param array $excludeFields
	 */
	public function generate( Array $values = Array(), Array $errors = Array() )
	{
		if ($this->isGenerated)
			return;
		$tableInfo = O_Dao_TableInfo::get( $this->class );

		// Prepare field rows
		foreach ($tableInfo->getFieldsByKey( self::FORM_KEY, $this->type,
				$this->excludeFields ) as $name => $params) {
			$fieldInfo = $tableInfo->getFieldInfo( $name );
			$producer = new O_Form_Row_AutoProducer( $name, $params, $fieldInfo, $this->record );
			if (isset( $this->relationQueries[ $name ] )) {
				$producer->setRelationQuery( $this->relationQueries[ $name ][ "query" ],
						$this->relationQueries[ $name ][ "displayField" ] );
			}
			if (isset( $values[ $name ] )) {
				$producer->setValue( $values[ $name ] );
			}
			$row = $producer->getRow();
			if (isset( $errors[ $name ] )) {
				$row->setError( $errors[ $name ] );
			}
			$this->addRow( $row, $name );
		}
		$this->addHidden( "o:sbm-form", "+1" );
		if ($this->record) {
			$this->addHidden( "id", $this->record->id );
		}

		// Set forms texts
		$submitTitle = $this->getFormText( "submit", $tableInfo );
		if ($submitTitle) {
			$this->addSubmitButton( $submitTitle );
		}

		$resetTitle = $this->getFormText( "reset", $tableInfo );
		if ($resetTitle) {
			$this->addResetButton( $resetTitle );
		}

		$legend = $this->getFormText( "title", $tableInfo );
		if ($legend) {
			$this->getFieldset()->setLegend( $legend );
		}
		$this->isGenerated = true;
	}

	/**
	 * Returns if a form was generated or not
	 *
	 * @return bool
	 */
	public function isGenerated()
	{
		return $this->isGenerated;
	}

	/**
	 * Returns the form to non-generated state
	 *
	 */
	public function clear()
	{
		$this->fieldsets = Array (parent::BASE_FIELDSET => new O_Form_Fieldset( ));
		$this->buttonsRow = null;
		$this->isGenerated = false;
	}

	/**
	 * Returns form text like submit button value, form title etc
	 *
	 * @param string $name
	 * @param O_Dao_TableInfo $tableInfo
	 * @return string
	 */
	private function getFormText( $name, O_Dao_TableInfo $tableInfo )
	{
		$paramFull = self::FORM_KEY . ":" . ($this->record ? "" : "create-") . $name;
		$param = self::FORM_KEY . ":" . $name;

		$value = $tableInfo->getParam( $paramFull );
		if (!$value && $paramFull != $param) {
			$value = $tableInfo->getParam( $param );
		}
		return $value;
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
		return true;
	}
}