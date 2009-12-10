<?php
class O_Dao_Field_ToMany extends O_Dao_Field_Bases implements O_Dao_Field_iFace, O_Dao_Field_iRelation {
	/**
	 * Field info instance for the field
	 *
	 * @var O_Dao_FieldInfo
	 */
	private $fieldInfo;
	/**
	 * Database's field name
	 *
	 * @var string
	 */
	private $name;
	
	/**
	 * Owns it its targets or not
	 *
	 * @var bool
	 */
	private $owns = 0;
	/**
	 * Not parsed classname of relation target
	 *
	 * @var string
	 */
	private $targetBase;
	/**
	 * Relation target's classname
	 *
	 * @var string
	 */
	private $target;
	
	/**
	 * Inverse fieldname
	 *
	 * @var string
	 */
	private $inverse;
	/**
	 * Inverse fieldinfo
	 *
	 * @var O_Dao_FieldInfo
	 */
	private $inverseField;
	
	/**
	 * Cached relation objects, like O_Dao_Relation_Base
	 *
	 * @var O_Dao_Relation_Base[]
	 */
	private $relations = Array ();
	
	/**
	 * Current dao classname -- cached in property
	 *
	 * @var string
	 */
	private $class;
	/**
	 * Dao -order-by key value -- cached in property
	 *
	 * @var string
	 */
	private $orderBy;

	public function __construct( O_Dao_FieldInfo $fieldInfo, $name, $owns, $target )
	{
		$this->fieldInfo = $fieldInfo;
		$this->name = $name;
		$this->owns = $owns;
		$this->class = $fieldInfo->getClass();
		$this->orderBy = $fieldInfo->getParam( "order-by" );
		$this->targetBase = $target;
		$this->target = $this->getTargetByBase( $this->targetBase );
		$this->inverse = $fieldInfo->getParam( "inverse" );
		if (!$this->inverse)
			throw new O_Ex_Config( "Inverse field must be specified for whatever-to-many relations." );
	
	}

	/**
	 * Reloads field's cache for the object
	 *
	 * @param int $obj_id
	 */
	public function reload( $obj_id )
	{
		O_Registry::startProfiler(__METHOD__."|".$obj_id);
		if (isset( $this->relations[ $obj_id ] ))
			unset( $this->relations[ $obj_id ] );
		O_Registry::stopProfiler(__METHOD__."|".$obj_id);
	}

	/**
	 * Returns relation target classname
	 *
	 * @return string
	 */
	public function getTargetClass()
	{
		return $this->target;
	}

	/**
	 * Sets the value with all tests provided
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @throws Exception
	 * @return bool
	 * @access private
	 */
	public function setValue( O_Dao_ActiveRecord $obj, $fieldValue, $fieldExists )
	{
		if ($fieldValue instanceof O_Dao_Query)
			$fieldValue = $fieldValue->getAll();
		if (!$fieldValue)
			$fieldValue = Array ();
		if (!is_array( $fieldValue ))
			throw new O_Ex_WrongArgument( "Cannot assign non-array/query to base-to-many relation." );
		$relation = $this->getRelation( $obj->id );
		foreach ($relation as $_el) {
			if (!array_key_exists( $_el->id, $fieldValue ))
				$relation->remove( $_el, $this->owns );
		}
		foreach ($fieldValue as $v) {
			$relation[] = $v;
		}
		return $relation;
	}

	/**
	 * Returns the field value, even if it's a relation or aliased query
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @return mixed
	 * @access private
	 */
	public function getValue( O_Dao_ActiveRecord $obj, $fieldValue, $fieldExists )
	{
		return $this->getRelation( $obj->id );
	}

	/**
	 * Sets the class
	 *
	 * @see O_Dao_TableInfo::__construct()
	 * @access package
	 * @param string $class
	 */
	public function setFieldInfo( O_Dao_FieldInfo $fieldInfo )
	{
		$this->fieldInfo = $fieldInfo;
		$this->class = $fieldInfo->getClass();
		$this->orderBy = $fieldInfo->getParam( "order-by" );
		if ($this->targetBase[ 0 ] == ":") {
			$const = $this->class . ":" . $this->targetBase;
			$this->target = defined( $const ) ? constant( $const ) : null;
			$this->inverseField = null;
			$this->relations = Array ();
		}
	}	

	/**
	 * FieldInfo of reverse field
	 *
	 * @return O_Dao_FieldInfo
	 * @access private
	 */
	public function getInverse()
	{
		if (!$this->inverseField)
			$this->inverseField = O_Dao_TableInfo::get( $this->target )->getFieldInfo( $this->inverse );
		return $this->inverseField;
	}

	/**
	 * Create relation with other objects.
	 *
	 * @param int $obj_id
	 * @return O_Dao_Relation_BaseToMany
	 * @access private
	 */
	public function getRelation( $obj_id )
	{
		if (!isset( $this->relation[ $obj_id ] ) || !$this->relation[ $obj_id ] instanceof O_Dao_Relation_BaseToMany) {
			if ($this->getInverse()->isRelationMany()) {
				// Relation with anchors table (many-to-many or one-to-many without inverse)
				$this->relation[ $obj_id ] = new O_Dao_Relation_ManyToMany( $this->target, $this->inverse, $obj_id, $this->class, $this->name, $this->orderBy );
			} else {
				// Has many with inverse
				$this->relation[ $obj_id ] = new O_Dao_Relation_OneToMany( $this->target, $this->inverse, $obj_id, $this->class, $this->name, $this->orderBy );
			}
		}
		return clone $this->relation[ $obj_id ];
	}

	/**
	 * No special actions should be done on atomic field deletion
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @access private
	 */
	public function deleteThis( O_Dao_ActiveRecord $obj, $fieldValue = null )
	{
		$this->reload( $obj->id );
		$this->getRelation( $obj->id )->removeAll( $this->owns );
		$this->reload( $obj->id );
	}

}