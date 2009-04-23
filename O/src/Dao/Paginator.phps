<?php
/**
 * Simple class to provide pages on O_Dao_Query
 *
 * @author Dmitry Kourinski
 */
class O_Dao_Paginator {
	/**
	 * Current page number
	 *
	 * @var int
	 */
	protected $page;
	/**
	 * Number of elements per page
	 *
	 * @var int
	 */
	protected $perpage;
	/**
	 * Query object to build pages on
	 *
	 * @var O_Dao_Query
	 */
	protected $query;
	/**
	 * Total number of elements in query
	 *
	 * @var int
	 */
	protected $total_count;
	/**
	 * Array of current page elements
	 *
	 * @var O_Dao_Query
	 */
	protected $page_elements = Array ();
	/**
	 * Callback for building page urls. With one or two arguments -- page number and order
	 *
	 * @var callback
	 */
	protected $url_callback;
	/**
	 * Element ID for ajax envelop
	 *
	 * @var string
	 */
	protected $ajax_id;
	/**
	 * Current order for pager
	 *
	 * @var string
	 */
	protected $order;
	/**
	 * List of available orders
	 *
	 * @var array
	 */
	protected $orders_list = Array ();
	/**
	 * If current order is desc
	 *
	 * @var int
	 */
	protected $order_desc = 0;

	/**
	 * Constructor
	 *
	 * @param O_Dao_Query $query
	 * @param callback $url_callback
	 * @param int $perpage Default is app/paginator/perpage
	 * @param string $page_registry in app rootkey
	 * @param string $order_registry in app rootkey
	 * @param array $orders like urlpart => array("title", "field") or field=>title
	 */
	public function __construct( O_Dao_Query $query, $url_callback, $perpage = null, $page_registry = "paginator/page", array $orders = array(), $order_registry = "paginator/order" )
	{
		$this->query = clone $query;
		$this->perpage = (int)($perpage ? $perpage : O_Registry::get( "app/paginator/perpage" ));
		if (!$this->perpage)
			throw new O_Ex_WrongArgument( "Cannot build paginator for 0 objects per page." );
		$this->page = (int)O_Registry::get( "app/" . $page_registry );
		if (!$this->page)
			$this->page = 1;
		
		if (!is_callable( $url_callback ))
			throw new O_Ex_Logic( "Wrong callback for paginator url-builder." );
		$this->url_callback = $url_callback;
		
		if (count( $orders )) {
			$this->order = O_Registry::get( "app/" . $order_registry );
			$this->orders_list = $orders;
			foreach ($this->orders_list as $k => &$v) {
				if (!is_array( $v ))
					$v = array ("field" => $k, "title" => $v);
			}
			
			if (substr( $this->order, -5 ) == "-desc") {
				$this->order = substr( $this->order, 0, -5 );
				if (isset( $this->orders_list[ $this->order ] )) {
					$order = $this->orders_list[ $this->order ][ "field" ];
					$this->query->clearOrders()->orderBy( $order . " DESC" );
					$this->order_desc = 1;
				}
			} elseif (isset( $this->orders_list[ $this->order ] )) {
				$this->query->clearOrders()->orderBy( $this->orders_list[ $this->order ][ "field" ] );
			}
		}
		
		$this->page_elements = $this->query->setSqlOption( O_Db_Query::CALC_FOUND_ROWS )->limit( 
				$this->perpage * ($this->page - 1), $this->perpage );
		$this->page_elements->getAll( true );
		$this->total_count = $this->query->getFoundRows();
		
		if (!count( $this->page_elements ))
			throw new O_Ex_PageNotFound( "No elements on current page.", 404 );
	
	}

	/**
	 * Returns total elements count in query
	 *
	 * @return int
	 */
	public function countElements()
	{
		return $this->total_count;
	}

	/**
	 * Returns number of pages (last page number)
	 *
	 * @return int
	 */
	public function numPages()
	{
		return ceil( $this->total_count / $this->perpage );
	}

	/**
	 * Returns page elements to be shown
	 *
	 * @return O_Dao_Query
	 */
	public function elements()
	{
		return $this->page_elements;
	}

	/**
	 * Checks if next page can be shown
	 *
	 * @return bool
	 */
	public function hasNext()
	{
		return $this->page * $this->perpage < $this->total_count;
	}

	/**
	 * Checks if previous page can be shown
	 *
	 * @return bool
	 */
	public function hasPrev()
	{
		return $this->page > 1 && count( $this->page_elements );
	}

	/**
	 * Returns start index of this page elements
	 *
	 * @return int
	 */
	public function startIndex()
	{
		return ($this->page - 1) * $this->perpage + 1;
	}

	/**
	 * Returns end index of current page elements
	 *
	 * @return int
	 */
	public function endIndex()
	{
		return $this->startIndex() + count( $this->page_elements );
	}

	/**
	 * Current page number
	 *
	 * @return int
	 */
	public function currentPageNum()
	{
		return $this->page;
	}

	/**
	 * Returns array with available page numbers
	 *
	 * @param int $range from current page, default is app/paginator/range
	 * @param int $tailsRange from first and last page, default is app/paginator/tails_range
	 * @return array of page numbers
	 */
	public function getPagesRange( $range = null, $tailsRange = null )
	{
		if (!$range)
			$range = (int)O_Registry::get( "app/paginator/range" );
		if (is_null( $tailsRange ))
			$tailsRange = (int)O_Registry::get( "app/paginator/tails_range" );
		$pages = range( max( 1, $this->page - $range ), min( $this->page + $range, $this->numPages() ) );
		if ($tailsRange) {
			$pages = array_merge( 
					range( 1, 1 + $tailsRange > $this->numPages() ? $this->numPages() : 1 + $tailsRange ), $pages, 
					range( $this->numPages() - $tailsRange > 1 ? $this->numPages() - $tailsRange : 1, 
							$this->numPages() ) );
			$pages = array_unique( $pages );
			sort( $pages );
		}
		return $pages;
	}

	/**
	 * Returns array of formatted page numbers, with anchors or bold tag (for current page)
	 *
	 * @param int $range from current page, default is app/paginator/range
	 * @param int $tailsRange from first and last page, default is app/paginator/tails_range
	 * @return array of page link htmls
	 */
	public function getPagesHtml( $range = null, $tailsRange = null )
	{
		$pages = $this->getPagesRange( $range, $tailsRange );
		$html = Array ();
		foreach ($pages as $page) {
			$v = $page;
			if ($v != $this->page) {
				if ($v == 1 && $this->page > 3)
					$v = O_Registry::get( "app/paginator/first" );
				elseif ($v == $this->numPages() && $this->page < $this->numPages() - 3)
					$v = O_Registry::get( "app/paginator/last" );
				elseif ($v == $this->numPages() - 1 && $this->page < $this->numPages() - 3)
					$v = O_Registry::get( "app/paginator/next" );
				elseif ($v == 2 && $this->page > 3)
					$v = O_Registry::get( "app/paginator/prev" );
				if (!$v)
					$v = $page;
			}
			if ($page == $this->page) {
				$html[ $page ] = "<b>" . $v . "</b>";
			} else {
				$url = call_user_func( $this->url_callback, $page, $this->order . ($this->order_desc ? "-desc" : "") );
				if ($this->ajax_id) {
					$html[ $page ] = "<a href=\"javascript:void(0)\" onclick=\"" . O_Js_Middleware::getFramework()->ajaxHtml( 
							$this->ajax_id, $url, array ("mode" => $this->ajax_id) ) . "\">$v</a>";
				} else {
					$html[ $page ] = "<a href=\"" . $url . "\">$v</a>";
				}
			}
		}
		return $html;
	}

	/**
	 * Returns array with formatted orders html
	 *
	 * @return array
	 */
	public function getOrdersHtml()
	{
		$html = Array ();
		foreach ($this->orders_list as $k => $v) {
			$title = $v[ "title" ];
			if ($k == $this->order) {
				$order = $k . ($this->order_desc ? "" : "-desc");
				$title .= " " . ($this->order_desc ? "&uarr;" : "&darr;");
				$title = "<b>" . $title . "</b>";
			} else {
				$order = $k . "-desc";
			}
			$url = call_user_func( $this->url_callback, 1, $order );
			if ($this->ajax_id) {
				$html[ $order ] = "<a href=\"javascript:void(0)\" onclick=\"" . O_Js_Middleware::getFramework()->ajaxHtml( 
						$this->ajax_id, $url, array ("mode" => $this->ajax_id) ) . "\">$title</a>";
			} else {
				$html[ $order ] = "<a href=\"" . $url . "\">$title</a>";
			}
		}
		return $html;
	}

	/**
	 * Echoes pager div html
	 *
	 * @param int $range
	 * @param int $tailsRange
	 * @todo add customization via callbacks
	 */
	public function showPager( $range = null, $tailsRange = null )
	{
		$html = $this->getPagesHtml( $range, $tailsRange );
		$orders = $this->getOrdersHtml();
		if (count( $html ) <= 1 && !count( $orders ))
			return;
		
		echo "<div class='" . O_Registry::get( "app/paginator/css_envelop" ) . "'>";
		if (count( $html ) > 1) {
			echo "<div><span>" . O_Registry::get( "app/paginator/title" ) . ":</span>";
			foreach ($html as $page => $code) {
				if (isset( $prev ) && $page - $prev > 1)
					echo " ...";
				echo " ";
				echo $code;
				$prev = $page;
			}
			echo "</div>";
		}
		if (count( $orders )) {
			echo "<div><span>" . O_Registry::get( "app/paginator/order_title" ) . ":</span> ";
			foreach ($orders as $order_html) {
				echo $order_html . " &nbsp; ";
			}
			echo "</div>";
		}
		
		echo "</div>";
	}

	/**
	 * Echoes page with pager
	 *
	 * @param O_Html_Layout $layout
	 * @param const $type
	 * @param int $range
	 * @param int $tailsRange
	 * @todo add envelop callback for page due to render type and active record class parameters like -paginator-$type
	 */
	public function show( O_Html_Layout $layout = null, $type = O_Dao_Renderer::TYPE_LOOP, $range = null, $tailsRange = null )
	{
		if ($this->ajax_id) {
			if (!$this->isAjaxPageRequest()) {
				if (!$layout)
					throw new O_Ex_Logic( "Cannot build ajax pager without layout object." );
				O_Js_Middleware::getFramework()->addSrc( $layout );
				$isNormal = 1;
			}
			
			if (isset( $isNormal )) {
				echo "<div id='$this->ajax_id'>";
			}
		}
		
		ob_start();
		$this->showPager( $range, $tailsRange );
		$pager = ob_get_flush();
		$this->page_elements->show( $layout, $type );
		echo $pager;
		
		if ($this->ajax_id && isset( $isNormal )) {
			echo "</div>";
		}
	}

	/**
	 * Sets shower into ajax mode, or switches it off
	 *
	 * @param bool $isAjax
	 * @param string $id required if you need to display several pagers on one page
	 */
	public function setModeAjax( $isAjax = true, $id = null )
	{
		if (!$isAjax)
			$this->ajax_id = false;
		else
			$this->ajax_id = $id ? $id : "pager-reload";
	}

	/**
	 * setModeAjax must be called first.
	 * If this returns true, you should call show() and return
	 *
	 * @return bool
	 */
	public function isAjaxPageRequest()
	{
		return $this->ajax_id && O_Registry::get( "app/env/request_method" ) == "POST" && O_Registry::get( 
				"app/env/params/mode" ) == $this->ajax_id;
	}

}