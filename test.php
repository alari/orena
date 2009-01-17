<?php
function __autoload($class){
	$filename = str_replace("_", "/", $class).".php";
	include_once is_file($filename) ? $filename : $filename."s";
}
@Db_Manager::connect(array(
	"host"=>"localhost",
	"dbname"=>"Orena",
	"engine"=>"mysql"
));
Db_Manager::getConnection()->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
Db_Manager::getConnection()->setAttribute(PDO::ATTR_AUTOCOMMIT, true);

$tpl = new Test_Templates_Main;
$tpl->display();

//Dao_Renderer::edit(Dao_ActiveRecord::getById(55,"Test_Models_Core"), "/");