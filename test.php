<?php
require_once './O/src/ClassManager.phps';

O_ClassManager::registerPrefix("Test", "./Test");
O_ClassManager::registerPrefix("PHPUnit", "./PHPUnit");

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