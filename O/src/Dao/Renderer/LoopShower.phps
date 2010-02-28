<?php
class O_Dao_Renderer_LoopShower extends O_Dao_Renderer_Commons {
	/**
	 * Query to display
	 *
	 * @var O_Dao_Query
	 */
	private $query;

	public function __construct( O_Dao_Query $q )
	{
		$this->query = $q;
		$this->class = $this->query->getClass();
	}

	/**
	 * Displays query as HTML
	 *
	 */
	public function display()
	{
		// If full callback is available, we need to call it
		$callback = $this->getEnvelopCallback( O_Dao_Renderer::KEY_LOOP,
				O_Dao_Renderer::CALLBACK_SHOW, parent::SUFF_CALLBACK );
		if ($callback) {
			$env_params = new O_Dao_Renderer_Show_Params( null, $this->class,
					$callback[ "params" ] );
			$env_params->setValue( $this->query );
			if ($this->layout)
				$env_params->setLayout( $this->layout );
			call_user_func( $callback[ "callback" ], $env_params );
			return;
		}
		// If envelop callback is available, we need to do output bufferization
		$envelopCallback = $this->getEnvelopCallback( O_Dao_Renderer::KEY_LOOP,
				O_Dao_Renderer::CALLBACK_SHOW, parent::SUFF_ENVELOP );
		if ($envelopCallback) {
			ob_start();
		}

		foreach ($this->query as $obj)
			O_Dao_Renderer::show( $obj, $this->layout, $this->type );

		// Calling envelop callback
		if (is_array( $envelopCallback )) {
			$env_params = new O_Dao_Renderer_Show_Params( null, $this->class,
					$envelopCallback[ "params" ] );
			$env_params->setValue( ob_get_clean() );
			if ($this->layout)
				$env_params->setLayout( $this->layout );
			call_user_func( $envelopCallback[ "callback" ], $env_params );
		}
	}
}