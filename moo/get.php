<?php
const SOURCES_ENV = "MOO_SCRIPT_SOURCES";
$addLibs = Array();
$addLibsKey = null;
if(array_key_exists(SOURCES_ENV, $_SERVER)) {
	$addLibsKey = SOURCES_ENV;
} elseif(array_key_exists("REDIRECT_".SOURCES_ENV, $_SERVER)) {
	$addLibsKey = "REDIRECT_".SOURCES_ENV;
}
$addConfig = Array("libs"=>Array());
if($addLibsKey) {
	$addLibs = explode(",", $_SERVER[$addLibsKey]);
	foreach($addLibs as $conf) {
		list($lib, $scripts) = explode(":", $conf, 2);
		$addConfig["libs"]["lib"] = Array("scripts"=>"../".$scripts);
	}
}

if (!file_exists('_cache')) mkdir('_cache');
require_once '_php/depender.php';

$depender = New Depender($addConfig);
if ($depender->getVar('require') || $depender->getVar('requireLibs') || $depender->getVar('client')) {
	$depender->build();
} else if ($depender->getVar('reset')) {
	$depender->deleteCache('flat');
}