<?php
include_once '_php/depender.php';

if (!file_exists('_cache')) mkdir('_cache');
$depender = New Depender;
if ($depender->getVar('require') || $depender->getVar('requireLibs') || $depender->getVar('client')) {
	$depender->build();
} else if ($depender->getVar('reset')) {
	$depender->deleteCache('flat');
}

