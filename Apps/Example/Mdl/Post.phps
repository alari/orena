<?php
/**
 * @table post
 * @field title tinytext -edit simple \
 * 		-show-loop linkInContainer h1 \
 * 		-show-def container h1 \
 * 		-required Заголовок обязателен
 * @field content text -edit wysiwyg \
 * 		-title Текст новости \
 * 		-show envelop content-news \
 * 		-required Нельзя запостить без текста
 * 		-check htmlPurifier
 * @field time int -show date
 * @field comments -owns many Ex_Mdl_Comment -inverse post \
 * 		-show-loop count Комментариев \
 * 		-show-def loop \
 * 		-title Комментарии
 */
class Ex_Mdl_Post extends O_Dao_ActiveRecord {

	public function url()
	{
		return str_replace( "//", "/", O_Registry::get( "app/env/base_url" ) . "/post/" . $this->id );
	}
}