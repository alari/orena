<?php
class O_Form_Row_EditableLine extends O_Form_Row_Field {

	/**
	 * Renders all the row contents, starting with box and including renderInner
	 *
	 * @param O_Html_Layout $layout
	 * @param bool $isAjax
	 */
	public function render( O_Html_Layout $layout = null, $isAjax = false )
	{
		if ($this->isVertical !== null) {
			$this->cssClass .= " form-row-" . ($this->isVertical ? "v" : "h");
		}
		echo "<div class=\"{$this->cssClass}\">";

		$this->renderInner($layout, $isAjax);

		if ($this->error) {
			echo "<div class=\"form-row-error\">$this->error</div>";
		}
		if ($this->remark) {
			echo "<div class=\"form-row-remark\">$this->remark</div>";
		}
		echo "</div>";
	}

	public function renderInner( O_Html_Layout $layout = null, $isAjax = false ){
		$id = $this->name.mt_rand(0,100);

		?>
<div id="<?=$id?>"><?=htmlspecialchars($this->value)?></div>
<script type="text/javascript">
Om.use("Om.EditableLine", function(){
	new Om.EditableLine("<?=$id?>", {
		field: "<?=$this->name?>",
		nullValue: "<?=$this->title?>"
	});
});
</script>
		<?
	}
}