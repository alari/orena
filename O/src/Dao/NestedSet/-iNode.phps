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
interface O_Dao_NestedSet_iNode {

	/**
	 * Creates new node, injects it into the root, on the bottom
	 *
	 * @param O_Dao_NestedSet_iRoot $root
	 */
	public function __construct( O_Dao_NestedSet_iRoot $root );

	/**
	 * Returns query containing all nodes within root of current node
	 *
	 * @return O_Dao_Query
	 */
	public function getRootNodes();

	/**
	 * Returns query with roots
	 *
	 * @return O_Dao_Query
	 */
	public function getRootsQuery();

	/**
	 * Returns path for this node from root, excluding current node.
	 *
	 * @return O_Dao_Query
	 */
	public function getPath();

	/**
	 * Returns branch with this node from the root.
	 *
	 * @return O_Dao_Query
	 */
	public function getBranch();

	/**
	 * Returns parent node, if it exists
	 *
	 * @return O_Dao_NestedSet_iNode
	 */
	public function getParent();

	/**
	 * Returns all children for given depth
	 *
	 * @param int $depth
	 * @return O_Dao_Query
	 */
	public function getChilds( $depth = 0 );

	/**
	 * Returns all leaves of this node's branch
	 *
	 * @return O_Dao_Query
	 */
	public function getLeaves();

	/**
	 * Counts all childs for given depth
	 *
	 * @param int $depth
	 * @return int
	 */
	public function countChilds( $depth = 0 );

	/**
	 * Deletes this node with childs (if argument is set to true)
	 *
	 * @param bool $processTree
	 */
	public function delete( $processTree = false );

	/**
	 * Deletes childs keeping current node in tree
	 *
	 */
	public function deleteChilds();

	/**
	 * Sets unique negative root, updates keys from 1, removes from old tree
	 *
	 * @return O_Dao_Query with this node and all its childs
	 */
	public function revoke();

	/**
	 * Updates keys for injection
	 *
	 * @param O_Dao_Query $childsQuery
	 * @param int $keyOffset
	 * @param int $levelOffset
	 * @access private
	 */
	public function processInjection( O_Dao_Query $childsQuery, $keyOffset, $levelOffset );

	/**
	 * Injects child node before this, on the same level
	 *
	 * @param O_Dao_NestedSet_iNode $child
	 */
	public function injectBefore( O_Dao_NestedSet_iNode $child );

	/**
	 * Injects child node after this, on the same level
	 *
	 * @param mr_abstract_node $child
	 */
	public function injectAfter( O_Dao_NestedSet_iNode $child );

	/**
	 * Injects child node inside this, on the top
	 *
	 * @param O_Dao_NestedSet_iNode $child
	 */
	public function injectTop( O_Dao_NestedSet_iNode $child );

	/**
	 * Injects child node inside this, on the bottom
	 *
	 * @param O_Dao_NestedSet_iNode $child
	 */
	public function injectBottom( O_Dao_NestedSet_iNode $child );

}