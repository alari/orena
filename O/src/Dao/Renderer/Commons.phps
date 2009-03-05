<?php
abstract class O_Dao_Renderer_Commons {
	
	protected $record;
	protected $class;
	protected $layout;
	protected $htmlBefore = Array ();
	protected $htmlAfter = Array ();
	protected $type;
	protected $exceptFields = Array ();

	public function exceptField( $fieldName )
	{
		$this->exceptFields[] = $fieldName;
	}

	public function setType( $type )
	{
		$this->type = $type;
	}

	public function setActiveRecord( O_Dao_ActiveRecord $record )
	{
		$this->record = $record;
		$this->class = get_class( $record );
	}

	public function getActiveRecord()
	{
		return $this->record;
	}

	public function removeActiveRecord()
	{
		$this->record = null;
	}

	public function injectHtmlBefore( $fieldName, $code )
	{
		$this->htmlBefore[ $fieldName ] = $code;
	}

	public function injectHtmlAfter( $fieldName, $code )
	{
		$this->htmlAfter[ $fieldName ] = $code;
	}

	public function setLayout( O_Html_Layout $layout )
	{
		$this->layout = $layout;
	}

	protected function getFieldsToProcess( $key )
	{
		$tableInfo = O_Dao_TableInfo::get( $this->class );
		$fullkey = $this->type ? $key . "-" . $this->type : "";
		$fields = Array ();
		foreach ($tableInfo->getFields() as $fieldName => $fieldInfo) {
			if (in_array( $fieldName, $this->exceptFields )) {
				continue;
			}
			if ($fullkey && $fieldInfo->getParam( $fullkey )) {
				$fields[ $fieldName ] = $fieldInfo->getParam( $fullkey );
			} elseif ($fieldInfo->getParam( $key )) {
				$fields[ $fieldName ] = $fieldInfo->getParam( $key );
			}
		}
		return $fields;
	}

	protected function getCallbackByParams( $params, $callback_type )
	{
		if ($params === 1)
			return "";
		
		$subparams = "";
		if (strpos( $params, " " )) {
			list ($callback, $subparams) = explode( " ", $params, 2 );
		} else {
			$callback = $params;
		}
		
		if (!strpos( $callback, "::" )) {
			$callback = $callback_type . "::" . $callback;
		}
		
		if (!is_callable( $callback ))
			return "";
		
		return array ("callback" => $callback, "params" => $subparams);
	}

	protected function getEnvelopCallback( $key, $callback_type )
	{
		$tableInfo = O_Dao_TableInfo::get( $this->class );
		$params = "";
		
		if ($this->type) {
			$params = $tableInfo->getParam( $key . "-" . $this->type . ":callback" );
		}
		
		if (!$params) {
			$params = $tableInfo->getParam( $key . ":callback" );
		}
		
		return $this->getCallbackByParams( $params, $callback_type );
	}
}