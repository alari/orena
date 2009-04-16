<?php

class O_Html_Layout {
	/**
	 * Template object to display
	 *
	 * @var O_Html_Template
	 */
	protected $tpl;
	
	/**
	 * Page title -- can be externally modified
	 *
	 * @var string
	 */
	public $title;
	
	/**
	 * Array of metatags
	 *
	 * @var Array
	 */
	protected $metas = Array ();
	/**
	 * Array of javascript source urls
	 *
	 * @var Array
	 */
	protected $jscriptSrc = Array ();
	/**
	 * Array of javascript codeblocks
	 *
	 * @var Array
	 */
	protected $jscriptCode = Array ();
	/**
	 * Array of css sources
	 *
	 * @var Array
	 */
	protected $cssSrc = Array ();
	/**
	 * Source of page shortcut icon
	 *
	 * @var string
	 */
	protected $favicon;
	/**
	 * Page's content type
	 *
	 * @var string
	 */
	protected $contentType = "text/html; charset=utf-8";
	/**
	 * Different links like "alternate"
	 *
	 * @var Array
	 */
	protected $headLinks = Array ();
	
	/**
	 * HTTP response code
	 *
	 * @var int
	 */
	private $responseCode = 200;
	/**
	 * HTTP response message
	 *
	 * @var string
	 */
	private $responseMessage = "OK";

	/**
	 * Creates an instance of html-layout
	 *
	 * @param O_Html_Template $tpl
	 */
	public function __construct( O_Html_Template $tpl )
	{
		$this->tpl = $tpl;
	}

	/**
	 * Echoes the page
	 *
	 */
	public function display()
	{
		Header( "HTTP/1.1 " . $this->responseCode . " " . $this->responseMessage );
		if ($this->contentType)
			Header( "Content-type: " . $this->contentType );
			
		// TODO find the way to avoid output bufferization
		ob_start();
		$this->displayBody();
		$body = ob_get_clean();
		
		// TODO create function to draw doctype
		$this->displayDoctype();     
		?>
<html>
<head>
		<?
		$this->displayHead()?>
	</head>
<body>
		<?
		echo $body;
		?>
	</body>
</html>
<?
	}

	/**
	 * Echoes doctype
	 * 
	 */
	protected function displayDoctype()
	{
		?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?
	}
	
	/**
	 * Echoes contents of head tag
	 *
	 */
	protected function displayHead()
	{
		if ($this->contentType)
			echo '<meta http-equiv="Content-type" content="' . $this->contentType . '" />';
		if ($this->title)
			echo '<title>', $this->title, "</title>";
		if ($this->favicon)
			echo '<link rel="SHORTCUT ICON" href="' . $this->favicon . '"/>';
		foreach ($this->cssSrc as $href)
			echo '<link rel="Stylesheet" type="text/css" href="' . $href . '"/>';
		foreach ($this->jscriptSrc as $src)
			echo '<script type="text/javascript" src="', $src, '"></script>';
		foreach ($this->jscriptCode as $code)
			echo "<script type=\"text/javascript\">\n$code\n</script>";
		foreach ($this->metas as $meta)
			echo '<meta name="' . $meta[ 0 ] . '" content="' . $meta[ 1 ] . '" />';
		foreach ($this->headLinks as $link) {
			echo "<link rel=\"{$link['rel']}\" href=\"{$link['href']}\"";
			if ($link[ "type" ])
				echo ' type="' . $link[ "type" ] . '"';
			if ($link[ "title" ])
				echo ' title="' . htmlspecialchars( $link[ "title" ] ) . '"';
			echo " />";
		}
	}

	/**
	 * Echoes contents of body tag
	 *
	 */
	protected function displayBody()
	{
		$this->tpl->displayContents();
	}

	/**
	 * Sets code and message for the first HTTP response header
	 *
	 * @param int $code
	 * @param string $message
	 */
	public function setResponseStatus( $code, $message )
	{
		$this->responseCode = (int)$code;
		$this->responseMessage = $message;
	}

	/**
	 * Adds a link tag like <link rel=...
	 *
	 * @param string $rel
	 * @param string $href
	 * @param string $type
	 * @param string $title
	 */
	public function addHeadLink( $rel, $href, $type = null, $title = null )
	{
		$this->headLinks[] = Array ("rel" => $rel, "href" => $href, "type" => $type, "title" => $title);
	}

	/**
	 * Adds meta name=... tag
	 *
	 * @param string $name
	 * @param string $content
	 */
	public function addMeta( $name, $content )
	{
		$this->metas[] = Array ($name, $content);
	}

	/**
	 * Adds javascript sourcefile URL
	 *
	 * @param string $src
	 */
	public function addJavaScriptSrc( $src )
	{
		$src = $this->staticUrl( $src );
		if (!in_array( $src, $this->jscriptSrc ))
			$this->jscriptSrc[] = $src;
	}

	/**
	 * Adds block of javascript code into page's head
	 *
	 * @param string $code
	 */
	public function addJavaScriptCode( $code )
	{
		$this->jscriptCode[] = $code;
	}

	/**
	 * Adds source url of CSS file.
	 *
	 * @param string $src URL from app/static_root, without trailing slash
	 */
	public function addCssSrc( $src )
	{
		$src = $this->staticUrl( $src );
		if (!in_array( $src, $this->cssSrc ))
			$this->cssSrc[] = $src;
	}

	/**
	 * Sets URL of page shortcut icon source
	 *
	 * @param string $src
	 */
	public function setFavicon( $src )
	{
		$src = $this->staticUrl( $src );
		$this->favicon = $src;
	}

	/**
	 * Sets page title
	 *
	 * @param string $title
	 */
	public function setTitle( $title )
	{
		$this->title = $title;
	}

	/**
	 * Returns full URL to static file
	 *
	 * @param string $url
	 * @param bool $fw If set to true, framework static root is used
	 * @return string
	 */
	public function staticUrl( $url, $fw = false )
	{
		return O_UrlBuilder::getStatic( $url, $fw );
	}

	public function url( $url, array $params = array() )
	{
		return O_UrlBuilder::get( $url, $params );
	}

}