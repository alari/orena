<?php

class O_Feed_Rss {
	protected $query;
	protected $type;
	protected $generator = "Orena.org";
	protected $title;
	protected $link;
	protected $lastBuildDate;

	public function __construct(O_Dao_Query $query, $link, $title, $type="rss") {
		$this->query = $query;
		$this->type = $type;
		$this->link = $link;
		$this->title = $title;
		$this->lastBuildDate = time();
	}

	public function setLastBuildDate($time) {
		$this->lastBuildDate = $time;
	}

	public function show() {
		header("Content-type: application/rss+xml; charset=utf-8");
		echo '<?xml version="1.0" encoding="utf-8"?>';
		?>
<rss version="2.0">
  <channel>
    <title><?=htmlspecialchars($this->title)?></title>
    <link><?=$this->link?></link>
    <lastBuildDate><?=gmdate("D, d M Y H:i:s", $this->lastBuildDate)?> GMT</lastBuildDate>
    <generator><?=htmlspecialchars($this->generator)?></generator>
		<?
    foreach($this->query as $item) $item->show(null, $this->type);
    echo "</channel></rss>";
	}



}