<?php

class O_Base_SearchEngines {
	private static $is_bot = null;
	private static $bot_sigs = Array (

	"Accoona", "Alexa", "ia_archiver", "antabot", "Ask Jeeves/Teoma", "Baiduspider", "curl",
									"EltaIndexer", "Feedfetcher-Google", "GameSpyHTTP",
									"Gigabot", "Googlebot", "gsa-crawler", "Grub",
									"Crawl your own stuff", "Web Bot", "Inktomi",
									"MihalismBot", "Msnbot", "OmniExplorer_Bot",
									"WorldIndexer", "PageBull", "Scooter", "W3C_Validator",
									"W3C_CSS_Validator", "WebAlta", "Wget",
									"YahooFeedSeeker", "Yahoo! Slurp", "Yahoo!-MMCrawler",
									"mmcrawler", "YandexBlog", "YandexSomething",
									"Yandex/1.01.001");



	static public function isBot()
	{
		if (is_bool( self::$is_bot ))
			return self::$is_bot;

		$user_agent = $_SERVER[ 'HTTP_USER_AGENT' ];
		foreach (self::$bot_sigs as $sig)
			if (strpos( $user_agent, $sig ) !== false) {
				return self::$is_bot = true;
			}
		return self::$is_bot = false;
	}

}