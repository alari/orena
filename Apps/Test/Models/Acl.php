<?php
/**
 * @Table(test_acl_resourse)
 * @Field(owner, has="one {classnames/user}", inverse=resourse)
 * @Field(owners, has="many {classnames/user}", inverse=resourses)
 * @Field(prop, "varchar(16)")
 */
class Test_Models_Acl extends O_Dao_ActiveRecord {

}

/**
 * @Table(test_acl_resourse)
 */
class Test_Models_Acl_Data {
	/**
	 * @ToOne(has="{classnames/user}", inverse=resourse)
	 */
	private $owner;
	/**
	 * @ToMany(has="{classnames/user}", inverse=resourses)
	 */
	private $owners;
	/**
	 * @Atomic("varchar(16)")
	 */
	private $prop;
}