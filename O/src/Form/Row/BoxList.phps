<?php
class O_Form_Row_BoxList extends O_Form_Row_Field {
	
	protected $variants = Array ();
	protected $multiply = false;

	public function setVariants( $variants, $showField = null )
	{
		if ($variants instanceof O_Dao_Query) {
			if (!$showField) {
				throw new O_Ex_WrongArgument( "The field to show must be specified" );
			}
			foreach ($variants as $v) {
				$this->variants[ $v[ "id" ] ] = $v->$showField;
			}
		} elseif (is_array( $variants )) {
			$this->variants = $variants;
		} else {
			throw new O_Ex_WrongArgument( "Variants must be of type Array or O_Dao_Query" );
		}
	}

	public function setMultiply( $multiply = true )
	{
		$this->multiply = $multiply;
	}

	public function renderInner( O_Html_Layout $layout = null, $isAjax = false )
	{
		$type = $this->multiply ? "checkbox" : "radio";
		$echoed = 0;
		foreach ($this->variants as $k => $v) {
			if ($echoed)
				echo ", ";
			?><label> <input type="<?=$type?>"
	name="<?=$this->name . ($this->multiply ? "[]" : "")?>"
	value="<?=htmlspecialchars( $k )?>"
	<?=(isset( $this->value[ $k ] ) ? " checked=\"yes\"" : "")?> />
			 &ndash; <?=$v?>
			</label><?
			$echoed = 1;
		}
	}
}