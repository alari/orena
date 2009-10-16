<?php

class O_Form_Row_AutoProducer {
	
	const CLASS_PREFIX = "O_Form_Row_";
	
	/**
	 * Rendered fieldname
	 *
	 * @var string
	 */
	protected $name;
	/**
	 * Callable resourse to generate a row
	 *
	 * @var callback
	 */
	protected $callback;
	/**
	 * String params given in dao' table info after callback
	 *
	 * @var string
	 */
	protected $params = "";
	/**
	 * Current field value
	 *
	 * @var mixed
	 */
	protected $value;
	/**
	 * FieldInfo object of current field
	 *
	 * @var O_Dao_FieldInfo
	 */
	protected $fieldInfo;
	/**
	 * Active record we're editing
	 *
	 * @var O_Dao_ActiveRecord
	 */
	protected $record;
	/**
	 * Relation query to choose valid target of relative field
	 *
	 * @var O_Dao_Query
	 */
	protected $relationQuery;
	/**
	 * Field name to show when choosing a relation
	 *
	 * @var string
	 */
	protected $relationDisplayField;
	/**
	 * Is it a to-many relation or just to-one
	 *
	 * @var bool
	 */
	protected $relationMultiple;
	/**
	 * Was callback already prepared from params and field info or not
	 *
	 * @var bool
	 */
	protected $callbackPrepared = false;
	/**
	 * Field title to be displayed in row
	 *
	 * @var string
	 */
	protected $title;

	/**
	 * Creates new instance of field renderer caller
	 *
	 * @param string $fieldName Processing fieldname
	 * @param string $callbackParams Callback params given by O_Dao_TableInfo::getFieldsByKey
	 * @param O_Dao_FieldInfo $fieldInfo
	 * @param O_Dao_ActiveRecord $record
	 */
	public function __construct( $fieldName, $callbackParams, O_Dao_FieldInfo $fieldInfo, O_Dao_ActiveRecord $record = null )
	{
		$this->name = $fieldName;
		if (strpos( $callbackParams, " " )) {
			list ($this->callback, $this->params) = explode( " ", $callbackParams, 2 );
		} elseif ($callbackParams) {
			$this->callback = $callbackParams;
		}
		$this->record = $record;
		$this->fieldInfo = $fieldInfo;
		$this->value = $this->record ? $this->record->$fieldName : null;
	}

	/**
	 * Set current value -- override the one from active record
	 *
	 * @param unknown_type $value
	 */
	public function setValue( $value )
	{
		$this->value = $value;
	}

	/**
	 * Sets field title to be displayed in form row
	 *
	 * @param string $title
	 */
	public function setTitle( $title )
	{
		$this->title = $title;
	}

	/**
	 * Set the relation value to select from
	 *
	 * @param O_Dao_Query $query
	 * @param string $displayField
	 */
	public function setRelationQuery( O_Dao_Query $query, $displayField )
	{
		$this->relationQuery = $query;
		$this->relationDisplayField = $displayField;
		$this->relationMultiple = $this->fieldInfo->isRelationMany();
	}

	/**
	 * Returns generated form row
	 *
	 * @return O_Form_Row
	 */
	public function getRow()
	{
		ob_start();
		$row = $this->call();
		$c = ob_get_clean();
		if (!$row instanceof O_Form_Row) {
			$row = new O_Form_Row_Html( );
			$row->setContent( $c ? $c : "<hr/><!--no row was rendered-->" );
		}
		return $row;
	}

	/**
	 * Returns currently handled field info
	 *
	 * @return O_Dao_FieldInfo
	 */
	public function getFieldInfo()
	{
		return $this->fieldInfo;
	}

	/**
	 * Returns a query to select relative objects from
	 *
	 * @return O_Dao_Query
	 */
	public function getRelationQuery()
	{
		return $this->relationQuery;
	}

	/**
	 * Returns the field to display in relation selecting list
	 *
	 * @return unknown
	 */
	public function getRelationDisplayField()
	{
		return $this->relationDisplayField;
	}

	/**
	 * Is relation multiple or not
	 *
	 * @return bool
	 */
	public function getRelationMultiple()
	{
		return $this->relationMultiple;
	}

	/**
	 * Returns current field name
	 *
	 * @return string
	 */
	public function getFieldName()
	{
		return $this->name;
	}

	/**
	 * Returns current field value
	 *
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * Returns ActiveRecord we're handling
	 *
	 * @return O_Dao_ActiveRecord
	 */
	public function getRecord()
	{
		return $this->record;
	}

	/**
	 * Returns additional params for row generator
	 *
	 * @return string
	 */
	public function getParams()
	{
		return $this->params;
	}

	/**
	 * Returns field title
	 *
	 * @return string
	 */
	public function getTitle()
	{
		if ($this->title)
			return $this->title;
		$this->title = $this->fieldInfo->getParam( O_Form_Processor::FORM_KEY . ":title" );
		if (!$this->title)
			$this->title = $this->fieldInfo->getParam( "title" );
		if (!$this->title)
			$this->title = $this->name;
		if ($this->title === 1)
			return $this->title = "";
		return $this->title;
	}

	/**
	 * Prepares callable function to be used in call()
	 *
	 * @return true
	 */
	protected function prepareCallback()
	{
		if ($this->callbackPrepared) {
			return true;
		}
		$this->callbackPrepared = true;
		
		if ($this->callback === 1) {
			$this->callback = null;
		}
		
		if (!strpos( $this->callback, "::" )) {
			$this->callback = self::CLASS_PREFIX . $this->callback;
			if (class_exists( $this->callback, true ))
				return true;
		}
		
		if (!$this->callback || !is_callable( $this->callback )) {
			$this->callback = null;
		} else {
			return true;
		}
		
		if ($this->relationQuery) {
			$this->callback = self::CLASS_PREFIX . "Select";
		} elseif ($this->fieldInfo->isFile()) {
			$this->callback = self::CLASS_PREFIX . "File";
		} else {
			$this->callback = self::CLASS_PREFIX . "String";
		}
		return true;
	}

	/**
	 * Calls prepared callback
	 *
	 * @return O_Form_Row
	 */
	protected function call()
	{
		$this->prepareCallback();
		if (!strpos( $this->callback, "::" )) {
			$class = $this->callback;
			$row = new $class( $this->name );
			$row->autoProduce( $this );
			return $row;
		}
		return call_user_func( $this->callback, $this );
	}

}