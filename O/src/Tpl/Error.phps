<?php
class O_Tpl_Error extends O_Html_Template {

	/**
	 * Exception object
	 *
	 * @var Exception
	 */
	public $e;

	public function __construct( Exception $e )
	{
		$this->e = $e;
	}

	public function displayContents()
	{
		$isProduction = O_Registry::get( "app/mode" ) == "production" &&0;
		$err = $this->e->getCode();
		$msg = $this->e->getMessage();
		if (!$err || $isProduction)
			$err = 500;
		if (!$msg || $isProduction)
			$msg = "Internal server error".($isProduction?" (prod. mode)":"");
		$this->layout()->setTitle( $err . ": " . $msg );
		?>
<h1>Error #<?=$err?></h1>
<strong><?=$msg?></strong>
<?
		if (!$isProduction) {
			?>
<p>
<pre>
<?=$this->e?>
</pre>
</p>
<?
		}
	}
}