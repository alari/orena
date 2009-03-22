<?php
/**
 * @table test_user
 * @field resourse -has one Test_Models_Acl -inverse owner
 * @field resourses -has many Test_Models_Acl -inverse owners
 */
class Test_Models_User extends O_Acl_User {

}