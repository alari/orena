<?php
/**
 * @table sub_model -signal
 * @field core -has one Test_Models_Core -inverse subs
 * @field cores -has many Test_Models_Core -inverse manysubs
 * @field testsignal tinytext -signal test
 * @field one_core -has one Test_Models_Core -inverse one_sub
 */
class Test_Models_Sub extends Dao_ActiveRecord {

}