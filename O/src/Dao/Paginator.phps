<?php
/**
 * Simple class to provide pages on O_Dao_Query
 *
 * @copyright Dmitry Kourinski
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
	 * Callback for building page urls. With one argument -- page number
	 *
	 * @var callback
	 */
	protected $url_callback;

	/**
	 * Constructor
	 *
	 * @param O_Dao_Query $query
	 * @param callback $url_callback
	 * @param int $perpage Default is app/paginator/perpage
	 * @param string $page_registry in app rootkey
	 */
	public function __construct( O_Dao_Query $query, $url_callback, $perpage = null, $page_registry = "paginator/page" )
	{
		$this->query = clone $query;
		$this->perpage = (int)($perpage ? $perpage : O_Registry::get( "app/paginator/perpage" ));
		if (!$this->perpage)
			throw new Exception( "Cannot build paginator for 0 objects per page." );
		$this->page = (int)O_Registry::get( "app/" . $page_registry );
		if (!$this->page)
			$this->page = 1;

		if (!is_callable( $url_callback ))
			throw new Exception( "Wrong callback for paginator url-builder." );
		$this->url_callback = $url_callback;

		$this->page_elements = $this->query->setSqlOption( O_Db_Query::CALC_FOUND_ROWS )->limit(
				$this->perpage * ($this->page - 1), $this->perpage );
		$this->page_elements->getAll( true );
		$this->total_count = $this->query->getFoundRows();

		if (!count( $this->page_elements ))
			throw new Exception( "No elements on current page." );

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
			$pages = array_merge( range( 1, 1 + $tailsRange > $this->numPages() ? $this->numPages() : 1+$tailsRange ), $pages,
					range( $this->numPages() - $tailsRange > 1 ? $this->numPages() - $tailsRange : 1 , $this->numPages()) );
			$pages = array_unique( $pages );
			sort($pages);
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
			$html[ $page ] = $page == $this->page ? "<b>$page</b>" : "<a href=\"" . call_user_func(
					$this->url_callback, $page ) . "\">$page</a>";
		}
		return $html;
	}

	/**
	 * Echoes pager div html
	 *
	 * @param int $range
	 * @param int $tailsRange
	 */
	public function showPager( $range = null, $tailsRange = null )
	{
		// TODO: remove Hardcode!
		$html = $this->getPagesHtml( $range, $tailsRange );
		if (count( $html ) <= 1)
			return;
		echo "<div class='o-pager'><span>Pages:</span>";
		foreach ($html as $page => $code) {
			if (isset( $prev ) && $page - $prev > 1)
				echo " ...";
			echo " ";
			echo $code;
			$prev = $page;
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
		ob_start();
		$this->showPager( $range, $tailsRange );
		$pager = ob_get_flush();
		$this->page_elements->show( $layout, $type );
		echo $pager;
	}
}