<?php
class O_Form_Row_BoxList extends O_Form_Row_Select {

	/**
	 * Sets options for box list
	 *
	 * @param O_Dao_Query|Array $options
	 * @param string $displayField
	 */
	public function setOptions( $options, $displayField = null )
	{
		if ($options instanceof O_Dao_Query) {
			if (!$displayField) {
				throw new O_Ex_WrongArgument( "The field to show must be specified" );
			}
			foreach ($options as $v) {
				$this->options[ $v[ "id" ] ] = $v->$displayField;
			}
		} elseif (is_array( $options )) {
			$this->options = $options;
		} else {
			throw new O_Ex_WrongArgument( "Options must be of type Array or O_Dao_Query" );
		}
	}

	/**
	 * Renders inner contents of field
	 *
	 * @param O_Html_Layout $layout
	 * @param bool $isAjax
	 */
	public function renderInner( O_Html_Layout $layout = null, $isAjax = false )
	{
		$type = $this->multiple ? "checkbox" : "radio";
		$echoed = 0;
		foreach ($this->options as $k => $v) {
			if ($echoed) {
				echo ", ";
			}
			?><label><input type="<?=$type?>" name="<?=$this->name . ($this->multiple ? "[]" : "")?>" value="<?=htmlspecialchars( $k )?>"<?=(isset( $this->value[ $k ] ) ? " checked=\"yes\"" : "")?> />&nbsp;&ndash;&nbsp;<?=trim($v)?></label><?
			$echoed = 1;
		}
	}
}