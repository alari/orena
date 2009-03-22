<?php

/**
 * @table test_acl_resourse
 * @field owner -has one {classnames/user} -inverse resourse
 * @field owners -has many {classnames/user} -inverse resourses
 * @field prop varchar(16)
 */
class Test_Models_Acl extends O_Dao_ActiveRecord {

}