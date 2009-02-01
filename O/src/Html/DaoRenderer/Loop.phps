<?php
/**
 * Renders a loop given in O_Dao_Query.
 *
 * To use it, type "-loop [$callback]" (to specify any specifical callback; not required) in
 * O_Dao_ActiveRecord table config, or "-show loop [$callback]" (or just "-show")
 * in whatever-to-many relation field.
 * Then call one of the following:
 *
 * To simply display the query:
 * @see O_Dao_Query->display()
 * @see O_Dao_Renderer::showLoop()
 *
 * To display a relation field:
 * @see O_Dao_Renderer::show()
 *
 * Callback could be given in the form of "[classname::]methodname[ params]".
 * ("[]" means optional substring.)
 * If no classname specified, built-in renderer is used.
 * Otherwise classname::methodname(query, layout, params) will be called.
 *
 * @author Dmitry Kourinski
 */
class O_Html_DaoRenderer_Loop {
	/**
	 * Layout object to modify by fields
	 *
	 * @var O_Html_Layout
	 */
	private $layout;
	/**
	 * Object to show
	 *
	 * @var O_Dao_Query
	 */
	private $query;
	/**
	 * Classname of objects we display
	 *
	 * @var string
	 */
	private $class;
	/**
	 * Params string -- if loop rendered from inside of another renderer
	 *
	 * @var string
	 */
	private $callback_params = "";

	/**
	 * Constructor
	 *
	 * @param O_Dao_Query $query
	 * @param O_Html_Layout $layout
	 * @param string $callback_params Config params for loop query
	 */
	public function __construct( O_Dao_Query $query, O_Html_Layout $layout = null, $callback_params = "" )
	{
		$this->query = $query;
		$this->layout = $layout;
		$this->callback_params = $callback_params;
		$this->class = get_class( $query->current() );
	}

	/**
	 * Echoes rendered query
	 *
	 */
	public function display()
	{
		$tableInfo = O_Dao_TableInfo::get( $this->query );
		$callback = $this->callback_params ? $this->callback_params : $tableInfo->getParam( "loop" );
		$params = "";
		if ($callback === 1 || !$callback) {
			$callback = "simple";
		} else {
			if (strpos( $callback, " " )) {
				list ($callback, $params) = explode( " ", $callback, 2 );
			}
		}
		
		if (!strpos( $callback, "::" )) {
			$callback = "shower" . ucfirst( $callback );
			if (!method_exists( $this, $callback ))
				throw new Exception( "Not a default loop renderer: $callback." );
			$this->$callback( $params );
			return;
		}
		
		call_user_func_array( $callback, array ($this->query, $this->layout, $params) );
	}

	/**
	 * Just renders instances as a list
	 *
	 * @param string $params
	 */
	private function showerSimple( $params )
	{
		?>
<div class="oo-renderer-loop">
<?
		foreach ($this->query as $record)
			O_Dao_Renderer::show( $record, $this->layout, "loop" );
		?>
</div>
<?
	}
}