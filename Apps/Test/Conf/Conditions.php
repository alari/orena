<?php
O("*conditions", function(){
	if(O("~http_host") == "test.orena.org") {
		O("*mode", "testing");
		O("_app/db/default", Array(
			"engine"=>"mysql",
			"host"=>"localhost:3306",
			"dbname"=>"orena_tests",
			"user"=>"orena",
			"password"=>"12354"
		));
	} elseif(O("~http_host") == "orena.dev") {
		O("*mode", "testing");
		// no db yet
		O("_app/db/default", Array("engine"=>"-"));
	}
	if(O("*mode")){
		O("_prefix", "Test");
		O("_ext", "php");
	}
});