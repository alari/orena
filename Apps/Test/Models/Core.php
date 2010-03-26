<?php
/**
 * @Table(core_model, signal=1, show:envelop=simple, show-loop:envelop="envelop loop")
 * @Field(textfield, text, edit=wysiwyg, title="Текстовое поле", signal="test", show-def="wysiwyg", check="htmlPurifier", edit-ajax="area", required="Введи текст!")
 * @Field(intfield, int, signal=1, show=simple, loop=simple, edit-ajax=1, required="Число!")
 * @Field(subs, owns="many {test/sub_class}", inverse=core)
 * @Field(manysubs, owns="many Test_Models_Sub", inverse=cores, signal=my)
 * @field myalias -alias subs.core
 * @field test_alter int default 1 -e-dit Test_Fragments_CustomEditor::render
 * @field one_sub -has one Test_Models_Sub -inverse one_core
 * @field core_direct -has one {test/core_class} -inverse core_inverse
 * @field core_inverse -has one Test_Models_Core -inverse core_direct
 * @field my_enum TINYINT -enum first; second; third
 * @field my_enum_keys ENUM('a','b','c') -enum a:d; b:e; c:f
 */
class Test_Models_Core extends O_Dao_ActiveRecord {

}