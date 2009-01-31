<?php
/**
 * Abstract class to describe standard jobs with Dao_ActiveRecord-to-many relations.
 *
 * @see Dao_FieldInfo
 * @see Dao_Relation_ManyToMany
 * @see Dao_Relation_OneToMany
 *
 * @author Dmitry Kourinski
 */
abstract class Dao_Relation_BaseToMany extends Dao_Query implements Countable {
	/**
	 * Removes linked object from relation.
	 *
	 * @param Dao_ActiveRecord $object
	 * @param bool $delete If set to true, linked object will be deleted
	 */
	abstract public function remove( Dao_ActiveRecord $object, $delete = false );

	/**
	 * Removes all linked objects from relation
	 *
	 * @param bool $delete If set to true, all linked objects will be deleted
	 */
	abstract public function removeAll( $delete = false );

	/**
	 * Returns query with all linked objects.
	 *
	 * @return Dao_Query
	 */
	abstract public function query();

	/**
	 * Implementation of Countable interface
	 *
	 * @return int
	 */
	public function count()
	{
		return count( $this->getAll() );
	}

	/**
	 * Reloads the relation, renewes it if it was cached.
	 *
	 */
	abstract public function reload();
}