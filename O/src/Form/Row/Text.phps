<?php
class O_Form_Row_Text extends O_Form_Row_Field {

	public function renderInner( O_Html_Layout $layout = null, $isAjax = false )
	{
		?><textarea class="form-text" name="<?=$this->name?>"><?=$this->value?></textarea>
</div><?
	}
}