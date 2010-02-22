<?php
const ADD_CONF = "MOO_ADD_CONF";
$addConfFile = null;
$addConfKey = null;
if(array_key_exists(ADD_CONF, $_SERVER)) {
	$addConfKey = ADD_CONF;
} elseif(array_key_exists("REDIRECT_".ADD_CONF, $_SERVER)) {
	$addConfKey = "REDIRECT_".ADD_CONF;
}
if($addConfKey) {
	$addConfFile = $_SERVER[$addConfKey];
}

if (!file_exists('_cache')) mkdir('_cache');
require_once '_php/depender.php';

$depender = New Depender($addConfFile);
if ($depender->getVar('require') || $depender->getVar('requireLibs') || $depender->getVar('client')) {
	$depender->build();
} else if ($depender->getVar('reset')) {
	$depender->deleteCache('flat');
}