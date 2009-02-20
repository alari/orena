<?php
/**
 * Displays one O_Dao_ActiveRecord object in HTML.
 *
 * To use this, type "-show[ callback]" (or "-loop[ callback]") in each Dao field you want to be displayed.
 * "-loop" params will be used in (simplified) loop rendering, e.g. article anonces.
 * @see O_Html_DaoRenderer_Loop
 *
 * Callback (optional) must look like "[classname::]methodname[ params]". ("[]" means that it's optional.)
 * If it contains classname, classname::methodname($record, $fieldName, $title, $params, $layout) will be called.
 * (To specify $title use "-title $title" flag in field info).
 * Otherwise default render method is called.
 *
 * Usually this class is used via
 * @see O_Dao_Renderer::show()
 *
 * @author Dmitry Kourinski
 */
class O_Html_DaoRenderer_Show {
	/**
	 * Layout object to modify by fields
	 *
	 * @var O_Html_Layout
	 */
	private $layout;
	/**
	 * Object to show
	 *
	 * @var O_Dao_ActiveRecord
	 */
	private $record;
	/**
	 * Mode of display -- "loop" or "show"
	 * TODO: add ability to use other words as suffixes for "-show-" key
	 *
	 * @var string
	 */
	private $mode;

	/**
	 * Constructor
	 *
	 * @param O_Dao_ActiveRecord $record
	 * @param O_Html_Layout $layout
	 * @param string $mode
	 */
	public function __construct( O_Dao_ActiveRecord $record, O_Html_Layout $layout = null, $mode = O_Dao_Renderer::MODE_SHOW )
	{
		$this->record = $record;
		$this->layout = $layout;
		$this->mode = $mode;
	}

	/**
	 * Echoes rendered form
	 *
	 */
	public function display()
	{
		// TODO: add usable classes for renderer, store them in "fw/renderer" registry key
		$tableInfo = O_Dao_TableInfo::get( get_class( $this->record ) );
		?>
<div class="oo-renderer">
<?
		foreach (array_keys( $tableInfo->getFields() ) as $fieldName) {
			$this->displayField( $fieldName, $this->record ? $this->record->$fieldName : null );
		}
		?>
</div>
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
		$fieldInfo = O_Dao_TableInfo::get( $this->record )->getFieldInfo( $fieldName );
		$param = $fieldInfo->getParam( $this->mode );
		
		if (!$param)
			return;
		
		$title = $fieldInfo->getParam( "title" );
		if (!$title)
			$title = ucfirst( str_replace( "_", " ", $fieldName ) );
		
		$subparams = "";
		
		if ($param === 1) {
			// Auto-generate renderer by field type
			if (!$fieldInfo->isAtomic()) {
				// TODO: add tests for loop
				if ($fieldValue instanceof O_Dao_Query)
					$callback = "loop";
				else
					throw new Exception( "Cannot autogenerate renderer for non-atomic field!" );
			} else {
				$callback = "simple";
			}
		} else {
			
			if (strpos( $param, " " )) {
				list ($callback, $subparams) = explode( " ", $param, 2 );
			} else {
				$callback = $param;
			}
		}
		
		if (!strpos( $callback, "::" )) {
			$callback = "shower" . ucfirst( $callback );
			if (!method_exists( $this, $callback ))
				throw new Exception( "Not a default renderer: $callback." );
			$this->$callback( $fieldValue, $title, $subparams );
			return;
		}
		
		call_user_func_array( $callback, array ($this->record, $fieldName, $title, $subparams, $this->layout) );
	}

	/**
	 * WYSIWYG editor
	 *
	 * @param string $fieldValue
	 * @param string $title
	 * @param string $subparams
	 */
	private function showerWysiwyg( $fieldValue, $title, $subparams )
	{
		echo '<div id="oo-renderer-wysiwyg">', $fieldValue, "</div>";
	}

	/**
	 * One-line input
	 *
	 * @param string $fieldValue
	 * @param string $title
	 * @param string $subparams
	 */
	private function showerSimple( $fieldValue, $title, $subparams )
	{
		echo '<div id="oo-renderer-simple">', $title, ": ", $fieldValue, "</div>";
	}

	/**
	 * Simple textarea
	 *
	 * @param string $fieldValue
	 * @param string $title
	 * @param string $subparams
	 */
	private function showerArea( $fieldValue, $title, $subparams )
	{
		echo '<div id="oo-renderer-area">', htmlspecialchars( $fieldValue ), "</div>";
	}

	/**
	 * Shows field values in a loop mode
	 *
	 * @param O_Dao_Query $fieldValue
	 * @param string $title
	 * @param string $subparams
	 */
	private function showerLoop( $fieldValue, $title, $subparams )
	{
		// TODO: $title must be used
		if ($fieldValue instanceof O_Dao_Query)
			O_Dao_Renderer::showLoop( $fieldValue, $this->layout, $subparams );
	}

}