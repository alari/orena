<?php
/**
 * Handles one persistent field of O_Dao_ActiveRecord.
 *
 * Supported configuration:
 *
 * Atomic field:
 * @field fieldname sql type
 *
 * Base to one or base to many relation:
 * @field fieldname -(has|owns) (one|many) Target_Classname -inverse other_fieldname [-order-by field]
 *
 * "owns" key means "on delete cascade". Inverse field must be specified for any base-to-many relation.
 * It will be one to many or many to many due to inverse field description.
 * Target_Classname could be in {some/registry/key} format. If it's so, target classname will be get from
 * "app/some/registry/key" registry.
 * Also Target_Classname will be get from class constant if it's in ":CONST_NAME" format.
 *
 * Base to one relations returns target object as value. Base to many returns instance of
 * @see O_Dao_Relation_OneToMany
 * or
 * @see O_Dao_Relation_ManyToMany
 *
 * For base to one relations you can use -preload key to preload all related objects via one request when selecting
 * many objects with O_Dao_Query.
 *
 * To add an object, use [] operator, e.g. $record->objs[] = new obj.
 * To remove an object, use remove() or removeAll() method:
 * @example $record->remove($obj) to delete relation with $obj
 * @example $record->removeAll() to delete relations with all objects
 * Those methods gets one additional bool parameter. If it's set to true, relative object will be deleted.
 *
 * O_Dao_FieldInfo also supports special "alias" fieldtype:
 * @field fieldname -alias relationfieldname.other_relation[.relation[...]] -where condition -order-by field
 * It returns O_Dao_Query with objects linked with current ActiveRecord with several steps.
 * E.g. A linked with B, B linked with C, so C is linked with A and this relation is available by calling
 * @example $A->{"B.C"} (this is named "mapped query" and handled by "B" fieldinfo object) or with
 * @field BC -alias B.C,
 * @example $A->BC
 * "-where" key adds an additional condition to result SQL string by using
 * @see O_Db_Query::where()
 *
 * Even thought, you can create alias for a number of other fields, relative to one object of different classes each.
 * @field fieldname -one-of field1; field2[; field3; ...]
 * If you're getting such alias value, the first existent field will be returned.
 * If you're setting to it, all fields will be nullified, except one with relation classname equal to value type.
 *
 * Next, you can create pseudo-field by going throught one or several to-one relations:
 * @field fieldname -relative field.itsField.relativeField->fieldToLinkAsPseudo
 *
 * Also FieldInfo provides signals support for fields changes:
 * @see O_Dao_Signals
 *
 * Finally, you can extend the field with
 * @field:config $name -params
 *
 * @author Dmitry Kourinski
 */
class O_Dao_FieldInfo {
	/**
	 * External (database) name of this field
	 *
	 * @var string
	 */
	private $name;
	/**
	 * Array of field params
	 *
	 * @var Array
	 */
	private $params;
	/**
	 * Classname of current O_Dao_ActiveRecord
	 *
	 * @var string
	 */
	private $class;

	/**
	 * Instance of field
	 *
	 * @var O_Dao_Field_iFace
	 */
	private $fieldInstance;

	/**
	 * Creates FieldInfo object
	 *
	 * @param string $class O_Dao_ActiveRecord classname
	 * @param string $name Persistent field name
	 * @param string $type SQL type of field, if specified
	 * @param array $params
	 */
	public function __construct( $class, $name, $type, Array $params )
	{
		$this->class = $class;
		$this->name = $name;
		$this->params = $params;

		// check if it's relation
		if (isset( $params[ "has" ] )) {
			$relation = "has";
			$relationOwns = 0;
		} elseif (isset( $params[ "owns" ] )) {
			$relation = "owns";
			$relationOwns = 1;
		}

		// save info about relation to be ready to say it to another side
		if (isset( $relation )) {

			list ($quantity, $relationTargetBase) = explode( " ", $params[ $relation ], 2 );

			if ($quantity == "one") {
				$this->fieldInstance = new O_Dao_Field_ToOne( $this, $name, $relationOwns, $relationTargetBase );
			} else {
				$this->fieldInstance = new O_Dao_Field_ToMany( $this, $name, $relationOwns, $relationTargetBase );

			}
		} else {
			// Alias field
			if (isset( $params[ "alias" ] ) && strpos( $params[ "alias" ], "." )) {
				$this->fieldInstance = new O_Dao_Field_Alias( $this, $params[ "alias" ] );
				// Alias for a number of other fields
			} elseif (isset( $params[ "one-of" ] ) && strpos( $params[ "one-of" ], ";" )) {
				$this->fieldInstance = new O_Dao_Field_OneOf( $this );
				// Relative field
			} elseif (isset( $params[ "relative" ] ) && strpos( $params[ "relative" ], "->" )) {
				$this->fieldInstance = new O_Dao_Field_Relative( $this );
				// Image file
			} elseif (isset( $params[ "image" ] )) {
				$this->fieldInstance = new O_Dao_Field_Image( $this, $type, $this->name );
				// Atomic field
			} else {
				$this->fieldInstance = new O_Dao_Field_Atomic( $this, $type, $this->name );
			}
		}
	}

	/**
	 * Sets the class
	 *
	 * @see O_Dao_TableInfo::__construct()
	 * @access package
	 * @param string $class
	 */
	public function setClass( $class )
	{
		$this->class = $class;
		$this->fieldInstance->setFieldInfo( $this );
	}

	/**
	 * Returns current classname this field is attached to
	 *
	 * @return unknown
	 */
	public function getClass()
	{
		return $this->class;
	}

	/**
	 * Returns value of param stored in field config query
	 *
	 * @param string $paramName
	 * @param bool $parseAsArray
	 * @return string or array
	 */
	public function getParam( $paramName, $parseAsArray = false )
	{
		if (!isset( $this->params[ $paramName ] ))
			return null;
		if ($parseAsArray && !is_array( $this->params[ $paramName ] )) {
			$arr = array ();
			foreach (explode( ";", $this->params[ $paramName ] ) as $v) {
				$v = trim( $v );
				if (strpos( $v, ":" )) {
					$v = explode( ":", $v, 2 );
					$arr[ trim( $v[ 0 ] ) ] = trim( $v[ 1 ] );
				} else
					$arr[] = $v;
			}
			$this->params[ $paramName ] = $arr;
		}
		return $this->params[ $paramName ];
	}

	/**
	 * Merges params with the old ones
	 *
	 * @param array $params
	 * @access private
	 */
	public function addParams( array $params )
	{
		$this->params = array_merge( $this->params, $params );
		$this->fieldInstance->setFieldInfo( $this );
	}

	/**
	 * Adds field to CREATE query
	 *
	 * @param O_Db_Query $query
	 * @access private
	 */
	public function addFieldTypeToQuery( O_Db_Query $query )
	{
		return $this->fieldInstance->addFieldTypeToQuery( $query );
	}

	/**
	 * Refreshes relation object
	 *
	 * @param int $obj_id
	 * @access private
	 */
	public function reload( $obj_id )
	{
		return $this->fieldInstance->reload( $obj_id );
	}

	/**
	 * Returns true if the field is not a relation or alias
	 *
	 * @return bool
	 */
	public function isAtomic()
	{
		return $this->fieldInstance instanceof O_Dao_Field_Atomic;
	}

	/**
	 * Returns true if the field is an one-of alias
	 *
	 * @return bool
	 */
	public function isOneOf()
	{
		return $this->fieldInstance instanceof O_Dao_Field_OneOf;
	}

	/**
	 * Returns true if the field is an alias
	 *
	 * @return bool
	 */
	public function isAlias()
	{
		return $this->fieldInstance instanceof O_Dao_Field_Alias;
	}

	/**
	 * Returns true if it's a *-to-one relation
	 *
	 * @return bool
	 */
	public function isRelationOne()
	{
		return $this->fieldInstance instanceof O_Dao_Field_ToOne;
	}

	/**
	 * Returns true if it's a *-to-many relation
	 *
	 * @return bool
	 */
	public function isRelationMany()
	{
		if ($this->fieldInstance instanceof O_Dao_Field_Relative) {
			return $this->fieldInstance->isRelationMany();
		}
		return $this->fieldInstance instanceof O_Dao_Field_ToMany;
	}

	/**
	 * Returns true if it's a relation
	 *
	 * @return bool
	 */
	public function isRelation()
	{
		if ($this->fieldInstance instanceof O_Dao_Field_Relative) {
			return (bool)$this->fieldInstance->getTargetClass();
		}

		return $this->fieldInstance instanceof O_Dao_Field_iRelation;
	}

	/**
	 * Returns true if it's relative field
	 *
	 * @return bool
	 */
	public function isRelative()
	{
		return $this->fieldInstance instanceof O_Dao_Field_Relative;
	}

	/**
	 * Returns true if it's a file
	 *
	 * @return bool
	 */
	public function isFile()
	{
		return $this->fieldInstance instanceof O_Dao_Field_Image;
	}

	/**
	 * Returns true if it's one-to-* relation
	 *
	 * @return bool
	 */
	public function isOneToWhateverRelation()
	{
		if (!$this->fieldInstance instanceof O_Dao_Field_iRelation)
			return false;
		return $this->fieldInstance->getInverse()->isRelationOne();
	}

	/**
	 * Clone field instance on field info cloning
	 *
	 */
	public function __clone()
	{
		$this->fieldInstance = clone $this->fieldInstance;
		$this->fieldInstance->setFieldInfo( $this );
	}

	/**
	 * Returns relation target class name, if it's a relation
	 *
	 * @return string
	 */
	public function getRelationTarget()
	{
		return $this->fieldInstance instanceof O_Dao_Field_iRelation ? $this->fieldInstance->getTargetClass() : null;
	}

	/**
	 * Returns real field name for OneOf field
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @return string
	 */
	public function getRealField( O_Dao_ActiveRecord $obj )
	{
		return $this->isOneOf() ? $this->fieldInstance->getExistentFieldName( $obj ) : null;
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
		if (isset( $this->params[ "signal" ] )) {
			// Old value removed
			O_Dao_Signals::fire( O_Dao_Signals::EVENT_REMOVE, $this->params[ "signal" ], $this->class,
					$obj, $obj->{$this->name} );
			// New value is set
			O_Dao_Signals::fire( O_Dao_Signals::EVENT_SET, $this->params[ "signal" ], $this->class,
					$obj, $fieldValue );
		}

		return $this->fieldInstance->setValue( $obj, $fieldValue, $fieldExists );
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
		return $this->fieldInstance->getValue( $obj, $fieldValue, $fieldExists );
	}

	/**
	 * Returns query by object relations mapping
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param int $fieldValue
	 * @param string $subreq
	 * @return O_Dao_Query
	 * @access private
	 */
	public function getMappedQuery( O_Dao_ActiveRecord $obj, $fieldValue = null, $subreq = "" )
	{
		// Atomic field is wrong basis
		if ($this->isAtomic())
			throw new O_Ex_Logic( "Cannot create mapped query field by atomic field basis." );
			// If the basis is an alias for a number of fields, build query from it
		if ($this->isOneOf()) {
			$field = $this->fieldInstance->getExistentFieldName( $obj );
			if ($field)
				return $obj->{$field . "." . $subreq};
			return false;
		}

		$query = null;
		$joinOnField = $this->prepareMappedQuery( $query, $subreq );
		if ($this->isRelationMany() && $this->fieldInstance->getInverse()->isRelationMany()) {
			$rel = $this->fieldInstance->getRelation( 0 );
			$tbl = $rel->getRelationTableName();
			$als = "__rel";
			$query->join( $tbl . " " . $als, $als . "." . $rel->getTargetFieldName() . "=" . $joinOnField, "CROSS" );
			$joinOnField = $als . "." . $rel->getBaseFieldName();
		}
		$query->test( $joinOnField, $fieldValue ? $fieldValue : $obj->id )->clearFields()->field(
				"DISTINCT " . O_Dao_TableInfo::get( $query->getClass() )->getTableName() . ".*" );
		return $query;
	}

	/**
	 * Prepares query but doesn't substitute concrete object
	 *
	 * @param O_Dao_Query $query
	 * @param string $subreq
	 * @return string Field to test
	 * @access private
	 */
	public function prepareMappedQuery( O_Dao_Query &$query = null, $subreq = "" )
	{
		$fieldInfos = Array ();
		$subreq = explode( ".", $subreq );
		$info = $this;
		foreach ($subreq as $fieldName) {
			$info = O_Dao_TableInfo::get( $info->fieldInstance->getTargetClass() )->getFieldInfo( $fieldName );
			if (!$info->fieldInstance instanceof O_Dao_Field_iRelation)
				throw new O_Ex_Logic( "Cannot use non-relative field ($fieldName) as a part of mapped query field." );
			array_unshift( $fieldInfos, $info );
		}

		$joinOnField = null;
		$i = 0;
		while ($fieldInfo = current( $fieldInfos )) {
			$nextInfo = next( $fieldInfos );
			if (!$nextInfo)
				$nextInfo = $this;
			$joinOnField = $fieldInfo->modifyMappedQuery( $query, $joinOnField, $nextInfo, $i++ );
		}
		return $joinOnField;
	}

	/**
	 * Prepares query step for getting mapped query
	 *
	 * @param O_Dao_Query $query
	 * @param O_Dao_FieldInfo $nextInfo
	 * @param string $joinOnField From previous request
	 * @param int $i Current iteration
	 * @return string
	 */
	private function modifyMappedQuery( O_Dao_Query &$query = null, $joinOnField = null, O_Dao_FieldInfo $nextInfo = null, $i = 0 )
	{
		if (!$query) {
			$query = new O_Dao_Query( $this->fieldInstance->getTargetClass() );
			$core_tbl = O_Dao_TableInfo::get( $this->fieldInstance->getTargetClass() )->getTableName();
			$joinOnField = $core_tbl . ".id";
			if ($this->isOneToWhateverRelation() && $this->isRelationMany())
				$joinOnField = $core_tbl . "." . $this->fieldInstance->getInverse()->name;

		}

		$isOneToMany = $nextInfo->isRelationMany() && !$nextInfo->fieldInstance->getInverse()->isRelationMany();

		$currTable = O_Dao_TableInfo::get( $this->class )->getTableName();
		$currAlias = "__rel" . $i;

		// many-to-many: relation is a special table
		if ($this->isRelationMany() && $this->fieldInstance->getInverse()->isRelationMany()) {
			$rel = $this->fieldInstance->getRelation( 0 );
			$tbl = $rel->getRelationTableName();
			$als = "__rel_" . $i;

			$query->join( $tbl . " " . $als, $als . "." . $rel->getTargetFieldName() . "=" . $joinOnField, "CROSS" );

			if ($isOneToMany) {
				$query->join( $currTable . " " . $currAlias,
						$currAlias . ".id=" . $als . "." . $rel->getBaseFieldName(), "CROSS" );
				return $currAlias . "." . $nextInfo->fieldInstance->getInverse()->name;
			}

			return $als . "." . $rel->getBaseFieldName();
			//relation is current table itself
		} else {
			if ($this->isOneToWhateverRelation() && $this->isRelationMany())
				$relLeft = $currAlias . ".id";
			else
				$relLeft = $currAlias . "." . $this->name;
			$query->join( $currTable . " " . $currAlias, $relLeft . "=" . $joinOnField, "CROSS" );

			if ($isOneToMany) {
				return $currAlias . "." . $nextInfo->fieldInstance->getInverse()->name;
			}
			return $currAlias . ".id";
		}
	}

	/**
	 * Handles deletion of object -- or just of relation
	 *
	 * @param O_Dao_ActiveRecord $obj
	 * @param mixed $fieldValue
	 * @access private
	 */
	public function deleteThis( O_Dao_ActiveRecord $obj, $fieldValue = null )
	{
		if (isset( $this->params[ "signal" ] )) {
			// Old value removed
			O_Dao_Signals::fire( O_Dao_Signals::EVENT_REMOVE, $this->params[ "signal" ], $this->class,
					$obj, $obj->{$this->name} );
		}

		$this->fieldInstance->deleteThis( $obj, $fieldValue );
	}

}