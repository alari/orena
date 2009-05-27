<?php
/**
 * Command to show query of active records in a loop, with paginator/
 *
 * Registry:
 * cmd/template -- template class name to show the query with
 * cmd/show/source -- query|user field|field -- where to get objects from
 * cmd/show/class -- class to get query if source is set to query or null
 * cmd/show/registry -- registry key to get resourse (if source is set to "field")
 * cmd/show/field -- relative field to show (if source is set to "field" or "user field")
 * cmd/show/order_by -- default query order, if needed
 * cmd/show/orders -- array of orders as field=>title
 * cmd/show/url_pattern -- pattern to build url via command, with {PAGE} and {ORDER} placements, and other {...}'s for resourse field displaying
 * cmd/show/url_callback -- static callback to build url with
 * (if no url callback is provided, resourse->url will be used)
 * cmd/show/type -- type of showing, default is loop, see O_Dao_Renderer for details
 * cmd/show/ajax -- process paging via ajax or by normal pages
 *
 * Other registry keys --
 * @see O_Dao_Paginator
 *
 * Notice: you can use "layout_class" registry key to easily change formatting of default templates
 *
 * @author Dmitry Kourinski
 */
class O_Cmd_ShowLoop extends O_Command {
	
	private $url_pattern;
	private $resourse;

	public function process()
	{
		$tpl = O_Registry::get( "app/cmd/template" ) ? $this->getTemplate( 
				O_Registry::get( "app/cmd/template" ), true ) : $this->getTemplate();
		
		$type = O_Registry::get( "app/cmd/show/source" );
		switch ($type) {
			// User related field
			case "user field" :
				$resourse = O_Acl_Session::getUser();
			// Resourse related field
			case "field" :
				if (!$resourse) {
					$resourse = O_Registry::get( O_Registry::get( "app/cmd/show/registry" ) );
				}
				if (!$resourse instanceof O_Dao_ActiveRecord) {
					throw new O_Ex_NotFound( "Resourse not found.", 404 );
				}
				$field = O_Registry::get( O_Registry::get( "app/cmd/show/field" ) );
				$query = $resourse->$field;
				$this->resourse = $resourse;
			break;
			default :
				$query = O_Dao_Query::get( O_Registry::get( "app/cmd/show/class" ) );
		}
		if (!$query instanceof O_Dao_Query) {
			throw new O_Ex_NotFound( "Wrong query provided.", 404 );
		}
		
		// Default order
		if (O_Registry::get( "app/cmd/show/order_by" )) {
			$query->orderBy( O_Registry::get( "app/cmd/show/order_by" ) );
		}
		
		// Other orders
		$orders = O_Registry::get( "app/cmd/show/orders" );
		if (!is_array( $orders ))
			$orders = array ();
			
		// Url pattern
		$this->url_pattern = O_Registry::get( "app/cmd/show/url_pattern" );
		if ($this->url_pattern) {
			$this->url_pattern = preg_replace_callback( "#{([^}]+)}#", 
					array ($this, "replaceInUrlPattern"), $this->url_pattern );
			$url_callback = array ($this, "url");
		} elseif (O_Registry::get( "app/cmd/show/url_callback" )) {
			$url_callback = O_Registry::get( "app/cmd/show/url_callback" );
		} else {
			$url_callback = array ($this->resourse, "url");
		}
		
		$tpl->paginator = $query->getPaginator( $url_callback, null, "paginator/page", $orders );
		if (O_Registry::get( "app/cmd/show/type" ))
			$tpl->type = O_Registry::get( "app/cmd/show/type" );
			
		// Ajax response
		if (O_Registry::get( "app/cmd/show/ajax" )) {
			$tpl->paginator->setModeAjax();
			
			if ($tpl->paginator->isAjaxPageRequest()) {
				$tpl->paginator->show( $tpl->layout(), $tpl->type );
				return null;
			}
		}
		
		return $tpl;
	}

	/**
	 * Url pattern replacement
	 *
	 * @param array $match
	 * @return string
	 * @access private
	 */
	public function replaceInUrlPattern( $match )
	{
		$m = $match[ 1 ];
		if ($m == "PAGE" || $m == "ORDER")
			return $match[ 0 ];
		return urlencode( $this->resourse->$m );
	}

	/**
	 * Default url builder for pages
	 *
	 * @param int $page
	 * @param string $order
	 * @return string
	 */
	public function url( $page, $order )
	{
		return O_UrlBuilder::get( 
				str_replace( array ("{PAGE}", "{ORDER}"), array ($page, $order), 
						$this->url_pattern ) );
	}

}