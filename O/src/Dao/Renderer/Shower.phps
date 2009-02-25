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
			throw new Exception( "Nothing to render: there's no ActiveRecord provided for renderer." );
		}
		
		// If envelop callback is available, we need to do output bufferization
		$envelopCallback = $this->getEnvelopCallback( O_Dao_Renderer::KEY_SHOW, 
				O_Dao_Renderer::CALLBACK_SHOW );
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
				if ($fieldInfo->isAtomic()) {
					$callback = O_Dao_Renderer::CALLBACK_SHOW . "::simple";
				} elseif ($fieldInfo->isRelationOne()) {
					$callback = O_Dao_Renderer::CALLBACK_SHOW . "::activeRecord";
				} elseif ($fieldInfo->isRelationMany() || $fieldInfo->isAlias()) {
					$callback = O_Dao_Renderer::CALLBACK_SHOW . "::loop";
				}
				$params = "";
			} else {
				$params = $callback[ "params" ];
				$callback = $callback[ "callback" ];
			}
			
			// Make HTML injections, display field value via callback
			if (isset( $this->htmlBefore[ $name ] ))
				echo $this->htmlBefore[ $name ];
			call_user_func_array( $callback, array ($this->record->$name, $params) );
			if (isset( $this->htmlAfter[ $name ] ))
				echo $this->htmlAfter[ $name ];
		}
		
		// Calling envelop callback
		if (is_array( $envelopCallback )) {
			call_user_func_array( $envelopCallback[ "callback" ], 
					array (ob_get_clean(), $envelopCallback[ "params" ], $this->record) );
		}
	}
}