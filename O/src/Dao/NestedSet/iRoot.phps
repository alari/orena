<?php
/**
 * @field nodes -owns many :NODES_CLASS -inverse root -order-by left_key
 *
 */
interface O_Dao_NestedSet_iRoot {

	/**
	 * Inserts child before all others
	 *
	 * @param O_Dao_NestedSet_iNode $node
	 */
	public function rootInjectTop( O_Dao_NestedSet_iNode $node );

	/**
	 * Inserts child after all others
	 *
	 * @param O_Dao_NestedSet_iNode $node
	 */
	public function rootInjectBottom( O_Dao_NestedSet_iNode $node );

}