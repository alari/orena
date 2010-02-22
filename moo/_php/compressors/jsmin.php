<?php

function jsmin($script, $path, $root){
	include_once $root.'_php/compressors/jsmin-1.1.1.php';
	return JSMin::minify($script);
}

?>