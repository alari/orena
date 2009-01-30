<?php
ini_set("include_path", ini_get("include_path").PATH_SEPARATOR."./O");
function __autoload($class){
	$filename = str_replace("_", "/", $class).".php";
	include_once is_file($filename) ? $filename : $filename."s";
}

Registry::set( "fw/html/static_root", "./O/static/" );

@Db_Manager::connect(array(
	"host"=>"localhost",
	"dbname"=>"Orena",
	"engine"=>"mysql"
));
Db_Manager::getConnection()->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
Db_Manager::getConnection()->setAttribute(PDO::ATTR_AUTOCOMMIT, true);

$tpl = new Test_Templates_Main;
$tpl->display();

/*$r = Dao_ActiveRecord::getById(59,"Test_Models_Core");

Dao_Renderer::show($r);
$q = new Dao_Query("Test_Models_Core");
Dao_Renderer::showLoop($q->where("intfield is not null"));*/