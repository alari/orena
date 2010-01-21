${?PHP}
class ${PREFIX}_Cmd_Default extends O_Command {

	public function process()
	{
		return $this->getTemplate();
	}
}