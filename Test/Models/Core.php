<?php
/**
 * @table core_model
 * @field textfield text
 * @field intfield int
 * @field subs -owns many Test_Models_Sub -inverse core
 * @field manysubs -owns many Test_Models_Sub -inverse cores
 * @field myalias -alias subs.core
 */
class Test_Models_Core extends Dao_Object {

}