<?php
/**
 * Table:
 * -edit:title
 * -edit:create-title
 * -edit:submit
 * -edit:create-submit
 * -edit:reset
 * -edit:create-reset
 *
 * Field:
 * -edit callback
 * -check callback
 * -check:before
 * -required error-string
 * -edit:title title
 * -title title
 *
 * @deprecated Use O_Form_Handler instead
 */
class O_Dao_Renderer_FormProcessor {

	/**
	 * Delegate object
	 *
	 * @var O_Form_Handler
	 */
	private $handler;

	private $isAjax = false;
	private $actionUrl;
	private $resetButtonValue;
	private $submitButtonValue = "Save changes";
	private $formTitle;
	private $hiddenFields = Array ();

	private $htmlBefore = Array();
	private $htmlAfter = Array();

	public function __construct()
	{
		$this->handler = new O_Form_Handler( );
	}

	/**
	 * Injects block of HTML before field
	 *
	 * @param string $fieldName
	 * @param string $code
	 */
	public function injectHtmlBefore( $fieldName, $code )
	{
		$this->htmlBefore[ $fieldName ] = $code;
	}

	/**
	 * Injects block of HTML after field
	 *
	 * @param string $fieldName
	 * @param string $code
	 */
	public function injectHtmlAfter( $fieldName, $code )
	{
		$this->htmlAfter[ $fieldName ] = $code;
	}

	/**
	 * Tries to handle the form, returns true on success
	 *
	 * @return bool
	 */
	public function handle()
	{
		return $this->handler->handle();
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
		return $this->handler->responseAjax( $refreshOrLocation, $showOnSuccess );
	}

	/**
	 * Removes field from processing
	 *
	 * @param string $fieldName
	 */
	public function exceptField( $fieldName )
	{
		$this->handler->excludeField( $fieldName );
	}

	/**
	 * Sets type suffix to process
	 *
	 * @param string $type
	 */
	public function setType( $type )
	{
		$this->handler->setType( $type );
	}

	/**
	 * Sets active record to process
	 *
	 * @param O_Dao_ActiveRecord $record
	 */
	public function setActiveRecord( O_Dao_ActiveRecord $record )
	{
		$this->handler->setClassOrRecord( $record );
	}

	/**
	 * Sets an ActiveRecord class to process
	 *
	 * @param string $class
	 */
	public function setClass( $class )
	{
		$this->handler->setClassOrRecord( $class );
	}

	/**
	 * Returns processed active record
	 *
	 * @return O_Dao_ActiveRecord
	 */
	public function getActiveRecord()
	{
		return $this->handler->getRecord();
	}

	/**
	 * Removes active record from processor context (its classname is still there)
	 *
	 */
	public function removeActiveRecord()
	{
		$this->handler->removeRecord();
	}

	/**
	 * This form should be handled not like edit-form, but like creation
	 *
	 * @param array $params Parameters to be given in constructor
	 */
	public function setCreateMode()
	{
		$params = func_get_args();
		call_user_func_array( array ($this->handler, "setCreateMode"), $params );
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
		$this->handler->setRelationQuery( $fieldName, $query, $displayField );
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
		$this->handler->setShowType( $type );
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
	 * Returns error message for given field
	 *
	 * @param string $field
	 * @return string
	 */
	public function getError( $field )
	{
		return $this->handler->getError( $field );
	}

	/**
	 * Returns array of errors for fields
	 *
	 * @return array
	 */
	public function getErrors()
	{
		return $this->handler->getErrors();
	}

	/**
	 * Displays form as HTML
	 *
	 * @param O_Html_Layout $layout
	 */
	public function show( O_Html_Layout $layout = null )
	{
		$generator = $this->handler->getForm();

		foreach ($this->hiddenFields as $name => $value) {
			$generator->addHidden( $name, $value );
		}
		if ($this->formTitle) {
			$generator->getFieldset()->setLegend( $this->formTitle );
		}
		if ($this->isAjax) {
			$generator->addHidden( "o:sbm-ajax", "+1" );
		}
		if ($this->submitButtonValue) {
			$generator->addSubmitButton( $this->submitButtonValue );
		}
		if ($this->resetButtonValue) {
			$generator->addResetButton( $this->resetButtonValue );
		}
		if(count($this->htmlAfter)) foreach($this->htmlAfter as $f=>$c) {
			$row = new O_Form_Row_Html();
			$row->setContent($c);
			$generator->addRowAfter($row, $f);
		}
		if(count($this->htmlBefore)) foreach($this->htmlBefore as $f=>$c) {
			$row = new O_Form_Row_Html();
			$row->setContent($c);
			$generator->addRowBefore($row, $f);
		}
		$generator->render( $layout, $this->isAjax );
	}

	/**
	 * Returns true if current request is form submission
	 *
	 * @return bool
	 */
	public function isFormRequest()
	{
		return $this->handler->getForm()->isFormRequest();
	}

}