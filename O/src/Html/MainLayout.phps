<?php
class O_Html_MainLayout extends O_Html_Layout {
	/**
	 * Source of main css-file of layout.
	 * If not specified by extensions, default one is used
	 *
	 * @var string
	 */
	protected $mainCssHref;

	/**
	 * Redefine O_Html_Layout::displayDoctype
	 *
	 */
	protected function displayDoctype()
	{
		?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<?
	}

	/**
	 * Simple layout with three columns
	 *
	 */
	public function displayBody()
	{
		?>
<div id="body">
<div id="wrap">
<div id="cont">
		<?
		$this->displayContents();
		?>
</div>

<div id="lcol"><?
		$this->displayColLeft();
		?></div>
<div id="rcol"><?
		$this->displayColRight();
		?></div>
</div>

<div id="head"><?
		$this->displayHeader();
		?></div>
<div id="foot"><?
		$this->displayFooter();
		?></div>
</div>
<?
	}

	/**
	 * Default contents of page header
	 *
	 */
	protected function displayHeader()
	{
		echo $this->title;
	}

	/**
	 * Default contents of page footer
	 *
	 */
	protected function displayFooter()
	{
		?>
&copy;
<a href="http://orena.org/">Orena Framework</a>
2008-2009
<?
	}

	/**
	 * Default contents of middle zone
	 *
	 */
	protected function displayContents()
	{
		$this->tpl->displayContents();
	}

	/**
	 * Contents for left column
	 * @todo add interface
	 */
	protected function displayColLeft()
	{
		?>
todo: add interface for left col generator
<?
	}

	/**
	 * Contents for right column
	 * @todo add interface
	 */
	protected function displayColRight()
	{
		?>
todo: add interface for right col generator
<?
	}

	/**
	 * Contents of HEAD tag -- adds main css file source
	 *
	 */
	protected function displayHead()
	{
		$this->addCssSrc( 
				$this->mainCssHref ? $this->mainCssHref : O_Registry::get( "fw/html/static_root" ) . "css/main.css" );
		parent::displayHead();
	}

}