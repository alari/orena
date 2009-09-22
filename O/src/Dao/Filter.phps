<?php
/**
 * Really not suggested yet how to build automatic query filters
 *
 * Please do not use this.
 *
 * @deprecated
 * @author Dmitry Kourinski
 */
class O_Dao_Filter {
	/**
	 * Query to process
	 *
	 * @var O_Dao_Query
	 */
	protected $query;
	protected $fields = Array ();
	protected $processed;
	
	const TYPE_PLAIN = 0;
	const TYPE_INTDATE = 1;
	const TYPE_RELATION = 2;

	public function __construct( O_Dao_Query $query )
	{
		$this->query = $query;
	}

	public function addField( $fieldName, $title )
	{
		$this->fields[ $fieldName ] = array ("type" => self::TYPE_PLAIN, "title" => $title);
	}

	public function addIntDate( $fieldName, $title, $interval = false )
	{
		$this->fields[ $fieldName ] = array ("type" => self::TYPE_INTDATE, "title" => $title, 
											"asInterval" => $interval);
	}

	public function addRelation( $fieldName, $title, O_Dao_Query $query, $showField = "id", $size = 1, $class = null )
	{
		$this->fields[ $fieldName ] = array ("type" => self::TYPE_RELATION, "title" => $title, 
											"query" => $query, "showField" => $showField, 
											"size" => $size, "class" => $class);
	}

	public function process()
	{
		if ($this->processed)
			return;
		foreach ($this->fields as $name => &$params) {
			if ($params[ "type" ] == self::TYPE_PLAIN) {
				$params[ "value" ] = O_Registry::get( "app/env/params/$name" );
				if ($params[ "value" ])
					$this->query->test( $name, "%" . $params[ "value" ] . "%", 
							O_Dao_Query::LIKE );
			} elseif ($params[ "type" ] == self::TYPE_INTDATE) {
				if ($params[ "asInterval" ]) {
					$left = O_Registry::get( "app/env/params/$name.left" );
					if ($left) {
						$_left = strtotime( $left );
						if ($_left) {
							$this->query->test( $name, $_left, O_Dao_Query::GT_EQ );
							$params[ "left" ] = $left;
						}
					}
					$right = O_Registry::get( "app/env/params/$name.right" );
					if ($right) {
						$_right = strtotime( $right );
						if ($_right) {
							$this->query->test( $name, $_right, O_Dao_Query::LT_EQ );
							$params[ "right" ] = $right;
						}
					}
				} else {
					$value = O_Registry::get( "app/env/params/$name" );
					if ($value) {
						$_value = strtotime( $value );
						if ($_value) {
							$this->query->test( $name, $_value, O_Dao_Query::GT_EQ )->test( 
									$name, $_value + 86400, O_Dao_Query::LT );
							$params[ "value" ] = $value;
						}
					}
				}
			} elseif ($params[ "type" ] == self::TYPE_RELATION) {
				$fieldName = strpos( $name, "." ) ? substr( $name, strpos( $name, "." ) ) : $name;
				$tableInfo = O_Dao_TableInfo::get( 
						$params[ "class" ] ? $params[ "class" ] : $this->query->getClass() );
				$fieldInfo = $tableInfo->getFieldInfo( $fieldName );
				$params[ "value" ] = O_Registry::get( "app/env/params/$name" );
				if ($fieldInfo->isRelationOne()) {
					$this->query->test( $name, $params[ "value" ] );
				} /* elseif($fieldInfo->isRelationMany() && $fieldInfo->getInverse()->isRelationOne()) {
					$tbl = O_Dao_TableInfo::get($fieldInfo->getRelationTarget())->getTableName();
					$als = $tbl."_filter".mt_rand(0,64);
					$nm = $fieldInfo->getInverse()->getName();
					$tb0 = $tableInfo->getTableName();
					$this->query->join($tbl." ".$als, "$als.$nm=$tb0.id")->test($tbl.".id", $params["value"])->test("$als.id", $params["query"]->clearFields()->field("id"));
					// TODO: make this clear
				} else {
					// many-to-many
				}*/
			}
		}
	}

	public function show( O_Html_Layout $layout )
	{
		;
	}

	public function getPaginator( $url_callback, $perpage = null, $page_registry = "paginator/page", array $orders = array(), $order_registry = "paginator/order" )
	{
		return new O_Dao_Paginator( $this->query, $url_callback, $perpage, $page_registry, $orders, 
				$order_registry );
	}

}