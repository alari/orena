<?php
class O_Form_Row_Select extends O_Form_Row_Field {
	
	protected $multiple = null;
	protected $options = Array ();
	protected $displayField;

	public function setOptions( $options, $displayField = null )
	{
		$this->options = $options;
		$this->displayField = $displayField;
	}

	public function setMultiple( $size = 4 )
	{
		$this->multiple = $size;
	}

	public function renderInner( O_Html_Layout $layout = null, $isAjax = false )
	{
		if (!count( $this->options )) {
			$this->error = "Options were not set";
			return;
		}
		
		echo "<select class=\"form-select\" name=\"" . $this->name . ($this->multiple ? "[]" : "") .
			 "\"" . ($this->multiple ? " multiple=\"yes\" size=\"{$this->multiple}\"" : "") . ">";
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
							 ">" . (is_object( $v ) ? $v->{$this->displayField} : $v) . "</option>";
					}
				}
			}

			public function autoProduce( O_Form_Row_AutoProducer $producer )
			{
				parent::autoProduce( $producer );
				if ($producer->getRelationQuery() instanceof O_Dao_Query) {
					$this->setOptions( $producer->getRelationQuery(), 
							$producer->getRelationDisplayField() );
					if ($producer->getRelationMultiple()) {
						$this->setMultiple( 
								is_numeric( $producer->getParams() ) ? $producer->getParams() : 4 );
					}
				} elseif (is_array( $producer->getFieldInfo()->getParam( "enum", 1 ) )) {
					$this->setOptions( $producer->getFieldInfo()->getParam( "enum", 1 ) );
				} else {
					throw new O_Ex_WrongArgument( 
							"Given producer is not well for list-choosing field" );
				}
			}
		
		}