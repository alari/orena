<?php
/**
 * @field nodes -owns many :NODES_CLASS -inverse root -order-by left_key
 *
 */
abstract class O_Dao_NestedSet_Root extends O_Dao_ActiveRecord {

	/**
	 * Inserts children before all others
	 *
	 * @param O_Dao_NestedSet_Node $node
	 */
	public function injectTop( O_Dao_NestedSet_Node $node )
	{
		$nodeQuery = $node->revoke();
		$_2N = $node->right_key - $node->left_key + 1;
		$this->nodes->query()->field( "left_key", "left_key+" . $_2N, true )->field( "right_key", 
				"right_key+" . $_2N, true )->update();
		$nodeQuery->field( "root", $this->id )->update();
		$node->reload();
	}

	/**
	 * Inserts children after all others
	 *
	 * @param O_Dao_NestedSet_Node $node
	 */
	public function injectBottom( O_Dao_NestedSet_Node $node )
	{
		$nodeQuery = $node->revoke();
		$maxRight = $this->nodes->query()->getFunc( "right_key", "MAX" );
		$nodeQuery->field( "root", $this->id )->field( "left_key", "left_key+" . $maxRight, true )->field( 
				"right_key", "right_key+" . $maxRight, true )->update();
		$node->reload();
	}

}