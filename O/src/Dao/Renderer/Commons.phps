<?php
/**
 * Table:
 * -(show|loop)[-type]:envelop -- callback for envelop
 * -(show|loop)[-type]:callback -- callback for object rendering
 *
 */
abstract class O_Dao_Renderer_Commons {

	const SUFF_CALLBACK = "callback";
	const SUFF_ENVELOP = "envelop";

	/**
	 * Active record to handle
	 *
	 * @var O_Dao_ActiveRecord
	 */
	protected $record;
	/**
	 * Active record classname
	 *
	 * @var string
	 */
	protected $class;
	/**
	 * Associated layout object
	 *
	 * @var O_Html_Layout
	 */
	protected $layout;
	/**
	 * Array of html blocks to inject before fields
	 *
	 * @var array
	 */
	protected $htmlBefore = Array ();
	/**
	 * Array of html blocks to inject after fields
	 *
	 * @var array
	 */
	protected $htmlAfter = Array ();
	/**
	 * Type suffix
	 *
	 * @var string
	 */
	protected $type;
	/**
	 * Array of fields to remove from processing
	 *
	 * @var array
	 */
	protected $exceptFields = Array ();

	/**
	 * Removes field from processing
	 *
	 * @param string $fieldName
	 */
	public function exceptField( $fieldName )
	{
		$this->exceptFields[] = $fieldName;
	}

	/**
	 * Sets type suffix to process
	 *
	 * @param string $type
	 */
	public function setType( $type )
	{
		$this->type = $type;
	}

	/**
	 * Sets active record to process
	 *
	 * @param O_Dao_ActiveRecord $record
	 */
	public function setActiveRecord( O_Dao_ActiveRecord $record )
	{
		$this->record = $record;
		$this->setClass( get_class( $record ) );
	}

	/**
	 * Sets an ActiveRecord class to process
	 *
	 * @param string $class
	 */
	public function setClass( $class )
	{
		$this->class = $class;
		if (!$this->record instanceof $class) {
			$this->record = null;
		}
	}

	/**
	 * Returns processed active record
	 *
	 * @return O_Dao_ActiveRecord
	 */
	public function getActiveRecord()
	{
		return $this->record;
	}

	/**
	 * Removes active record from processor context (its classname is still there)
	 *
	 */
	public function removeActiveRecord()
	{
		$this->record = null;
	}

	/**
	 * Injects block of HTML before field
	 *
	 * @param string $fieldName
	 * @param string $code
	 */
	public function injectHtmlBefore( $fieldName, $code )
	{
		$this->htmlBefore[ $fieldName ] = $code;
	}

	/**
	 * Injects block of HTML after field
	 *
	 * @param string $fieldName
	 * @param string $code
	 */
	public function injectHtmlAfter( $fieldName, $code )
	{
		$this->htmlAfter[ $fieldName ] = $code;
	}

	/**
	 * Sets layout into processor context
	 *
	 * @param O_Html_Layout $layout
	 */
	public function setLayout( O_Html_Layout $layout )
	{
		$this->layout = $layout;
	}

	/**
	 * Returns array of fields to process by key.
	 *
	 * @param const $key
	 * @return unknown
	 */
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

	/**
	 * Finds callback by it type and parameters stored in field info
	 *
	 * @param string $params
	 * @param const $callback_type
	 * @return array("callback","params")
	 */
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

	/**
	 * Returns envelop callback by process key and callback type
	 * To get default callback name without storing it in config file:
	 * 1) Replaces _Mdl_ with _Fr_ (fragments) in model classname
	 * 2) Tries to call fragments method keyTypeSuffix
	 * 3) Gets key:suffix from config, returns it if exists
	 * 4) Elsewhere tries to call fragments method keySuffix
	 *
	 * @param const $key
	 * @param const $callback_type
	 * @return array("callback","params")
	 */
	protected function getEnvelopCallback( $key, $callback_type, $suffix = self::SUFF_CALLBACK )
	{
		$tableInfo = O_Dao_TableInfo::get( $this->class );
		$params = "";

		if ($this->type) {
			$params = $tableInfo->getParam( $key . "-" . $this->type . ":" . $suffix );
		}

		if (!$params) {
			$class = str_replace( "_Mdl_", "_Fr_", $this->class );
			$method = $key . str_replace( " ", "", ucwords( str_replace( "-", " ", $this->type . " " . $suffix ) ) );

			if (is_callable( $class . "::" . $method )) {
				return array ("callback" => $class . "::" . $method, "params" => "");
			}

			$params = $tableInfo->getParam( $key . ":" . $suffix );
			if (!$params) {
				$method = $key . ucfirst( $suffix );
				if (is_callable( $class . "::" . $method )) {
					return array ("callback" => $class . "::" . $method, "params" => "");
				}
			}
		}

		return $this->getCallbackByParams( $params, $callback_type );
	}
}
