<?php

class O_Form_Fieldset {
	/**
	 * Contents of <legend> tag
	 *
	 * @var string
	 */
	protected $legend;
	/**
	 * Array of rows inside fieldset
	 *
	 * @var O_Form_Row[]
	 */
	protected $rows = Array ();

	/**
	 * Creates a new fieldset
	 *
	 * @param string $legend
	 */
	public function __construct( $legend = null )
	{
		$this->legend = $legend;
	}

	/**
	 * Sets <legend> contents of fieldset
	 *
	 * @param string $legend
	 */
	public function setLegend( $legend )
	{
		$this->legend = $legend;
	}

	/**
	 * Adds a row in the end of rows list
	 *
	 * @param O_Form_Row $row
	 * @param string $fieldName to be used in addRowAfter() and addRowBefore() if a row is not an O_Form_Row_Field
	 */
	public function addRow( O_Form_Row $row, $fieldName=null )
	{
		if ($row instanceof O_Form_Row_Field) {
			$this->rows[ $row->getName() ] = $row;
		} else {
			$this->rows[$fieldName] = $row;
		}
	}

	/**
	 * Adds row after specified field
	 *
	 * @param O_Form_Row $row
	 * @param string $afterFieldName
	 * @param string $fieldName to be used in addRowAfter() and addRowBefore() if a row is not an O_Form_Row_Field
	 */
	public function addRowAfter( O_Form_Row $row, $afterFieldName, $fieldName=null )
	{
		$tmp_rows = Array ();
		foreach ($this->rows as $k => $r) {
			if (is_int( $k )) {
				$tmp_rows[] = $r;
			} else {
				$tmp_rows[ $k ] = $r;
				if ($k == $afterFieldName) {
					if ($row instanceof O_Form_Row_Field) {
						$tmp_rows[ $row->getName() ] = $row;
					} else {
						$tmp_rows[$fieldName] = $row;
					}
				}
			}
		}
		$this->rows = $tmp_rows;
	}

	/**
	 * Adds row before specified field
	 *
	 * @param O_Form_Row $row
	 * @param string $afterFieldName
	 * @param string $fieldName to be used in addRowAfter() and addRowBefore() if a row is not an O_Form_Row_Field
	 */
	public function addRowBefore( O_Form_Row $row, $beforeFieldName, $fieldName=null )
	{
		$tmp_rows = Array ();
		foreach ($this->rows as $k => $r) {
			if (is_int( $k )) {
				$tmp_rows[] = $r;
			} else {
				if ($k == $beforeFieldName) {
					if ($row instanceof O_Form_Row_Field) {
						$tmp_rows[ $row->getName() ] = $row;
					} else {
						$tmp_rows[$fieldName] = $row;
					}
				}
				$tmp_rows[ $k ] = $r;
			}
		}
		$this->rows = $tmp_rows;
	}

	/**
	 * Sets error msg to field
	 *
	 * @param string $fieldName
	 * @param string $error
	 * @throws O_Ex_WrongArgument
	 */
	public function setFieldError( $fieldName, $error )
	{
		if (isset( $this->rows[ $fieldName ] ) && $this->rows[ $fieldName ] instanceof O_Form_Row_Field) {
			$this->rows[ $fieldName ]->setError( $error );
			return;
		}
		throw new O_Ex_WrongArgument( "Field $fieldName not found" );
	}

	/**
	 * Renders fieldset contents
	 *
	 * @param O_Html_Layout $layout
	 * @param bool $isAjax
	 */
	public function render( O_Html_Layout $layout = null, $isAjax = false )
	{
		if ($this->legend) {
			echo "<fieldset><legend>" . $this->legend . "</legend>";
		}
		foreach ($this->rows as $row) {
			$row->render( $layout, $isAjax );
		}
		if ($this->legend) {
			echo "</fieldset>";
		}
	}

	/**
	 * Returns true if a field is in fieldset, false elsewhere
	 *
	 * @param string $name
	 * @return bool
	 */
	public function hasField( $name )
	{
		return array_key_exists( $name, $this->rows );
	}

}