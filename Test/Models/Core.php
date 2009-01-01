<?php
/**
 * @table core_model
 * @field textfield text -render wysiwyg -title Текстовое поле -signal test
 * @field intfield int -signal
 * @field subs -owns many Test_Models_Sub -inverse core
 * @field manysubs -owns many Test_Models_Sub -inverse cores
 * @field myalias -alias subs.core
 * @field test_alter int default 1
 * @field one_sub -has one Test_Models_Sub -inverse one_core
 * @field core_direct -has one Test_Models_Core -inverse core_inverse
 * @field core_inverse -has one Test_Models_Core -inverse core_direct
 */
class Test_Models_Core extends Dao_ActiveRecord {

}