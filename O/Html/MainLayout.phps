<?php
class Html_MainLayout extends Html_Layout {
	/**
	 * Source of main css-file of layout.
	 * If not specified by extensions, default one is used
	 *
	 * @var string
	 */
	protected $mainCssHref;

	/**
	 * Simple layout with three columns
	 *
	 */
	public function displayBody()
	{
		?>
<div id="wrap">
<div id="cont">
		<?
		$this->displayContents();
		?>
</div>
</div>

<div id="head"><?
		$this->displayHeader();
		?></div>
<div id="lcol"><?
		$this->displayColLeft();
		?></div>
<div id="rcol"><?
		$this->displayColRight();
		?></div>

<div class="clear"><!--  --></div>

<div id="feet"><?
		$this->displayFooter();
		?></div>
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
				$this->mainCssHref ? $this->mainCssHref : Registry::get( "fw/html/static_root" ) . "css/main.css" );
		parent::displayHead();
	}

}