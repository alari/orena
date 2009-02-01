<?php
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR."./O/src");
function __autoload($class){
	$filename = str_replace("_", "/", $class).".php";
	if(substr($filename, 0, 2) == "O/") $filename = substr($filename, 2);
	include_once is_file($filename) ? $filename : $filename."s";
}

O_Registry::set( "fw/html/static_root", "./O/static/" );

@O_Db_Manager::connect(array(
	"host"=>"localhost",
	"dbname"=>"Orena",
	"engine"=>"mysql"
));
O_Db_Manager::getConnection()->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
O_Db_Manager::getConnection()->setAttribute(PDO::ATTR_AUTOCOMMIT, true);

$tpl = new Test_Templates_Main;
$tpl->display();

/*$r = O_Dao_ActiveRecord::getById(59,"Test_Models_Core");

O_Dao_Renderer::show($r);
$q = new O_Dao_Query("Test_Models_Core");
O_Dao_Renderer::showLoop($q->where("intfield is not null"));*/