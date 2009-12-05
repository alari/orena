<?php
/**
 * @field nodes -owns many :NODES_CLASS -inverse root -order-by left_key
 *
 */
abstract class O_Dao_NestedSet_Root extends O_Dao_ActiveRecord implements O_Dao_NestedSet_iRoot {

	/**
	 * Inserts child before all others
	 *
	 * @param O_Dao_NestedSet_iNode $node
	 */
	public function rootInjectTop( O_Dao_NestedSet_iNode $node )
	{
		return self::_delegateInjectTop($this, $node);
	}

	/**
	 * Inserts child after all others
	 *
	 * @param O_Dao_NestedSet_iNode $node
	 */
	public function rootInjectBottom( O_Dao_NestedSet_iNode $node )
	{
		return self::_delegateInjectBottom($this, $node);
	}

	static public function _delegateInjectBottom(O_Dao_NestedSet_iRoot $root, O_Dao_NestedSet_iNode $node ) {
		$nodeQuery = $node->revoke();
		$maxRight = $root->nodes->query()->getFunc( "right_key", "MAX" );
		$nodeQuery->field( "root", $root->id )->field( "left_key", "left_key+" . $maxRight, true )->field( 
				"right_key", "right_key+" . $maxRight, true )->update();
		$node->reload();
	}
	
	static public function _delegateInjectTop(O_Dao_NestedSet_iRoot $root, O_Dao_NestedSet_iNode $node) {
		$nodeQuery = $node->revoke();
		$_2N = $node->right_key - $node->left_key + 1;
		$root->nodes->query()->field( "left_key", "left_key+" . $_2N, true )->field( "right_key", 
				"right_key+" . $_2N, true )->update();
		$nodeQuery->field( "root", $root->id )->update();
		$node->reload();
	}
	
	
}