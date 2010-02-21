<?php
/**
 * @field root -has one :ROOT_CLASS -inverse nodes
 * @field left_key int DEFAULT 1
 * @field right_key int DEFAULT 2
 * @field level int DEFAULT 0
 *
 * @index level
 * @index left_key
 * @index right_key
 * @index left_key,level
 * @index left_key,right_key
 */
abstract class O_Dao_NestedSet_Node extends O_Dao_ActiveRecord implements O_Dao_NestedSet_iNode  {

	/**
	 * Creates new node, injects it into the root, on the bottom
	 *
	 * @param O_Dao_NestedSet_iRoot $root
	 */
	public function __construct( O_Dao_NestedSet_iRoot $root )
	{
		$this->root = $root;
		$left_key = $root->nodes->query()->getFunc( "right_key", "MAX" ) + 1;
		$this->left_key = $left_key;
		$this->right_key = $left_key + 1;
		$this->level = 0;
		parent::__construct();
	}

	/**
	 * Returns query containing all nodes within root of current node
	 *
	 * @return O_Dao_Query
	 */
	public function getRootNodes()
	{
		if ($this[ "root" ] < 0)
			$this->reload();
		if ($this[ "root" ] > 0) {
			return $this->root->nodes->query();
		} else {
			return $this->getRootsQuery()->test( "root", $this[ "root" ] );
		}
	}

	/**
	 * Returns query with roots
	 *
	 * @return O_Dao_Query
	 */
	public function getRootsQuery()
	{
		return O_Dao_Query::get(
				O_Dao_TableInfo::get( get_class( $this ) )->getFieldInfo( "root" )->getRelationTarget() );
	}

	/**
	 * Returns path for this node from root, excluding current node.
	 *
	 * @return O_Dao_Query
	 */
	public function getPath()
	{
		return $this->getRootNodes()->where( "left_key<? AND right_key>?", $this->left_key,
				$this->right_key );
	}

	/**
	 * Returns branch with this node from the root.
	 *
	 * @return O_Dao_Query
	 */
	public function getBranch()
	{
		return $this->getRootNodes()->where( "left_key<? AND right_key>?", $this->right_key,
				$this->left_key );
	}

	/**
	 * Returns parent node, if it exists
	 *
	 * @return O_Dao_NestedSet_iNode
	 */
	public function getParent()
	{
		if (!$this->level)
			return false;
		return $this->getPath()->test( "level", $this->level - 1 )->getOne();
	}

	/**
	 * Returns all children for given depth
	 *
	 * @param int $depth
	 * @return O_Dao_Query
	 */
	public function getChilds( $depth = 0 )
	{
		$q = $this->getRootNodes()->where( "left_key>? AND right_key<?", $this->left_key,
				$this->right_key );

		if ($depth > 0)
			$q->where( "level<=?", $this->level + $depth );
		return $q;
	}

	/**
	 * Returns all leaves of this node's branch
	 *
	 * @return O_Dao_Query
	 */
	public function getLeaves()
	{
		return $this->getBranch()->where( "right_key-left_key=1" );
	}

	/**
	 * Counts all childs for given depth
	 *
	 * @param int $depth
	 * @return int
	 */
	public function countChilds( $depth = 0 )
	{
		if (!$depth)
			return ($this->right_key - $this->left_key - 1) / 2;
		return $this->getChilds( $depth )->getFunc( "left_key" );
	}

	/**
	 * Deletes this node with childs (if argument is set to true)
	 *
	 * @param bool $processTree
	 */
	public function delete( $processTree = false )
	{
		if ($processTree) {
			// delete childs
			$this->getChilds()->delete();
			// move keys
			$_2N = $this->right_key - $this->left_key + 1;
			$this->getRootNodes()->field( "right_key",
					"IF(right_key>$this->left_key,right_key-$_2N,right_key)", true )->field(
					"left_key", "IF(left_key>$this->left_key,left_key-$_2N,left_key)", true )->update();
		}
		// delete this
		parent::delete();
	}

	/**
	 * Deletes childs keeping current node in tree
	 *
	 */
	public function deleteChilds()
	{
		$this->getChilds()->delete();
		$_2N = $this->right_key - $this->left_key - 1;
		$this->getRootNodes()->field( "right_key",
				"IF(right_key>$this->left_key,right_key-$_2N,right_key)", true )->field(
				"left_key", "IF(left_key>$this->left_key,left_key-$_2N,left_key)", true )->update();

	}

	/**
	 * Sets unique negative root, updates keys from 1, removes from old tree
	 *
	 * @return O_Dao_Query with this node and all its childs
	 */
	public function revoke()
	{
		// Cache query with old root nodes
		$this->reload();
		$oldRootNodes = $this->root ? $this->getRootNodes() : null;
		$_2N = $this->right_key - $this->left_key + 1;
		$oldLeft = $this->left_key;
		$oldRight = $this->right_key;
		$oldLevel = $this->level;

		// Set random negative root
		do {
			$rand_root = -rand( 1, 1 << 16 );
		} while (O_Dao_Query::get( get_class( $this ) )->test( "root", $rand_root )->getFunc(
				"left_key" ));

		if ($oldRight - $oldLeft > 1) {
			$this->getChilds()->addOr()->test( "id", $this->id )->field( "root", $rand_root )->field(
					"left_key", "left_key-" . ($oldLeft + 1), true )->field( "right_key",
					"right_key-" . ($oldRight + 1), true )->field( "level",
					"level-" . $oldLevel, true )->update();
		} else {
			$this->setField( "root", $rand_root );
			$this->left_key = 1;
			$this->right_key = 2;
			$this->level = 0;
			$this->save();
		}

		// Move keys in old root
		if ($oldRootNodes) {
			$oldRootNodes->field( "right_key", "IF(left_key>$oldLeft,right_key-$_2N,right_key)",
					true )->field( "left_key", "IF(left_key>$oldLeft,left_key-$_2N,left_key)",
					true )->update();
		}
		$this->reload();

		return O_Dao_Query::get( get_class( $this ) )->test( "root", $rand_root );
	}

	/**
	 * Updates keys for injection
	 *
	 * @param O_Dao_Query $childsQuery
	 * @param int $keyOffset
	 * @param int $levelOffset
	 * @access private
	 */
	public function processInjection( O_Dao_Query $childsQuery, $keyOffset, $levelOffset )
	{
		$childsQuery->field( "level", "level+" . $levelOffset, true )->field( "left_key",
				"left_key+" . $keyOffset, true )->field( "right_key", "right_key+" . $keyOffset,
				true )->field( "root", $this[ "root" ] )->update();
	}

	/**
	 * Injects child node before this, on the same level
	 *
	 * @param O_Dao_NestedSet_iNode $child
	 */
	public function injectBefore( O_Dao_NestedSet_iNode $child )
	{
		O_Db_Manager::getConnection()->beginTransaction();
		// Remove child from its tree
		$childQuery = $child->revoke();
		$this->reload();

		$parentLeft = $this->left_key - 1;

		// Update keys in current tree
		$_2N = $child->right_key - $child->left_key + 1;
		$this->getRootNodes()->field( "left_key",
				"IF(left_key>=$this->left_key,left_key+$_2N,left_key)", true )->field(
				"right_key", "IF(right_key>$this->left_key,right_key+$_2N,right_key)", true )->update();

		// Set root, level and keys for childs
		$this->processInjection( $childQuery, $parentLeft, $this->level );
		$child->reload();
		$this->reload();
		O_Db_Manager::getConnection()->commit();

	}

	/**
	 * Injects child node after this, on the same level
	 *
	 * @param O_Dao_NestedSet_iNode $child
	 */
	public function injectAfter( O_Dao_NestedSet_iNode $child )
	{
		O_Db_Manager::getConnection()->beginTransaction();
		// Remove child from its tree
		$childQuery = $child->revoke();
		$this->reload();

		$parentLeft = $this->right_key;

		// Update keys in current tree
		$_2N = $child->right_key - $child->left_key + 1;
		$this->getRootNodes()->field( "left_key",
				"IF(left_key>$this->right_key,left_key+$_2N,left_key)", true )->field(
				"right_key", "IF(right_key>$this->right_key,right_key+$_2N,right_key)", true )->update();

		// Set root, level and keys for childs
		$this->processInjection( $childQuery, $parentLeft, $this->level );
		O_Db_Manager::getConnection()->commit();
	}

	/**
	 * Injects child node inside this, on the top
	 *
	 * @param O_Dao_NestedSet_iNode $child
	 */
	public function injectTop( O_Dao_NestedSet_iNode $child )
	{
		O_Db_Manager::getConnection()->beginTransaction();
		// Remove child from its tree
		$childQuery = $child->revoke();
		$this->reload();

		$parentLeft = $this->left_key;

		// Update keys in current tree
		$_2N = $child->right_key - $child->left_key + 1;
		$this->getRootNodes()->field( "left_key",
				"IF(left_key>$this->left_key,left_key+$_2N,left_key)", true )->field(
				"right_key", "IF(right_key>$this->left_key,right_key+$_2N,right_key)", true )->update();

		// Set root, level and keys for childs
		$this->processInjection( $childQuery, $parentLeft, $this->level + 1 );
		O_Db_Manager::getConnection()->commit();
	}

	/**
	 * Injects child node inside this, on the bottom
	 *
	 * @param O_Dao_NestedSet_iNode $child
	 */
	public function injectBottom( O_Dao_NestedSet_iNode $child )
	{
		O_Db_Manager::getConnection()->beginTransaction();
		// Remove child from its tree
		$childQuery = $child->revoke();
		$this->reload();

		$parentLeft = $this->right_key - 1;

		// Update keys in current tree
		$_2N = $child->right_key - $child->left_key + 1;
		$this->getRootNodes()->field( "left_key",
				"IF(left_key>$this->right_key,left_key+$_2N,left_key)", true )->field(
				"right_key", "IF(right_key>=$this->right_key,right_key+$_2N,right_key)", true )->update();

		// Set root, level and keys for childs
		$this->processInjection( $childQuery, $parentLeft, $this->level + 1 );
		O_Db_Manager::getConnection()->commit();
	}

}