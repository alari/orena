<?php
class O_Form_Row_Select extends O_Form_Row_Field {

	protected $multiplySize = null;
	protected $options = Array ();
	protected $displayField;

	public function setOptions( $options, $displayField=null )
	{
		$this->options = $options;
		$this->displayField = $displayField;
	}

	public function setMultiply( $size = 4 )
	{
		$this->multiplySize = $size;
	}

	public function renderInner( O_Html_Layout $layout = null, $isAjax = false )
	{
		if (!count( $this->options )) {
			$this->error = "Options were not set";
			return;
		}

		echo "<select class=\"form-select\" name=\"" . $this->name . ($this->multiplySize ? "[]" : "") .
			 "\"" . ($this->multiplySize ? " multiple=\"yes\" size=\"{$this->multiplySize}\"" : "") .
			 ">";
			$this->renderOptions( $this->options );
			echo "</select>";
		}

		protected function renderOptions( $options )
		{
			foreach ($options as $k => $v) {
				if (is_array( $v )) {
					echo "<optgroup label=\"" . htmlspecialchars( $k ) . "\">";
					$this->renderOptions( $v );
					echo "</optgroup>";
				} else {
					echo "<option value=\"" . htmlspecialchars( $k ) . "\"" . ($k == $this->value ? " selected=\"yes\"" : "") .
							 ">" . (is_object($v)?$v->{$this->displayField}:$v) . "</option>";
					}
				}
			}

		}