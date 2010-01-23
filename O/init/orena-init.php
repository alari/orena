<?php
/**
 * 0) read applications root (Apps/)
 * 1) read project name (folder)
 * 2) handle array of default files
 */

// Declare routines

function error($msg){
	fputs(STDERR, "Error! $msg");
	exit(1);
}

function request($msg, $format="%s") {
	println($msg);
	fscanf(STDIN, $format."\n", $ret);
	return $ret;
}

function println($msg) {
	fputs(STDOUT, $msg."\n");
}

function askForDirectory($question){
	$dirname = request($question);
	if(!$dirname) {
		$dirname = getcwd();
	}
	if(!is_dir($dirname)) {
		error("$dirname is not a valid directory");
	}
	if(!is_writable($dirname)) {
		error("$dirname is not writeable");
	}
	if($dirname[strlen($dirname)-1] != "/") {
		$dirname .= "/";
	}
	return $dirname;
}

$placeholders = Array("?PHP" => '<?php');

function replacePatterns ($text, &$placeholders){
	return preg_replace_callback('#\${([^:]+?)(:([^:}]+?):([^:}]+?))?}#i', function($matches) use (&$placeholders){
		if(count($matches)>2) {
			$def = $matches[3];
			$v = request($matches[4], "%s");
			$placeholders[$matches[1]] = $v ? $v : $def;
		}
		return $placeholders[$matches[1]];
	}, $text);
}

println("Hello world!");
// Handle project root

$root= askForDirectory("Enter path to your site root directory (default is current).");
copy(__DIR__."/.htaccess", $root.".htaccess");
$text = file_get_contents(__DIR__."/entry.php");
println("Creating: entry.php");
file_put_contents($root."entry.php", (string)replacePatterns($text, &$placeholders));

// Handling base directory
$dirname = askForDirectory("Enter path to your Apps directory (default is current).");
copy(__DIR__."/.htaccess", $dirname.".htaccess");

// Requesting application name
do {
	$app_name = ucfirst(request("Enter application name ([A-Z][A-Za-z0-9]+)"));
} while(!$app_name || !preg_match("#^[A-Z][A-Za-z0-9]+$#", $app_name));

$dirname .= $app_name;
mkdir($dirname);
$dirname.="/";

// Creating directories
$dirs = Array("Cmd", "Conf", "Mdl", "Tpl");
foreach($dirs as $d) {
	mkdir($dirname.$d);
}

println("Directories were created");

$files = Array(
	"Conf/Conditions.conf",
	"Conf/Registry.conf",
	"Conf/Urls.conf",
	"Cmd/Default.phps",
	"Mdl/User.phps",
	"Tpl/Default.phps",
	"Layout.phps"
);

foreach($files as $f) {
	println("Creating: $f");
	$text = file_get_contents(__DIR__."/App/".$f);
	file_put_contents($dirname.$f, (string)replacePatterns($text, &$placehoders));
}

println("Finished!");


