<?php
class O_Dao_Field_Alias extends O_Dao_Field_Bases implements O_Dao_Field_iFace {
	/**
	 * Field info instance for the field
	 *
	 * @var O_Dao_FieldInfo
	 */
	private $fieldInfo;
	/**
	 * String map for alias field
	 * e.g. fieldA.fieldB means relation field A which targets object whith relation field B
	 * Query with B will be returned
	 *
	 * @var string
	 */
	private $alias;
	/**
	 * Field to test to get concrete alias instance
	 *
	 * @var string
	 */
	private $testField;
	/**
	 * Query for alias
	 *
	 * @var O_Dao_Query
	 */
	private $query;

	/**
	 * Cached queries for concrete objects
	 *
	 * @var O_Dao_Query[]
	 */
	private $objQueries = Array();
	
	/**
	 * Constructor
	 *
	 * @param O_Dao_FieldInfo $fieldInfo
	 * @param string $alias
	 */
	public function __construct( O_Dao_FieldInfo $fieldInfo, $alias )
	{
		$this->fieldInfo = $fieldInfo;
		$this->alias = $alias;
	}

	/**
	 * @throws O_Ex_Critical
	 * @access private
	 */
	public function setValue( O_Dao_ActiveRecord $obj, $fieldValue, $fieldExists )
	{
		throw new O_Ex_Critical( "Cannot assign to aliases." );
	}

	/**
	 * Returns built and cached aliased query
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @return O_Dao_Query
	 * @access private
	 */
	public function getValue( O_Dao_ActiveRecord $obj, $fieldValue, $fieldExists )
	{
		if (!$this->query) {
			list ($name, $subreq) = explode( ".", $this->alias, 2 );
			$this->testField = O_Dao_TableInfo::get( $this->fieldInfo->getClass() )->getFieldInfo( 
					$name )->prepareMappedQuery( $this->query, $subreq );
			
			if ($this->fieldInfo->getParam( "where" ) && $this->query instanceof O_Dao_Query) {
				$this->query->where( $this->fieldInfo->getParam( "where" ) );
			}
			if ($this->fieldInfo->getParam( "order-by" ) && $this->query instanceof O_Dao_Query) {
				$this->query->orderBy( $this->fieldInfo->getParam( "order-by" ) );
			}
		}
		if (!$this->query instanceof O_Dao_Query) {
			throw new O_Ex_Critical( "Wrong mapped query is produced by $name.$subreq map." );
		}
		if(!$fieldValue) {
			$fieldValue = $obj->id;
		}
		if(!isset($this->objQueries[$fieldValue])) {
			$this->objQueries[$fieldValue] = clone $this->query;
			$this->objQueries[$fieldValue]->test($this->testField, $fieldValue);
		}
		return $this->objQueries[$fieldValue];
	}

	/**
	 * Sets the field info
	 *
	 * @see O_Dao_TableInfo::__construct()
	 * @access package
	 * @param string $class
	 */
	public function setFieldInfo( O_Dao_FieldInfo $fieldInfo )
	{
		$this->fieldInfo = $fieldInfo;
	}

}