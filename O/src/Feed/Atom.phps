<?php

class O_Feed_Atom {
	protected $query;
	protected $type;
	protected $title;
	protected $subtitle;
	protected $link;
	protected $updated;

	public function __construct( O_Dao_Query $query, $link, $title, $subtitle, $type = "atom" )
	{
		$this->query = $query;
		$this->type = $type;
		$this->link = $link;
		$this->title = $title;
		$this->subtitle = $subtitle;
		$this->lastBuildDate = time();
	}

	public function setUpdatedDate( $time )
	{
		$this->updated = $time;
	}

	public function show()
	{
		header( "Content-type: application/atom+xml; charset=utf-8" );
		echo '<?xml version="1.0" encoding="utf-8"?>';
		?>
   <feed xmlns="http://www.w3.org/2005/Atom">
     <title type="text"><?=$this->title?></title>
     <subtitle type="html"><?=$this->subtitle?></subtitle>
     <updated><?=date("Y-m-dTH:i:sZ", $this->updated)?></updated>
     <id><?=$this->link?></id>
     <link rel="alternate" type="text/html" href="<?=$this->link?>"/>
     <rights>Copyright (c) 2003, Mark Pilgrim</rights>
     <generator uri="http://orena.org/" version="1.0">
       Orena Framework
     </generator>
<?
		foreach ($this->query as $item)
			$item->show( null, $this->type );
		echo "</feed>";
	}

}