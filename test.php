<?php
require_once './O/src/ClassManager.phps';

O_ClassManager::registerPrefix("Test", "./Test");
O_ClassManager::registerPrefix("PHPUnit", "./PHPUnit");

O_Registry::set( "fw/html/static_root", "./O/static/" );

O_Db_Manager::connect(array(
	"host"=>"localhost",
	"dbname"=>"Orena",
	"engine"=>"mysql"
));
O_Db_Manager::getConnection()->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
O_Db_Manager::getConnection()->setAttribute(PDO::ATTR_AUTOCOMMIT, true);

$tpl = new Test_Templates_Main;
$tpl->display();