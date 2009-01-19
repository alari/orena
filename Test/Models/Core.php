<?php
/**
 * @table core_model -signal
 * @field textfield text -edit wysiwyg \
 * 		-title Текстовое поле -signal test \
 * 		-show area
 * @field intfield int -signal -show simple -loop simple
 * @field subs -owns many Test_Models_Sub -inverse core
 * @field manysubs -owns many Test_Models_Sub -inverse cores
 * @field myalias -alias subs.core
 * @field test_alter int default 1 -edit Test_Fragments_CustomEditor::render
 * @field one_sub -has one Test_Models_Sub -inverse one_core
 * @field core_direct -has one Test_Models_Core -inverse core_inverse
 * @field core_inverse -has one Test_Models_Core -inverse core_direct
 */
class Test_Models_Core extends Dao_ActiveRecord {

}