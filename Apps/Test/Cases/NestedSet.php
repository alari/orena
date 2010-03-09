<?php
/**
 * Test_Models_Node test case.
 */
class Test_Cases_NestedSet extends PHPUnit_Framework_TestCase {

	/**
	 * @var Test_Models_Root
	 */
	private static $root;
	private static $nodes = Array ();

	public function testFill()
	{

		self::$root = new Test_Models_Root( );
		$this->nodes = Array ();

		$this->fill( 1, 24, 0 );
		$this->fill( 2, 7, 1 );
		$this->fill( 3, 4, 2 );
		$this->fill( 5, 6, 2 );
		$this->fill( 8, 19, 1 );
		$this->fill( 9, 10, 2 );
		$this->fill( 11, 12, 2 );
		$this->fill( 13, 18, 2 );
		$this->fill( 14, 15, 3 );
		$this->fill( 16, 17, 3 );
		$this->fill( 20, 23, 1 );
		$this->fill( 21, 22, 2 );
	}

	private function fill( $left, $right, $level )
	{
		self::$nodes[ $left ] = Array (new Test_Models_Node( self::$root ), $right, $level);
	}

	/**
	 * Returns cached node by its left key
	 *
	 * @param int $left
	 * @return Test_Models_Node
	 */
	private function node( $left )
	{
		return self::$nodes[ $left ][ 0 ];
	}

	public function testConstruct()
	{
		$i = 0;
		foreach (self::$nodes as $arr) {
			$node = $arr[ 0 ];
			$this->assertEquals( 1 + 2 * $i, $node->left_key, "Left key" );
			$this->assertEquals( 2 + 2 * $i, $node->right_key, "Right key" );
			$this->assertEquals( 0, $node->level, "Level" );
			$i++;
		}
	}

	public function testRevoke()
	{
		$node0 = new Test_Models_Node( self::$root );
		$l0 = $node0->left_key;
		$r0 = $node0->right_key;
		$node = new Test_Models_Node( self::$root );
		$node2 = new Test_Models_Node( self::$root );
		$l2 = $node2->left_key;
		$r2 = $node2->right_key;
		$this->assertEquals( self::$root->id, $node[ "root" ], "Root equal" );
		$node->revoke();
		$this->assertLessThan( 0, $node[ "root" ] );
		$this->assertEquals( 1, $node->left_key );
		$this->assertEquals( 2, $node->right_key );
		$this->assertEquals( 0, $node->level );
		$node->delete();
		$node0->reload();
		$this->assertEquals( $l0, $node0->left_key, "Left before" );
		$this->assertEquals( $r0, $node0->right_key, "Right before" );
		$node2->reload();
		$this->assertEquals( $l2 - 2, $node2->left_key, "Left after" );
		$this->assertEquals( $r2 - 2, $node2->right_key, "Right after" );
	}

	public function testInject()
	{
		$root = new Test_Models_Root( );
		$parent = new Test_Models_Node( $root );
		$child = new Test_Models_Node( $root );
		$parent->injectTop( $child );

		$child->reload();
		$parent->reload();

		$this->assertEquals( 0, $parent->level, "Parent level" );
		$this->assertEquals( 1, $child->level, "Child level" );
		$this->assertEquals( 1, $parent->left_key, "Left key of root" );
		$this->assertEquals( 4, $parent->right_key, "Right key of root" );
		$this->assertEquals( 2, $child->left_key, "Left key of sibling" );
		$this->assertEquals( 3, $child->right_key, "Right key of sibling" );
	}

	public function testBuildTree()
	{
		$this->node( 1 )->injectTop( $this->node( 2 ) );
		$this->node( 2 )->injectBottom( $this->node( 5 ) );
		$this->node( 2 )->injectAfter( $this->node( 20 ) );
		$this->node( 20 )->injectBefore( $this->node( 8 ) );
		$this->node( 8 )->injectTop( $this->node( 9 ) );
		$this->node( 9 )->injectAfter( $this->node( 11 ) );
		$this->node( 8 )->injectBottom( $this->node( 13 ) );
		$this->node( 13 )->injectTop( $this->node( 14 ) );
		$this->node( 13 )->injectBottom( $this->node( 16 ) );
		$this->node( 20 )->injectTop( $this->node( 21 ) );
		$this->node( 2 )->injectTop( $this->node( 3 ) );

		foreach (self::$nodes as $left => $arr) {
			list ($node, $right, $level) = $arr;
			$node->reload();
			$this->assertEquals( $left, $node->left_key, "Left key" );
			$this->assertEquals( $right, $node->right_key, "Right key" );
			$this->assertEquals( $level, $node->level, "Level" );
		}
	}

	/**
	 * Cleans up the environment after running a test.
	 */
	protected function tearDown()
	{
		Test_Models_Root::getQuery()->delete();
		// TODO Auto-generated Test_Cases_NestedSet::tearDown()


		$this->Test_Models_Node = null;

		parent::tearDown();
	}

}

