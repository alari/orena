${?PHP}
class ${PREFIX}_Tpl_Default extends O_Html_Template {
	public function displayContents()
	{
?>
<h1>It works!</h1>
${?PHP}
		$this->layout()->setTitle('Orena Framework do work. Really');
	}
}