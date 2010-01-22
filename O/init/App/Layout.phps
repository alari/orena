${?PHP}
class ${PREFIX}_Layout extends O_Html_Layout {

	/**
	 * Displays template contents (and whole layout)
	 */
	protected function displayBody() {
?>
<hr/>
${?PHP}$this->tpl->displayContents();?>
<hr/>
${?PHP}
	}
}