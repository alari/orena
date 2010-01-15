<?php
/**
 * Class to implement both Root and Node functionality
 * 
 * @author Dmitry Kurinskiy
 * 
 * @field nodes -owns many :NODES_CLASS -inverse root -order-by left_key
 */
abstract class O_Dao_NestedSet_Both extends O_Dao_NestedSet_Node implements O_Dao_NestedSet_iRoot {
	/**
	 * Inserts child before all others
	 *
	 * @param O_Dao_NestedSet_iNode $node
	 */
	public function rootInjectTop( O_Dao_NestedSet_iNode $node )
	{
		return O_Dao_NestedSet_Root::_delegateInjectTop($this, $node);
	}

	/**
	 * Inserts child after all others
	 *
	 * @param O_Dao_NestedSet_iNode $node
	 */
	public function rootInjectBottom( O_Dao_NestedSet_iNode $node )
	{
		return O_Dao_NestedSet_Root::_delegateInjectBottom($this, $node);
	}
}