<?php
/**
 * Command to simply show the active record.
 *
 * Registry:
 * cmd/template -- template class name to show with
 * cmd/show/source -- registry key to find the object in
 * cmd/show/type -- type of showing, see O_Dao_Renderer
 *
 * Notice: you can use "layout_class" registry key to easily change formatting of default templates
 *
 * @author Dmitry Kourinski
 */
class O_Cmd_Show extends O_Command {

	public function process()
	{
		$tpl = O_Registry::get( "app/cmd/template" ) ? $this->getTemplate( O_Registry::get( "app/cmd/template" ), true ) : $this->getTemplate();
		$tpl->obj = O_Registry::get( O_Registry::get( "app/cmd/show/source" ) );
		if (!$tpl->obj instanceof O_Dao_ActiveRecord) {
			throw new O_Ex_NotFound( "Object not found.", 404 );
		}
		if (O_Registry::get( "app/cmd/show/type" ))
			$tpl->type = O_Registry::get( "app/cmd/show/type" );
		return $tpl;
	}
}