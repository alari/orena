<?php
abstract class Dao_Relation_BaseToMany extends Dao_Query implements Countable {

	abstract public function remove( Dao_ActiveRecord $object, $delete = false );

	abstract public function removeAll( $delete = false );

	abstract public function query();

	public function count()
	{
		return count( $this->getAll() );
	}

	abstract public function reload();
}