<?php

class Html_Layout {
	/**
	 * Template object to display
	 *
	 * @var Html_Template
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
	protected $metas = Array();
	/**
	 * Array of javascript source urls
	 *
	 * @var Array
	 */
	protected $jscriptSrc = Array();
	/**
	 * Array of javascript codeblocks
	 *
	 * @var Array
	 */
	protected $jscriptCode = Array();
	/**
	 * Array of css sources
	 *
	 * @var Array
	 */
	protected $cssSrc = Array();
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
	 * @param Html_Template $tpl
	 */
	public function __construct(Html_Template $tpl) {
		$this->tpl = $tpl;
	}

	/**
	 * Echoes the page
	 *
	 */
	public function display() {
		Header("HTTP/1.1 ".$this->responseCode." ".$this->responseMessage);
		Header("Content-type: ".$this->contentType);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<title><?=$this->title?></title>
		<?$this->displayHead()?>
	</head><body>
		<?$this->displayBody()?>
	</body>
</html>
<?
	}

	/**
	 * Echoes contents of head tag
	 *
	 */
	protected function displayHead() {
		if($this->contentType) echo '<meta http-equiv="Content-type" content="'.$this->contentType.'" />';
		if($this->title) echo '<title>', $this->title, "</title>";
		if($this->favicon) echo '<link rel="SHORTCUT ICON" href="'.$this->favicon.'"/>';
		foreach($this->cssSrc as $href) echo '<link rel="Stylesheet" type="text/css" href="'.$href.'"/>';
		foreach($this->jscriptSrc as $src) echo '<script type="text/javascript" src="',$src,'"></script>';
		foreach($this->jscriptCode as $code) echo "<script type=\"text/javascript\">\n$code\n</script>";
		foreach($this->metas as $meta) echo '<meta name="'.$meta[0].'" content="'.$meta[1].'" />';
	}

	/**
	 * Echoes contents of body tag
	 *
	 */
	protected function displayBody() {
		$this->tpl->displayContents();
	}

	/**
	 * Sets code and message for the first HTTP response header
	 *
	 * @param int $code
	 * @param string $message
	 */
	public function setResponseStatus($code, $message) {
		$this->responseCode = (int)$code;
		$this->responseMessage = $message;
	}


	/**
	 * Adds meta name=... tag
	 *
	 * @param string $name
	 * @param string $content
	 */
	public function addMeta($name, $content) {
		$this->metas[] = Array($name, $content);
	}

	/**
	 * Adds javascript sourcefile URL
	 *
	 * @param string $src
	 */
	public function addJavaScriptSrc($src) {
		if(!in_array($src, $this->jscriptSrc)) $this->jscriptSrc[] = $src;
	}

	/**
	 * Adds block of javascript code into page's head
	 *
	 * @param string $code
	 */
	public function addJavaScriptCode($code) {
		$this->jscriptCode[] = $code;
	}

	/**
	 * Adds source url of CSS file
	 *
	 * @param string $src
	 */
	public function addCssSrc($src) {
		if(!in_array($src, $this->cssSrc)) $this->cssSrc[] = $src;
	}

	/**
	 * Sets URL of page shortcut icon source
	 *
	 * @param string $src
	 */
	public function setFavicon($src) {
		$this->favicon = $src;
	}

	/**
	 * Sets page title
	 *
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}
}