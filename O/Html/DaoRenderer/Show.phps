<?php
class Html_DaoRenderer_Show {
	/**
	 * Layout object to modify by fields
	 *
	 * @var Html_Layout
	 */
	private $layout;
	/**
	 * Object to show
	 *
	 * @var Dao_ActiveRecord
	 */
	private $record;
	/**
	 * Mode of display -- "loop" or "show"
	 *
	 * @var string
	 */
	private $mode;

	/**
	 * Constructor
	 *
	 * @param Dao_ActiveRecord $record
	 * @param Html_Layout $layout
	 * @param string $mode
	 */
	public function __construct( Dao_ActiveRecord $record, Html_Layout $layout = null, $mode = "show" )
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
		// TODO: add usable classes for renderer
		$tableInfo = Dao_TableInfo::get( get_class( $this->record ) );
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
		$fieldInfo = Dao_TableInfo::get( $this->record )->getFieldInfo( $fieldName );
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
				if ($fieldValue instanceof Dao_Query)
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
	 * @param Dao_Query $fieldValue
	 * @param string $title
	 * @param string $subparams
	 */
	private function showerLoop( $fieldValue, $title, $subparams )
	{
		if ($fieldValue instanceof Dao_Query)
			Dao_Renderer::showLoop( $fieldValue, $this->layout );
	}

}