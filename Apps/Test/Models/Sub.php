<?php
/**
 * @table sub_model -signal
 * @field core -has one {test/core_class} -inverse subs
 * @field cores -has many Test_Models_Core -inverse manysubs
 * @field testsignal tinytext -signal test
 * @field one_core -has one {test/core_class} -inverse one_sub
 */
class Test_Models_Sub extends O_Dao_ActiveRecord {

}