<?php

class O_Form_Fieldset {
	protected $legend;
	protected $rows = Array ();

	public function __construct( $legend = null )
	{
		$this->legend = $legend;
	}

	public function setLegend( $legend )
	{
		$this->legend = $legend;
	}

	public function addRow( O_Form_Row $row )
	{
		if ($row instanceof O_Form_Row_Field) {
			$this->rows[ $row->getName() ] = $row;
		} else {
			$this->rows[] = $row;
		}
		return $this;
	}

	public function addRowAfter( O_Form_Row $row, $afterFieldName )
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
						$tmp_rows[] = $row;
					}
				}
			}
		}
		$this->rows = $tmp_rows;
		return $this;
	}

	public function addRowBefore( O_Form_Row $row, $beforeFieldName )
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
						$tmp_rows[] = $row;
					}
				}
				$tmp_rows[ $k ] = $r;
			}
		}
		$this->rows = $tmp_rows;
		return $this;
	}

	public function setFieldError( $fieldName, $error )
	{
		if (isset( $this->rows[ $fieldName ] ) && $this->rows[ $fieldName ] instanceof O_Form_Row_Field) {
			$this->rows[ $fieldName ]->setError( $error );
			return;
		}
		throw new O_Ex_WrongArgument( "Field $fieldName not found" );
	}

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

	public function hasField( $name )
	{
		return array_key_exists( $name, $this->rows );
	}

}