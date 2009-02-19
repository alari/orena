<?php
require_once './O/src/EntryPoint.phps';

O_Db_Manager::connect(array(
	"host"=>"localhost",
	"dbname"=>"Orena",
	"engine"=>"mysql"
));

O_ClassManager::registerPrefix("PHPUnit", "./PHPUnit");

O_EntryPoint::processRequest();
//exit;