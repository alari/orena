<?php
/**
 * @table sub_model
 * @field core -has one Test_Models_Core -inverse subs
 * @field cores -has many Test_Models_Core -inverse manysubs
 */
class Test_Models_Sub extends Dao_Object {

}