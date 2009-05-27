<?php
/**
 * Command to simply edit the active record.
 *
 * Registry:
 * cmd/template -- template class name to show with
 * cmd/create/class -- class to create object
 * cmd/create/type -- type of form to process, see O_Dao_Renderer
 * cmd/create/show_type -- type of shower, see O_Dao_Renderer. Works via ajax
 * cmd/create/redirect -- if set to 1, refresh; to "-obj:url" -- get ->url() from object; elsewhere urlbuilder will be called
 * cmd/create/show_on_success -- message to show when form is processed successfully
 * cmd/create/params -- array of registry keys to get active records from to give them to constructor due new record creation
 *
 * Registry to prepare relation queries:
 * cmd/create/relations/$field_name:
 * 		multiply -- allow multiply selection or not
 * 		display -- field name to display, default is id
 * 		source -- where to get query from
 * 		class -- if source==class, so there should be stored classname to get query from
 * 		field -- if source==field or source==user field, there should be described a field relative to a resourse
 * 		resourse -- if source==field, there should be stored registry key to the resourse to get relative field from
 *
 * Notice: you can use "layout_class" registry key to easily change formatting of default templates
 *
 * @author Dmitry Kourinski
 */
class O_Cmd_Create extends O_Cmd_Edit {

	public function process()
	{
		$tpl = O_Registry::get( "app/cmd/template" ) ? $this->getTemplate( 
				O_Registry::get( "app/cmd/template" ), true ) : $this->getTemplate();
		
		$form = new O_Dao_Renderer_FormProcessor( );
		$form->setClass( O_Registry::get( "app/cmd/create/class" ) );
		
		$createParams = O_Registry::get( "app/cmd/create/params" );
		if (!is_array( $createParams ))
			$createParams = array ();
		else {
			$_createParams = $createParams;
			$createParams = array ();
			foreach ($_createParams as $reg) {
				$createParams[] = O_Registry::get( $reg );
			}
		}
		
		call_user_method_array( "setCreateMode", $form, $createParams );
		
		if (O_Registry::get( "app/cmd/create/type" )) {
			$form->setType( O_Registry::get( "app/cmd/create/type" ) );
		}
		if (O_Registry::get( "app/cmd/create/show_type" )) {
			$form->setShowType( O_Registry::get( "app/cmd/create/show_type" ) );
		}
		
		// Prepare relations
		$relations = O_Registry::get( "app/cmd/create/relations" );
		if (is_array( $relations ))
			$this->setRelationQueries( $form, $relations );
			
		// Prepare form fields by inherited commands
		$this->prepareForm( $form );
		
		// Prepare redirect url
		$redirect = O_Registry::get( "app/cmd/create/redirect" );
		if ($redirect != "-obj:url" && $redirect !== 1 && $redirect)
			$redirect = O_UrlBuilder::get( $redirect );
			
		// Ajax response
		if (O_Registry::get( "app/cmd/create/ajax" )) {
			$form->setAjaxMode();
			if ($form->handle()) {
				if ($redirect == "-obj:url")
					$redirect = $form->getActiveRecord()->url();
				$form->responseAjax( $redirect, 
						O_Registry::get( "app/cmd/create/show_on_success" ) );
				return null;
			}
			// Plain request handling
		} else {
			// No errors
			if ($form->handle()) {
				if ($redirect == "-obj:url")
					$redirect = $form->getActiveRecord()->url();
				if ($redirect)
					return $this->redirect( $redirect );
				$tpl->success = O_Registry::get( "app/cmd/create/show_on_success" );
			}
		}
		$tpl->form = $form;
		
		return $tpl;
	}
}