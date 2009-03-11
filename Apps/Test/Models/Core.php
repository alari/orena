<?php
/**
 * @table core_model -signal -show:callback simple -show-loop:callback envelop loop
 * @field textfield text -edit wysiwyg \
 * 		-title Текстовое поле -signal test \
 * 		-show-def wysiwyg \
 * 		-check htmlPurifier \
 * 		-edit-ajax area -required Введи текст!
 * @field intfield int -signal -show simple -loop simple -edit-ajax -required Число!
 * @field subs -owns many {test/sub_class} -inverse core
 * @field manysubs -owns many Test_Models_Sub -inverse cores
 * @field myalias -alias subs.core
 * @field test_alter int default 1 -e-dit Test_Fragments_CustomEditor::render
 * @field one_sub -has one Test_Models_Sub -inverse one_core
 * @field core_direct -has one {test/core_class} -inverse core_inverse
 * @field core_inverse -has one Test_Models_Core -inverse core_direct
 */
class Test_Models_Core extends O_Dao_ActiveRecord {

}