<?php
/**
 * Displays create or edit form for O_Dao_ActiveRecord class or instance.
 *
 * To use this, specify "-edit[ callback]" flag in configuration of each field you want to edit.
 * Callback must be like "[classname::]methodname[ params]". If classname is specified,
 * classname::methodname($record, $class, $fieldName, $title, $subparams, $errorMessage, $isAjax, $layout)
 * will be called, otherwise built-in renderers are used.
 * Field titles are from "-title $title" param.
 *
 * @see O_Dao_ActiveRecord::edit()
 * @see O_Dao_ActiveRecord::show()
 *
 * @author Dmitry Kourinski
 *
 * @todo Add type=hidden params to create form
 */
class O_Html_DaoRenderer_Form {
	/**
	 * Layout object to add styles to
	 *
	 * @var O_Html_Layout
	 */
	private $layout;
	/**
	 * ActiveRecord object to edit
	 *
	 * @var O_Dao_ActiveRecord
	 */
	private $record;
	/**
	 * Classname of O_Dao_ActiveRecord to work with
	 *
	 * @var string
	 */
	private $class;
	/**
	 * Form action to post to
	 *
	 * @var string
	 */
	private $action;
	/**
	 * To post a simple or ajax form
	 *
	 * @var bool
	 */
	private $isAjax = false;
	/**
	 * Array of error messages
	 *
	 * @var array
	 */
	private $errorsArray = Array ();
	/**
	 * Form title to display as fieldset legend
	 *
	 * @var string
	 */
	private $formTitle = "";
	/**
	 * Value of submit button
	 *
	 * @var string
	 */
	private $submitText = "Submit";
	/**
	 * Value of reset button
	 *
	 * @var string
	 */
	private $resetText = "";

	/**
	 * Constructor
	 *
	 * @param string $action
	 * @param O_Html_Layout $layout
	 */
	public function __construct( $action, O_Html_Layout $layout = null )
	{
		$this->action = $action;
		$this->layout = $layout;
	}

	/**
	 * Sets an active record object to edit
	 *
	 * @param O_Dao_ActiveRecord $record
	 */
	public function setActiveRecord( O_Dao_ActiveRecord $record )
	{
		$this->record = $record;
		$this->setActiveRecordClass( get_class( $record ) );
	}

	/**
	 * Sets active record class to display create form
	 *
	 * @param string $class
	 */
	public function setActiveRecordClass( $class )
	{
		$this->class = $class;
		if (!$this->record instanceof $class)
			$this->record = null;
		// TODO: add default things getting, like form name and submit button title
	}

	/**
	 * Sets ajax or simple post mode
	 *
	 * @param bool $isAjax
	 */
	public function setAjaxMode( $isAjax = true )
	{
		$this->isAjax = (bool)$isAjax;
	}

	/**
	 * Sets errors to display near fields
	 *
	 * @param array $errors
	 */
	public function setErrorsArray( Array $errors )
	{
		$this->errorsArray = $errors;
	}

	/**
	 * Sets title for fieldset
	 *
	 * @param unknown_type $title
	 */
	public function setTitle( $title )
	{
		$this->formTitle = $title;
	}

	/**
	 * Sets value for SUBMIT button
	 *
	 * @param unknown_type $text
	 */
	public function setSubmitText( $text )
	{
		$this->submitText = $text;
	}

	/**
	 * Sets value for RESET button
	 *
	 * @param string $text
	 */
	public function setResetText( $text )
	{
		$this->resetText = $text;
	}

	/**
	 * Echoes rendered form
	 *
	 */
	public function display()
	{
		if (!$this->class)
			throw new Exception( "Cannot render nothing." );
		$ref = new ReflectionClass( $this->class );
		if (!$ref->isSubclassOf( "O_Dao_ActiveRecord" ))
			throw new Exception( "Cannot render classes that are not subclasses of O_Dao_ActiveRecord." );

		$tableInfo = O_Dao_TableInfo::get( $this->class );

		// TODO: add ajax support after choosing JS framework
		?>
<form method="POST" enctype="application/x-www-form-urlencoded"
	action="<?=$this->action?>">
<fieldset class="oo-renderer">
		<?
		if ($this->formTitle) {
			?><legend><?=$this->formTitle?></legend><?
		}

		foreach (array_keys( $tableInfo->getFields() ) as $fieldName) {
			$this->displayField( $fieldName, $this->record ? $this->record->$fieldName : null );
		}

		if ($this->record) {
			?>

<input type="hidden" name="id" value="<?=$this->record->id?>" />
<?
		}
		?>

<input type="submit" value="<?=$this->submitText?>"
	class="oo-renderer-submit" />
<?
		if ($this->resetText) {
			?>
<input type="reset" value="<?=$this->resetText?>"
	class="oo-renderer-reset" />
<?
		}
		?>
</fieldset>
</form>
<?
	}

	/**
	 * Displays one field -- by given callback or default editor method
	 *
	 * @param string $fieldName
	 * @param mixed $fieldValue
	 */
	private function displayField( $fieldName, $fieldValue )
	{
		$fieldInfo = O_Dao_TableInfo::get( $this->class )->getFieldInfo( $fieldName );
		$param = $fieldInfo->getParam( "edit" );

		if (!$param)
			return;

		$title = $fieldInfo->getParam( "title" );
		if (!$title)
			$title = ucfirst( str_replace( "_", " ", $fieldName ) );

		if ($param === 1) {
			// Auto-generate renderer by field type
			if (!$fieldInfo->isAtomic())
				throw new Exception( "Cannot autogenerate renderer for non-atomic field!" );
				// Finally it should produce $callback and $subparams
			// TODO: add logic to autogenerate callback for atomic fields
			return;
		} else {

			$subparams = "";
			if (strpos( $param, " " )) {
				list ($callback, $subparams) = explode( " ", $param, 2 );
			} else {
				$callback = $param;
			}
		}

		$errorMessage = isset( $this->errorsArray[ $fieldName ] ) ? $this->errorsArray[ $fieldName ] : null;

		if (!strpos( $callback, "::" )) {
			$callback = "editor" . ucfirst( $callback );
			if (!method_exists( $this, $callback ))
				throw new Exception( "Not a default renderer: $callback." );
			$this->$callback( $fieldName, $fieldValue, $title, $subparams, $errorMessage );
			return;
		}

		if (!is_callable( $callback ))
			throw new Exception( "Worng field renderer callback: $callback." );

		call_user_func_array( $callback,
				array ($this->record, $this->class, $fieldName, $title, $subparams, $errorMessage, $this->isAjax,
						$this->layout) );
	}

	/**
	 * Boolean checkbox
	 *
	 * @param string $fieldName
	 * @param string $fieldValue
	 * @param string $title
	 * @param string $subparams If it's numeric, true is "1", else true is the first param, false -- the second
	 * @param string $errorMessage
	 */
	private function editorBool( $fieldName, $fieldValue, $title, $subparams, $errorMessage )
	{
		if ($subparams) {
			if (strpos( $subparams, " " ))
				list ($true, $false) = explode( " ", $subparams, 2 );
			else
				$true = $subparams;
		} else {
			$true = 1;
			$false = 0;
		}
		?>
<div class="oo-renderer-field">
<div class="oo-renderer-title">
<?=$title?>:</div>
<?
		if ($errorMessage) {
			?>
<div class="oo-renderer-error">
<?=$errorMessage?>
</div>
<?
		}
		?>

<?
		if (isset( $false )) {
			?>
<input type="hidden" name="<?=$fieldName?>" value="<?=$false?>" />
<?
		}
		?>
<input type="checkbox" name="<?=$fieldName?>" value="<?=$true?>"
	<?=($fieldValue == $true ? " checked=\"checked\"" : "")?> /></div>
<?
	}

	/**
	 * WYSIWYG editor
	 *
	 * @param string $fieldName
	 * @param string $fieldValue
	 * @param string $title
	 * @param string $subparams
	 * @param string $errorMessage
	 */
	private function editorWysiwyg( $fieldName, $fieldValue, $title, $subparams, $errorMessage )
	{
		if ($this->layout) {
			$this->layout->addJavaScriptSrc( $this->layout->staticUrl( "fckeditor/fckeditor.js", 1 ) );
			O_Js_Middleware::getFramework()->addDomreadyCode(
					"
var oFCKeditor = new FCKeditor( 'oo-r-w-$fieldName' );
oFCKeditor.BasePath = '" . $this->layout->staticUrl( 'fckeditor/',
							1 ) . "';
oFCKeditor.ToolbarSet = 'Basic';
oFCKeditor.ReplaceTextarea();", $this->layout );
		}
		?>
<div class="oo-renderer-field">
<div class="oo-renderer-title">
<?=$title?>:</div>
<?
		if ($errorMessage) {
			?>
<div class="oo-renderer-error">
<?=$errorMessage?>
</div>
<?
		}
		?>
<textarea class="oo-renderer-field-wysiwyg" id="oo-r-w-<?=$fieldName?>"
	name="<?=$fieldName?>"><?=$fieldValue?></textarea></div>
<?
	}

	/**
	 * One-line input
	 *
	 * @param string $fieldName
	 * @param string $fieldValue
	 * @param string $title
	 * @param string $subparams
	 * @param string $errorMessage
	 */
	private function editorLine( $fieldName, $fieldValue, $title, $subparams, $errorMessage )
	{
		?>
<div class="oo-renderer-field">
<div class="oo-renderer-title">
<?=$title?>:</div>
<?
		if ($errorMessage) {
			?>
<div class="oo-renderer-error">
<?=$errorMessage?>
</div>
<?
		}
		?>
<input type="text" name="<?=$fieldName?>"
	value="<?=htmlspecialchars( $fieldValue )?>"
	class="oo-renderer-field-line" /></div>
<?
	}

	/**
	 * Simple textarea
	 *
	 * @param string $fieldName
	 * @param string $fieldValue
	 * @param string $title
	 * @param string $subparams
	 * @param string $errorMessage
	 */
	private function editorArea( $fieldName, $fieldValue, $title, $subparams, $errorMessage )
	{
		?>
<div class="oo-renderer-field">
<div class="oo-renderer-title">
<?=$title?>:</div>
<?
		if ($errorMessage) {
			?>
<div class="oo-renderer-error">
<?=$errorMessage?>
</div>
<?
		}
		?>
<textarea class="oo-renderer-field-area" name="<?=$fieldName?>"><?=htmlspecialchars( $fieldValue )?></textarea>
</div>
<?
	}

}
