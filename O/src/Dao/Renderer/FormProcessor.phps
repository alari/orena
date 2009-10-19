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
class O_Dao_Renderer_FormProcessor extends O_Form_Handler {

	private $htmlBefore = Array();
	private $htmlAfter = Array();

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
	 * Removes field from processing
	 *
	 * @param string $fieldName
	 */
	public function exceptField( $fieldName )
	{
		$this->excludeField( $fieldName );
	}


	/**
	 * Sets active record to process
	 *
	 * @param O_Dao_ActiveRecord $record
	 */
	public function setActiveRecord( O_Dao_ActiveRecord $record )
	{
		$this->setClassOrRecord( $record );
	}

	/**
	 * Sets an ActiveRecord class to process
	 *
	 * @param string $class
	 */
	public function setClass( $class )
	{
		$this->setClassOrRecord( $class );
	}

	/**
	 * Returns processed active record
	 *
	 * @return O_Dao_ActiveRecord
	 */
	public function getActiveRecord()
	{
		return $this->getRecord();
	}

	/**
	 * Removes active record from processor context (its classname is still there)
	 *
	 */
	public function removeActiveRecord()
	{
		$this->removeRecord();
	}

	/**
	 * Tries to handle form via AJAX
	 *
	 * @param bool $isAjax
	 */
	public function setAjaxMode( $isAjax = true )
	{
		$this->setAjax($isAjax);
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
		parent::setRelationQuery( $fieldName, $query, $displayField );
	}

	/**
	 * Sets reset button value
	 *
	 * @param bool $value Set to false to disable the button
	 */
	public function setResetButtonValue( $value )
	{
		$this->addResetButton($value);
	}

	/**
	 * Sets submit button value
	 *
	 * @param string $value
	 */
	public function setSubmitButtonValue( $value )
	{
		$this->addSubmitButton($value);
	}

	/**
	 * Sets form title
	 *
	 * @param string $title
	 */
	public function setFormTitle( $title )
	{
		$this->getFieldset()->setLegend( $title );
	}

	/**
	 * Adds hidden field to form
	 *
	 * @param string $fieldName
	 * @param string $fieldValue
	 */
	public function addHiddenField( $fieldName, $fieldValue )
	{
		$this->addHidden($fieldName, $fieldValue);
	}

	/**
	 * Displays form as HTML
	 *
	 * @param O_Html_Layout $layout
	 */
	public function show( O_Html_Layout $layout = null )
	{
		if ($this->isAjax) {
			$this->addHidden( "o:sbm-ajax", "+1" );
		}
		if(count($this->htmlAfter)) foreach($this->htmlAfter as $f=>$c) {
			$row = new O_Form_Row_Html();
			$row->setContent($c);
			$this->addRowAfter($row, $f);
		}
		if(count($this->htmlBefore)) foreach($this->htmlBefore as $f=>$c) {
			$row = new O_Form_Row_Html();
			$row->setContent($c);
			$this->addRowBefore($row, $f);
		}
		$this->render( $layout, $this->isAjax );
	}
}