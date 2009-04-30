<?php
class O_Dao_Renderer_Shower extends O_Dao_Renderer_Commons {

	/**
	 * Displays Active Record as HTML
	 *
	 */
	public function display()
	{
		// Cannot display anything
		if (!$this->record) {
			throw new O_Ex_NotFound( "Nothing to render: there's no ActiveRecord provided for renderer." );
		}

		// If full envelop callback is available, we need to do output bufferization
		$callback = $this->getEnvelopCallback( O_Dao_Renderer::KEY_SHOW, O_Dao_Renderer::CALLBACK_SHOW,
				parent::SUFF_CALLBACK );
		if ($callback) {
			$env_params = new O_Dao_Renderer_Show_Params( null, get_class( $this->record ), $callback[ "params" ],
					$this->record );
			if ($this->layout)
				$env_params->setLayout( $this->layout );
			call_user_func( $callback[ "callback" ], $env_params );
			return;
		}

		// If envelop callback is available, we need to do output bufferization
		$envelopCallback = $this->getEnvelopCallback( O_Dao_Renderer::KEY_SHOW,
				O_Dao_Renderer::CALLBACK_SHOW, parent::SUFF_ENVELOP );
		if ($envelopCallback) {
			ob_start();
		}

		// Handle every field to display it
		foreach ($this->getFieldsToProcess( O_Dao_Renderer::KEY_SHOW ) as $name => $params) {
			// Get provided callback
			$callback = $this->getCallbackByParams( $params, O_Dao_Renderer::CALLBACK_SHOW );
			// Find a default callback according with the field type
			if (!$callback) {
				$fieldInfo = O_Dao_TableInfo::get( $this->class )->getFieldInfo( $name );

				$callback = O_Dao_Renderer::CALLBACK_SHOW . "::simple";

				if ($fieldInfo->isRelationOne()) {
					$callback = O_Dao_Renderer::CALLBACK_SHOW . "::activeRecord";
				} elseif ($fieldInfo->isRelationMany() || $fieldInfo->isAlias()) {
					$callback = O_Dao_Renderer::CALLBACK_SHOW . "::loop";
				}
				$params = "";
			} else {
				$params = $callback[ "params" ];
				$callback = $callback[ "callback" ];
			}

			$call_params = new O_Dao_Renderer_Show_Params( $name, get_class( $this->record ), $params, $this->record );
			if ($this->layout)
				$call_params->setLayout( $this->layout );
			$call_params->setValue( $this->record->$name );

			// Make HTML injections, display field value via callback
			if (isset( $this->htmlBefore[ $name ] ))
				echo $this->htmlBefore[ $name ];
			call_user_func( $callback, $call_params );
			if (isset( $this->htmlAfter[ $name ] ))
				echo $this->htmlAfter[ $name ];
		}

		// Calling envelop callback
		if (is_array( $envelopCallback )) {
			$env_params = new O_Dao_Renderer_Show_Params( null, get_class( $this->record ),
					$envelopCallback[ "params" ], $this->record );
			$env_params->setValue( ob_get_clean() );
			if ($this->layout)
				$env_params->setLayout( $this->layout );
			call_user_func( $envelopCallback[ "callback" ], $env_params );
		}
	}
}