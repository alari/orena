<?php

class O_Form_Builder {
	const BASE_FIELDSET = "base";

	protected $fieldsets = Array ();
	protected $hiddens = Array ();
	protected $url;
	protected $method = "POST";
	protected $enctype = "multipart/form-data";
	protected $acceptCharset = "utf-8";
	protected $buttonsRow = null;
	protected $instanceId;

	public function __construct( $url, $legend = null )
	{
		$this->fieldsets[ self::BASE_FIELDSET ] = new O_Form_Fieldset( $legend );
		$this->url = $url;
	}

	public function setInstanceId( $id )
	{
		$this->instanceId = $id;
	}

	public function setMethod( $method )
	{
		$this->method = $method;
	}

	public function setActionUrl($url) {
		$this->url = $url;
	}


	public function setEnctype( $enctype )
	{
		$this->enctype = $enctype;
	}

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

	public function addRow( O_Form_Row $row, $fieldsetName = self::BASE_FIELDSET )
	{print_r($this->fieldsets);
		$this->fieldsets[ $fieldsetName ]->addRow( $row );
	}

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

	public function addHidden( $name, $value )
	{
		$this->hiddens[ $name ] = $value;
	}

	protected function getButtonsRow()
	{
		if ($this->buttonsRow)
			return $this->buttonsRow;
		$this->buttonsRow = new O_Form_Row_Buttons( );
		return $this->buttonsRow;
	}

	public function addSubmitButton( $title, $name = null )
	{
		$this->getButtonsRow()->addSubmit( $title, $name );
	}

	public function addResetButton( $title )
	{
		$this->getButtonsRow()->addReset( $title );
	}

	public function setFieldError( $fieldName, $error )
	{
		foreach ($this->fieldsets as $f)
			if ($f->hasField( $fieldName )) {
				$f->setFieldError( $fieldName, $error );
				return true;
			}
		throw new O_Ex_WrongArgument( "Field $fieldName not found. Cannot assign error '$error'." );
	}

	public function render( O_Html_Layout $layout = null, $isAjax = false )
	{
		echo "<form action=\"$this->url\" method=\"$this->method\" enctype=\"$this->enctype\" accept-charset=\"$this->acceptCharset\"" .
			 ($this->instanceId ? " id=\"{$this->instanceId}\"" : "") . ">";
			foreach ($this->fieldsets as $fieldset) {
				$fieldset->render( $layout, $this->isAjax );
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

	}