<?php

class O_Form_Check_AutoProducer extends O_Form_FieldActionProducer {
	const CLASS_PREFIX = "O_Form_Check_";
	/**
	 * Type suffix of checking
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * Creates auto producer instance
	 *
	 * @param string $name
	 * @param O_Dao_ActiveRecord $record
	 * @param O_Dao_FieldInfo $fieldInfo
	 * @param string $type
	 * @param reference $value New value to be checked
	 */
	public function __construct( $name, $record, $fieldInfo, $type, &$value )
	{
		$this->name = $name;
		$this->record = $record;
		$this->fieldInfo = $fieldInfo;
		$this->type = $type;
		$this->value = &$value;
	}

	/**
	 * Sets the query to get related values from
	 *
	 * @param O_Dao_Query $query
	 */
	public function setRelationQuery( O_Dao_Query $query )
	{
		$this->relationQuery = $query;
	}

	/**
	 * Provides the check
	 *
	 * @param bool $createMode
	 * @throws O_Form_Check_Error
	 */
	public function check( $createMode = false )
	{

		// Checker callback is called after finding relations -- by default
		if ($this->fieldInfo->isRelation() && !$this->fieldInfo->getParam(
				"check:before" )) {
			$this->checkRelationValue();
		}

		if ($this->prepareCallback()) {
			if (!strpos( $this->callback, "::" )) {
				$class = $this->callback;
				$checker = new $class( $this );
				$checker->check();
			} else {
				call_user_func( $this->callback, $this );
			}
		}

		// Required value test
		$required = $this->fieldInfo->getParam( "required-" . $this->type );
		if (!$required)
			$required = $this->fieldInfo->getParam( "required" );
		if ($required && ((!$this->value && !$this->fieldInfo->isFile()) || ($this->fieldInfo->isFile() &&
			 $createMode && (!isset( $_FILES[ $this->name ] ) || !$_FILES[ $this->name ][ "size" ])))) {
				throw new O_Form_Check_Error(
						$required === 1 ? "Field value is required!" : $required );
		}

		// Checker callback already was called -- check:before param was set
		if ($this->fieldInfo->isRelation() && $this->fieldInfo->getParam( "check:before" )) {
			$this->checkRelationValue();
		}
	}

	/**
	 * Loads and checks relation values according with given queries
	 *
	 * @todo Cache ids only, don't load full objects from relation query
	 */
	private function checkRelationValue()
	{
		// Prepare available values
		$availableValues = null;
		if ($this->relationQuery) {
			$availableValues = $this->relationQuery->getAll();
		}
		// multiply relation
		if (is_array( $this->value )) {
			// Array of values for *-to-one relation
			if (!$this->fieldInfo->isRelationMany()) {
				throw new O_Form_Check_Error( "Wrong values for relation." );
			}
			// Prepare result value
			$value = Array ();
			foreach ($this->value as $id) {
				if (is_array( $availableValues ) && !isset( $availableValues[ $id ] )) {
					throw new O_Form_Check_Error(
							"Not a valid value for relation: obj not found." );
				}
				$value[ $id ] = O_Dao_ActiveRecord::getById( $id,
						$this->fieldInfo->getRelationTarget() );
			}
			$this->value = $value;
			// single relation
		} else {
			if ($this->value) {
				if (is_array( $availableValues ) && !isset( $availableValues[ $this->value ] )) {
					throw new O_Form_Check_Error( "Not a valid value for relation." );
				}
				$this->value = O_Dao_ActiveRecord::getById( $this->value,
						$this->fieldInfo->getRelationTarget() );
			}
		}
	}

	/**
	 * Prepares callable function to be used in check()
	 *
	 * @return bool
	 */
	protected function prepareCallback()
	{
		if ($this->callbackPrepared !== null) {
			return $this->callbackPrepared;
		}
		$this->callbackPrepared = true;

		$this->callback = $this->fieldInfo->getParam( "check-" . $this->type );
		if (!$this->callback)
			$this->callback = $this->fieldInfo->getParam( "check" );

		if (!$this->callback) {
			return $this->callbackPrepared = false;
		}

		if ($this->callback === 1) {
			$this->callback = null;
		}

		if (strpos( $this->callback, " " )) {
			list ($this->callback, $this->params) = explode( " ", $this->callback, 2 );
		}

		if ($this->callback && !strpos( $this->callback, "::" )) {
			// FIXME temporary hack
			$replace_callback = array ("htmlPurifier" => "HtmlPurifier",
														"timestamp" => "DateTime");
			if (isset( $replace_callback[ $this->callback ] ))
				$this->callback = $replace_callback[ $this->callback ];
				// /FIXME
			$this->callback = self::CLASS_PREFIX . $this->callback;
			if (class_exists( $this->callback, true ))
				return $this->callbackPrepared = true;
		}

		if (!$this->callback || !is_callable( $this->callback )) {
			$this->callback = null;
			return $this->callbackPrepared = false;
		} else {
			return $this->callbackPrepared = true;
		}
	}

	/**
	 * Sets the new formed value
	 *
	 * @param mixed $value
	 */
	public function setValue( $value )
	{
		$this->value = $value;
	}
}