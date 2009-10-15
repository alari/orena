<?php
class O_Form_Row_DateTime extends O_Form_Row_Field {

	public function renderInner( O_Html_Layout $layout = null, $isAjax = false )
	{
		$date = Array ("d" => "", "m" => "", "Y" => "", "H" => "", "i" => "");
		if (is_numeric( $this->value )) {
			$date_a = explode( " ", date( "d m Y H i", $this->value ), 5 );
			$date[ "d" ] = array_shift( $date_a );
			$date[ "m" ] = array_shift( $date_a );
			$date[ "Y" ] = array_shift( $date_a );
			$date[ "H" ] = array_shift( $date_a );
			$date[ "i" ] = array_shift( $date_a );
		} elseif (is_array( $this->value )) {
			$date = $this->value;
		}
		
		?>
<input class="form-row-time" type="text" name="<?=$this->name?>[d]"
	maxlength="2" size="2" value="<?=$date[ "d" ]?>" />
.
<input class="form-row-time" type="text" name="<?=$this->name?>[m]"
	maxlength="2" size="3" value="<?=$date[ "m" ]?>" />
.
<input class="form-row-time" type="text" name="<?=$this->name?>[Y]"
	maxlength="4" size="4" value="<?=$date[ "Y" ]?>" />
&nbsp;
<input class="form-row-time" type="text" name="<?=$this->name?>[H]"
	maxlength="2" size="2" value="<?=$date[ "H" ]?>" />
:
<input class="form-row-time" type="text" name="<?=$this->name?>[i]"
	maxlength="2" size="2" value="<?=$date[ "i" ]?>" />
<?
	
	}
}