<?php

class O_Form_Builder {
	const BASE_FIELDSET = "base";

	/**
	 * Array of fieldsets used in form. At least one always exists
	 *
	 * @var O_Form_Fieldset[]
	 */
	protected $fieldsets = Array ();
	/**
	 * Hidden fields
	 *
	 * @var array
	 */
	protected $hiddens = Array ();
	/**
	 * Form action url
	 *
	 * @var string
	 */
	protected $url;
	/**
	 * Sending method
	 *
	 * @var string
	 */
	protected $method = "POST";
	/**
	 * Form enctype
	 *
	 * @var string
	 */
	protected $enctype = "multipart/form-data";
	/**
	 * Form accepting charset
	 *
	 * @var string
	 */
	protected $acceptCharset = "utf-8";
	/**
	 * Row with default buttons
	 *
	 * @var O_Form_Row_Buttons
	 */
	protected $buttonsRow = null;
	/**
	 * Form instance id, id attribute
	 *
	 * @var string
	 */
	protected $instanceId;
	/**
	 * Array of field errors
	 *
	 * @var Array
	 */
	protected $errors = Array ();

	/**
	 * Constructor
	 *
	 * @param string $url Action url
	 * @param string $legend Legend of base fieldset
	 */
	public function __construct( $url, $legend = null )
	{
		$this->fieldsets[ self::BASE_FIELDSET ] = new O_Form_Fieldset( $legend );
		$this->url = $url;
	}

	/**
	 * Fieldset legend setting shortcut
	 *
	 * @param string $legend
	 * @param string $fieldset
	 */
	public function setLegend( $legend, $fieldset = self::BASE_FIELDSET )
	{
		$this->fieldsets[ $fieldset ]->setLegend( $legend );
	}

	/**
	 * Sets form id attribute
	 *
	 * @param string $id
	 */
	public function setInstanceId( $id )
	{
		$this->instanceId = $id;
	}

	/**
	 * Sets form method
	 *
	 * @param string $method
	 */
	public function setMethod( $method )
	{
		$this->method = $method;
	}

	/**
	 * Sets form action
	 *
	 * @param string $url
	 */
	public function setActionUrl( $url )
	{
		$this->url = $url;
	}

	/**
	 * Sets form enctype
	 *
	 * @param string $enctype
	 */
	public function setEnctype( $enctype )
	{
		$this->enctype = $enctype;
	}

	/**
	 * Sets form charset
	 *
	 * @param string $acceptCharset
	 */
	public function setCharset( $acceptCharset )
	{
		$this->acceptCharset = $acceptCharset;
	}

	/**
	 * Returns named fieldset
	 *
	 * @param string $fieldsetName
	 * @return O_Form_Fieldset
	 */
	public function getFieldset( $fieldsetName = self::BASE_FIELDSET )
	{
		return $this->fieldsets[ $fieldsetName ];
	}

	/**
	 * Adds row to the end of fieldset
	 *
	 * @param O_Form_Row $row
	 * @param string $fieldName
	 * @param string $fieldsetName
	 */
	public function addRow( O_Form_Row $row, $fieldName = null, $fieldsetName = null )
	{
		if (!$fieldsetName)
			$fieldsetName = self::BASE_FIELDSET;
		$this->fieldsets[ $fieldsetName ]->addRow( $row, $fieldName );
	}

	/**
	 * Adds row after another
	 *
	 * @param O_Form_Row $row
	 * @param string $afterFieldName
	 * @return bool
	 */
	public function addRowAfter( O_Form_Row $row, $afterFieldName )
	{
		foreach ($this->fieldsets as $fieldset) {
			if ($fieldset->hasField( $afterFieldName )) {
				$fieldset->addRowAfter( $row, $afterFieldName );
				return true;
			}
		}
		return false;
	}

	/**
	 * Adds row before another
	 *
	 * @param O_Form_Row $row
	 * @param string $beforeFieldName
	 * @return bool
	 */
	public function addRowBefore( O_Form_Row $row, $beforeFieldName )
	{
		foreach ($this->fieldsets as $fieldset) {
			if ($fieldset->hasField( $beforeFieldName )) {
				$fieldset->addRowBefore( $row, $beforeFieldName );
				return true;
			}
		}
		return false;
	}

	/**
	 * Adds hidden field to the form
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function addHidden( $name, $value )
	{
		$this->hiddens[ $name ] = $value;
	}

	/**
	 * Returns buttons row
	 *
	 * @return O_Form_Row_Buttons
	 */
	protected function getButtonsRow()
	{
		if ($this->buttonsRow)
			return $this->buttonsRow;
		$this->buttonsRow = new O_Form_Row_Buttons( );
		return $this->buttonsRow;
	}

	/**
	 * Adds submit button
	 *
	 * @param string $title
	 * @param string $name
	 */
	public function addSubmitButton( $title, $name = null )
	{
		$this->getButtonsRow()->addSubmit( $title, $name );
	}

	/**
	 * Adds reset button
	 *
	 * @param string $title
	 */
	public function addResetButton( $title )
	{
		$this->getButtonsRow()->addReset( $title );
	}

	/**
	 * Sets error message to a field
	 *
	 * @param string $fieldName
	 * @param string $error
	 * @return true
	 * @throws O_Ex_WrongArgument
	 */
	public function setFieldError( $fieldName, $error )
	{
		foreach ($this->fieldsets as $f)
			if ($f->hasField( $fieldName )) {
				$f->setFieldError( $fieldName, $error );
				$this->errors[$fieldName] = $error;
				return true;
			}
		throw new O_Ex_WrongArgument( "Field $fieldName not found. Cannot assign error '$error'." );
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
	 * Renders form html
	 *
	 * @param O_Html_Layout $layout
	 * @param bool $isAjax
	 */
	public function render( O_Html_Layout $layout = null, $isAjax = false )
	{
		echo "<form action=\"$this->url\" method=\"$this->method\" enctype=\"$this->enctype\" accept-charset=\"$this->acceptCharset\"" .
			 ($this->instanceId ? " id=\"{$this->instanceId}\"" : "") . ">";
			foreach ($this->fieldsets as $fieldset) {
				$fieldset->render( $layout, $isAjax );
			}
			foreach ($this->hiddens as $k => $v)
				echo "<input type=\"hidden\" name=\"" . htmlspecialchars( $k ) . "\" value=\"" . htmlspecialchars(
						$v ) . "\"/>";
			if ($this->buttonsRow instanceof O_Form_Row_Buttons) {
				$this->buttonsRow->render( $layout, $isAjax );
			}
			if ($isAjax) {
				if ($layout)
					O_Js_Middleware::getFramework()->addSrc( $layout );
				O_Js_Middleware::getFramework()->ajaxForm( $this->instanceId );
			}
			echo "</form>";
		}

		public function ajaxSucceedResponse($refreshOrLocation = null) {
			$response = Array ("status" => "SUCCEED");

			if ($refreshOrLocation === 1 || $refreshOrLocation === true) {
				$response[ "refresh" ] = 1;
			} elseif ($refreshOrLocation) {
				$response[ "redirect" ] = $refreshOrLocation;

			}
			return json_encode($response);
		}

		public function ajaxFailedResponse() {
			$response = Array("status" => "FAILED", "errors" => $this->errors);
			return json_encode($response);
		}
	}