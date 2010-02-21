<?php
/**
 * Command to simply edit the active record.
 *
 * Registry:
 * cmd/template -- template class name to show with
 * cmd/edit/source -- registry key to find the object in
 * cmd/edit/type -- type of form to process, see O_Dao_Renderer
 * cmd/edit/show_type -- type of shower, see O_Dao_Renderer. Works via ajax
 * cmd/edit/redirect -- if set to 1, refresh; to "-obj:url" -- get ->url() from object; elsewhere urlbuilder will be called
 * cmd/edit/show_on_success -- message to show when form is processed successfully
 *
 * Registry to prepare relation queries:
 * cmd/edit/relations/$field_name:
 * 		multiply -- allow multiply selection or not
 * 		display -- field name to display, default is id
 * 		source -- where to get query from
 * 		class -- if source==class, so there should be stored classname to get query from
 * 		field -- if source==field or source==user field, there should be described a field relative to a resource
 * 		resource -- if source==field, there should be stored registry key to the resource to get relative field from
 *
 * Notice: you can use "layout_class" registry key to easily change formatting of default templates
 *
 * @author Dmitry Kurinskiy
 */
class O_Cmd_Edit extends O_Command {

	public function process()
	{
		$tpl = O_Registry::get( "app/cmd/template" ) ? $this->getTemplate(
				O_Registry::get( "app/cmd/template" ), true ) : $this->getTemplate();
		$tpl->obj = O_Registry::get( O_Registry::get( "app/cmd/edit/source" ) );
		if (!$tpl->obj instanceof O_Dao_ActiveRecord) {
			throw new O_Ex_NotFound( "Object not found.", 404 );
		}
		/* @var $form O_Dao_Renderer_FormProcessor */
		$form = $tpl->obj->form();

		if (O_Registry::get( "app/cmd/edit/type" )) {
			$form->setType( O_Registry::get( "app/cmd/edit/type" ) );
		}
		if (O_Registry::get( "app/cmd/edit/show_type" )) {
			$form->setShowType( O_Registry::get( "app/cmd/edit/show_type" ) );
		}

		// Prepare relations
		$relations = O_Registry::get( "app/cmd/edit/relations" );
		if (is_array( $relations ))
			$this->setRelationQueries( $form, $relations );

		// Prepare form fields by inherited commands
		$this->prepareForm( $form );

		// Prepare redirect url
		$redirect = O_Registry::get( "app/cmd/edit/redirect" );
		if ($redirect == "-obj:url")
			$redirect = $tpl->obj->url();
		elseif ($redirect !== 1 && $redirect)
			$redirect = O_UrlBuilder::get( $redirect );

		// Ajax response
		if (O_Registry::get( "app/cmd/edit/ajax" )) {
			$form->setAjaxMode();
			if ($form->responseAjax( $redirect, O_Registry::get( "app/cmd/edit/show_on_success" ) )) {
				return null;
			}
			// Plain request handling
		} else {
			// No errors
			if ($form->handle()) {
				if ($redirect)
					return $this->redirect( $redirect );
				$tpl->success = O_Registry::get( "app/cmd/edit/show_on_success" );
			}
		}
		$tpl->form = $form;

		return $tpl;
	}

	/**
	 * To be overriden; called before form handling
	 *
	 * @param O_Dao_Renderer_FormProcessor $form
	 */
	protected function prepareForm( O_Dao_Renderer_FormProcessor $form )
	{
		;
	}

	/**
	 * Sets relation queries from registry array
	 *
	 * @param O_Dao_Renderer_FormProcessor $form
	 * @param array $registry
	 */
	protected function setRelationQueries( O_Dao_Renderer_FormProcessor $form, Array $registry )
	{
		foreach ($registry as $fieldName => $params) {
			$multiply = isset( $params[ "multiply" ] ) ? $params[ "multiply" ] : false;
			$display = isset( $params[ "display" ] ) ? $params[ "display" ] : "id";
			$source = isset( $params[ "source" ] ) ? $params[ "source" ] : null;
			switch ($source) {
				// User related field
				case "user field" :
					$resource = O_Acl_Session::getUser();
				// Resource related field
				case "field" :
					if (!$resource && isset( $params[ "resource" ] )) {
						$resource = O_Registry::get( $params[ "resource" ] );
					}
					if (!$resource instanceof O_Dao_ActiveRecord) {
						throw new O_Ex_NotFound( "Resource not found.", 404 );
					}
					if (isset( $params[ "field" ] )) {
						$field = $params[ "field" ];
						$query = $resource->$field;
					}
				break;
				default :
					if (isset( $params[ "class" ] ))
						$query = O_Dao_Query::get( $params[ "class" ] );
			}
			if (!$query instanceof O_Dao_Query) {
				throw new O_Ex_NotFound( "Wrong query provided.", 404 );
			}

			$form->setRelationQuery( $fieldName, $query, $display, $multiply );
		}
	}
}