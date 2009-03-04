<?php
/**
 * @table comment
 * @field content text -edit wysiwyg \
 * 		-title Текст отзыва \
 * 		-show envelop content-comments \
 * 		-check htmlPurifier \
 * 		-required Отзыв без контента бессмысленнен
 * @field time int -show date
 * @field post -has one Ex_Mdl_Post -inverse comments
 */
class Ex_Mdl_Comment extends O_Dao_ActiveRecord {

}