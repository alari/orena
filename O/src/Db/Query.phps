<?php
class O_Db_Query {
	/**
	 * Array of tables to work with
	 *
	 * @var array
	 * @see Query::from(), Query::prepareFrom()
	 */
	protected $from = array ();
	/**
	 * Array of SQL-options to use with SELECT
	 *
	 * @var array
	 * @see Query::setSqlOption(), Query::prepareSelect()
	 */
	protected $sql_options = array ();
	
	/**
	 * Array of join conditions
	 *
	 * @var array("table", "cond", "type")
	 * @see Query::join(), Query::prepareFrom()
	 */
	protected $joins = array ();
	
	/**
	 * Array of WHERE parameters
	 *
	 * @var array
	 * @see Query::where(), Query::test(), Query::prepareWhere()
	 */
	protected $where = array ();
	
	/**
	 * Array of fields to select/update/insert/create table
	 *
	 * @var array
	 * @see Query::field(), Query::prepareInsert(), Query::prepareUpdate(), Query::prepareSelect(), Query::prepareCreate
	 */
	protected $fields = array ();
	
	/**
	 * Array of indexes to create table
	 *
	 * @var array
	 * @see Query::index(), Query::prepareCreate()
	 */
	protected $indexes = array ();
	
	/**
	 * Array of fields to sort by
	 *
	 * @var array
	 * @see Query::orderBy(), Query::prepareTail()
	 */
	protected $orders = array ();
	
	/**
	 * Array of fields to group by
	 *
	 * @var array
	 * @see Query::groupBy(), Query::prepareTail()
	 */
	protected $group_by = array ();
	
	/**
	 * Limit by
	 *
	 * @var int
	 * @see Query::limit(), Query::prepareTail()
	 */
	protected $limit = 0;
	/**
	 * Offset by
	 *
	 * @var int
	 * @see Query::limit(), Query::prepareTail()
	 */
	protected $offset = 0;
	
	/**
	 * Condition to execute after query
	 *
	 * @var string
	 * @see Query::having(), Query::prepareTail()
	 */
	protected $having_condition = null;
	/**
	 * Parameters of HAVING condition
	 *
	 * @var array
	 * @see Query::having(), Query::prepareTail()
	 */
	protected $having_params = array ();
	
	/**
	 * Array of params used to execute query
	 *
	 * @var array
	 * @see Query::bindParams()
	 * @access private
	 */
	protected $params = array ();
	
	/**
	 * ID of database connection
	 *
	 * @var int
	 * @see Query::conn(), Query::__construct()
	 * @access private
	 */
	protected $db_conn = O_Db_Manager::CONN_DEFAULT;
	
	/**
	 * Last executed statement object
	 *
	 * @var PDOStatement
	 * @access private
	 */
	protected $stmt;
	
	/**
	 * Prepared statements
	 *
	 * @var PDOStatement[]
	 */
	protected static $prepared_stmts = Array ();
	
	/**
	 * Line of tables used in query
	 *
	 * @var string
	 */
	protected $tables = "";
	
	/**
	 * Array of tables with deny to prepare statements
	 *
	 * @var Array
	 */
	protected static $disable_preparing = Array ();
	
	/**
	 * @see Query::setSqlOption()
	 */
	const CALC_FOUND_ROWS = "SQL_CALC_FOUND_ROWS";
	const CACHE = "SQL_CACHE";
	
	/**
	 * @see Query::test()
	 */
	const EQ = "=";
	const IN = " IN ";
	const GT = ">";
	const GT_EQ = ">=";
	const LT = "<";
	const LT_EQ = "<=";
	const EXISTS = " EXISTS ";
	const LIKE = " LIKE ";
	
	/**
	 * @access private
	 */
	const T_WHERE_SIMPLE = 1;
	const T_WHERE_TEST = 2;
	const T_WHERE_OR = 3;

	/**
	 * Query constructor
	 *
	 * @param string $table Main table to manipulate with
	 * @param string $alias Table alias
	 * @param string $db_conn Internal database connection ID
	 */
	public function __construct( $table = null, $alias = null, $db_conn = O_Db_Manager::CONN_DEFAULT )
	{
		if ($table) {
			$this->from[] = $table . ($alias ? " " . $alias : "");
		}
		$this->db_conn = $db_conn;
	}

	/**
	 * Shortcut for constructor
	 *
	 * @param string $class
	 * @param string $alias
	 * @param string $db_conn Internal database connection ID
	 * @return O_Db_Query
	 */
	static public function get( $table, $alias = null, $db_conn = O_Db_Manager::CONN_DEFAULT )
	{
		return new self( $table, $alias, $db_conn );
	}

	/**
	 * Checks if table exists in database
	 *
	 * @return bool
	 */
	public function tableExists()
	{
		if (!isset( $this->from[ 0 ] )) {
			throw new Exception( "No table specified for query." );
		}
		$table_name = $this->from[ 0 ];
		if (strpos( $table_name, " " ))
			$table_name = substr( $table_name, 0, strpos( $table_name, " " ) );
		$stmt = O_Db_Manager::getConnection()->query( "SHOW TABLE STATUS WHERE name = '" . $table_name . "'" );
		if (!$stmt)
			return false;
		$stmt->execute();
		return (bool)count( $stmt->fetchAll() );
	}

	/**
	 * Adds table to the beginning of FROM
	 *
	 * @param string $table Table name
	 * @param string $alias Table alias
	 * @return O_Db_Query
	 */
	public function from( $table, $alias = null )
	{
		array_unshift( $this->from, $table . ($alias ? " " . $alias : "") );
		return $this;
	}

	/**
	 * Joins table by adding it at the end of FROM
	 *
	 * @param string $table
	 * @param string $alias
	 * @return O_Db_Query
	 */
	public function addFrom( $table, $alias = null )
	{
		$this->from[] = $table . ($alias ? " " . $alias : "");
		return $this;
	}

	/**
	 * Removes all tables from FROM section
	 *
	 * @return O_Db_Query
	 */
	public function clearFrom()
	{
		$this->from = array ();
		return $this;
	}

	/**
	 * Processes any JOIN condition
	 *
	 * @param string $table Table to join. With alias separated by space if needed
	 * @param strig $condition Condition to join
	 * @param string $type Join type
	 * @return O_Db_Query
	 */
	public function join( $table, $condition, $type = "LEFT" )
	{
		$this->joins[] = array ("table" => $table, "cond" => $condition, "type" => $type);
		return $this;
	}

	/**
	 * Joins the table if it's not already joined
	 *
	 * @param string $table Table to join. With alias separated by space if needed
	 * @param strig $condition Condition to join
	 * @param string $type Join type
	 * @return O_Db_Query
	 */
	public function joinOnce( $table, $condition, $type = "LEFT" )
	{
		foreach ($this->joins as $join)
			if ($join[ "table" ] == $table)
				return $this;
		return $this->join( $table, $condition, $type );
	}

	/**
	 * Removes all JOIN tables
	 *
	 * @return O_Db_Query
	 */
	public function clearJoins()
	{
		$this->joins = array ();
		return $this;
	}

	/**
	 * Any WHERE condition
	 *
	 * @param string $condition
	 * @param mixed $param ...
	 * @return O_Db_Query
	 */
	public function where( $condition )
	{
		$cond = Array ("cond" => $condition, "t" => self::T_WHERE_SIMPLE);
		$args = func_get_args();
		array_shift( $args );
		$cond[ "params" ] = $args;
		$this->where[] = $cond;
		return $this;
	}

	/**
	 * Removes all conditions from WHERE
	 *
	 * @return O_Db_Query
	 */
	public function clearWhere()
	{
		$this->where = array ();
		return $this;
	}

	/**
	 * WHERE condition with two fields and one operator
	 *
	 * @param string $field Database column name
	 * @param mixed $value Null, bool, int, double, string, other query
	 * @param const $type
	 * @return O_Db_Query
	 */
	public function test( $field, $value, $type = self::EQ )
	{
		$this->where[] = Array ("t" => self::T_WHERE_TEST, "param" => $value, "cond" => $type, "field" => $field);
		return $this;
	}

	/**
	 * Adds OR in condition
	 *
	 * @return O_Db_Query
	 */
	public function addOr()
	{
		$this->where[] = Array ("t" => self::T_WHERE_OR);
		return $this;
	}

	/**
	 * Processes field to UPDATE, SELECT or INSERT it
	 *
	 * @param string $name Database column name
	 * @param string $aliasOrValue Alias to select or value to update/insert or field type to create/alter table
	 * @param bool $asIs If true, no escaping will be produced
	 * @return O_Db_Query
	 */
	public function field( $name, $aliasOrValue = null, $asIs = false )
	{
		$this->fields[] = array ($name, $aliasOrValue, $asIs);
		return $this;
	}

	/**
	 * Removes all fields
	 *
	 * @return O_Db_Query
	 */
	public function clearFields()
	{
		$this->fields = array ();
		return $this;
	}

	/**
	 * Adds a key to CREATE TABLE
	 *
	 * @param string $field
	 * @param string $type
	 * @param string $name
	 * @see Query::prepareCreate()
	 * @return O_Db_Query
	 */
	public function index( $field, $type = "key", $name = null )
	{
		$this->indexes[] = array ($type, $field, $name);
		return $this;
	}

	/**
	 * Adds SQL SELECT options like SQL_CACHE or SQL_CALC_FOUND_ROWS
	 *
	 * @param const $option
	 * @return O_Db_Query
	 */
	public function setSqlOption( $option )
	{
		$this->sql_options[] = $option;
		return $this;
	}

	/**
	 * Adds a field to order by
	 *
	 * @param string $field
	 * @return O_Db_Query
	 */
	public function orderBy( $field )
	{
		$this->orders[] = $field;
		return $this;
	}

	/**
	 * Removes all OrderBy fields
	 *
	 * @return O_Db_Query
	 */
	public function clearOrders()
	{
		$this->orders = array ();
		return $this;
	}

	/**
	 * Adds field to GROUP BY condition
	 *
	 * @param string $field
	 * @return O_Db_Query
	 */
	public function groupBy( $field )
	{
		$this->group_by[] = $field;
		return $this;
	}

	/**
	 * Clears all GroupBy fields
	 *
	 * @return O_Db_Query
	 */
	public function clearGroupBy()
	{
		$this->group_by = array ();
		return $this;
	}

	/**
	 * Adds offset/limit to SELECT / UPDATE query
	 *
	 * @param [opt]int $offset
	 * @param int $limit
	 * @return O_Db_Query
	 */
	public function limit( $offset = 0, $limit = 0 )
	{
		$this->limit = (int)($limit ? $limit : $offset);
		$this->offset = (int)($limit ? $offset : 0);
		return $this;
	}

	/**
	 * WHERE-like condition to process after whole query
	 *
	 * @param string $condition
	 * @return self
	 */
	public function having( $condition )
	{
		$this->having_condition = $condition;
		$this->having_params = func_get_args();
		array_shift( $this->having_params );
		return $this;
	}

	/**
	 * Returns number of founded rows for SQL_CALC_FOUND_ROWS
	 *
	 * @return int
	 */
	public function getFoundRows()
	{
		return $this->conn()->query( "SELECT FOUND_ROWS()" )->fetchColumn();
	}

	/**
	 * Processes builded SELECT
	 *
	 * @return PDOStatement
	 */
	public function select()
	{
		$this->stmt = $this->prepareStmt( $this->prepareSelect() );
		
		$this->bindParams( $this->stmt );
		$this->stmt->execute();
		
		$this->stmt->setFetchMode( PDO::FETCH_ASSOC );
		
		return $this->stmt;
	}

	/**
	 * CREATE TABLE
	 *
	 * @param string $tail
	 * @return PDOStatement
	 */
	public function create( $tail = "" )
	{
		$this->stmt = $this->conn()->prepare( $this->prepareCreate( $tail ) );
		
		$this->stmt->execute();
		
		return $this->stmt;
	}

	/**
	 * Executes ALTER TABLE query with ONE action for each field/index
	 *
	 * @param string $command
	 * @return PDOStatement
	 */
	public function alter( $command = "ADD" )
	{
		$this->stmt = $this->conn()->prepare( $this->prepareAlter( $command ) );
		
		$this->stmt->execute();
		
		return $this->stmt;
	}

	/**
	 * Processes builded UPDATE
	 *
	 * @return int Affected rows
	 */
	public function update()
	{
		$this->stmt = $this->prepareStmt( $this->prepareUpdate() );
		
		$this->bindParams( $this->stmt );
		$this->stmt->execute();
		
		return $this->stmt->rowCount();
	}

	/**
	 * Deletes matching rows
	 *
	 * @return int Number of deleted rows
	 */
	public function delete()
	{
		$this->stmt = $this->prepareStmt( $this->prepareDelete() );
		
		$this->bindParams( $this->stmt );
		$this->stmt->execute();
		
		return $this->stmt->rowCount();
	}

	/**
	 * Processes builded INSERT
	 *
	 * @return int lastInsertId
	 */
	public function insert()
	{
		$this->stmt = $this->prepareStmt( $this->prepareInsert() );
		
		$this->bindParams( $this->stmt );
		
		$this->stmt->execute();
		
		return $this->conn()->lastInsertId();
	}

	/**
	 * Disables statements preparing for particular table use
	 *
	 * @param string $table
	 */
	static public function disablePreparing( $table )
	{
		if (!in_array( $table, self::$disable_preparing ))
			self::$disable_preparing[] = $table;
	}

	/**
	 * Returns prepared statement to execute
	 *
	 * @param string $query
	 * @return PDOStatement
	 */
	protected function prepareStmt( $query )
	{
		if ($this->tables && count( self::$disable_preparing )) {
			foreach (self::$disable_preparing as $table)
				if (strpos( $this->tables, $table ) !== false) {
					return $this->conn()->prepare( $query );
				}
		}
		if (!isset( self::$prepared_stmts[ $query ] ) || !self::$prepared_stmts[ $query ] instanceof PDOStatement) {
			self::$prepared_stmts[ $query ] = $this->conn()->prepare( $query );
		}
		return self::$prepared_stmts[ $query ];
	}

	/**
	 * Binds param values to execute query
	 *
	 * @param PDOStatement $query
	 */
	protected function bindParams( $query )
	{
		foreach ($this->params as $k => $v) {
			if (is_null( $v ))
				$query->bindValue( $k + 1, null, PDO::PARAM_NULL );
			elseif ($v === '')
				$query->bindValue( $k + 1, '' );
			elseif (is_numeric( $v ))
				$query->bindValue( $k + 1, $v, PDO::PARAM_INT );
			elseif (is_double( $v ))
				$query->bindValue( $k + 1, str_replace( ",", ".", $v ), PDO::PARAM_INT );
			elseif ($v === true || $v === false)
				$query->bindValue( $k + 1, $v, PDO::PARAM_BOOL );
			else
				$query->bindValue( $k + 1, $v );
		}
	}

	/**
	 * Returns connection assotiated with this query
	 *
	 * @return PDO
	 */
	protected function conn()
	{
		return O_Db_Manager::getConnection( $this->db_conn );
	}

	/**
	 * Prepares CREATE TABLE
	 *
	 * @param string $tail to put after query
	 * @return string
	 */
	protected function prepareCreate( $tail )
	{
		$query = "CREATE TABLE " . $this->from[ 0 ] . "(";
		foreach ($this->fields as $k => $v) {
			$query .= ($k ? ",\n" : "") . $v[ 0 ] . " " . $v[ 1 ];
		}
		foreach ($this->indexes as $k => $v) {
			$query .= ",\n" . $v[ 0 ] . ($v[ 2 ] ? " `{$v[2]}` " : "") . "(" . $v[ 1 ] . ")";
		}
		$query .= ") " . $tail;
		
		return $query;
	}

	/**
	 * Prepares ALTER TABLE
	 *
	 * @param string $cmd to put before each field/index
	 * @return string
	 */
	protected function prepareAlter( $cmd )
	{
		$query = "ALTER TABLE " . $this->from[ 0 ] . " ";
		foreach ($this->fields as $k => $v) {
			$query .= ($k ? ",\n" : "") . $cmd . " COLUMN " . $v[ 0 ] . " " . $v[ 1 ];
		}
		foreach ($this->indexes as $k => $v) {
			$query .= ($k || count( $this->fields ) ? ",\n" : "") . $cmd . " " . $v[ 0 ] . ($v[ 2 ] ? " `{$v[2]}` " : "") .
						 "(" . $v[ 1 ] . ")";
		}
		
		return $query;
	}

	/**
	 * Prepares INSERT query string and params
	 *
	 * @return string
	 * @access private
	 */
	public function prepareInsert()
	{
		$this->params = array ();
		
		$query = "INSERT INTO " . $this->from[ 0 ];
		
		if (!count( $this->fields ))
			return $query . "() VALUES()";
		
		$query .= " SET ";
		
		foreach ($this->fields as $k => $v) {
			$query .= ($k ? ", " : "") . $v[ 0 ] . "=";
			if ($v[ 2 ]) {
				$query .= $v[ 1 ];
			} else {
				$query .= "?";
				$this->params[] = $v[ 1 ];
			}
		}
		
		return $query;
	}

	/**
	 * Prepares UPDATE query string and params
	 *
	 * @return string
	 */
	protected function prepareUpdate()
	{
		$this->params = array ();
		
		$query = "UPDATE ";
		foreach ($this->from as $k => $v) {
			$query .= ($k ? ", " : "") . $v;
		}
		$query .= " SET ";
		
		foreach ($this->fields as $k => $v) {
			$query .= ($k ? ", " : "") . $v[ 0 ] . "=";
			if ($v[ 2 ]) {
				$query .= $v[ 1 ];
			} else {
				$query .= "?";
				$this->params[] = $v[ 1 ];
			}
		}
		
		$this->prepareWhere( $query );
		
		return $query;
	}

	/**
	 * Prepares DELETE query
	 *
	 * @return string
	 */
	protected function prepareDelete()
	{
		$this->params = array ();
		
		$query = "DELETE";
		$this->prepareFrom( $query );
		
		return $query;
	}

	/**
	 * Prepares SELECT query string and params
	 *
	 * @return string
	 */
	public function prepareSelect()
	{
		$this->params = array ();
		
		$query = "SELECT ";
		foreach ($this->sql_options as $option) {
			$query .= $option . " ";
		}
		if (count( $this->fields )) {
			foreach ($this->fields as $k => $v) {
				$query .= ($k ? ", " : "") . $v[ 0 ] . ($v[ 1 ] ? " AS " . $v[ 1 ] : "");
			}
		} else {
			if (count( $this->from )) {
				list ($tbl, ) = explode( " ", $this->from[ 0 ], 2 );
				$tbl .= ".";
			} else
				$tbl = "";
			$query .= $tbl . "*";
		}
		
		$this->prepareFrom( $query );
		return $query;
	}

	/**
	 * Prepares query from FROM to end
	 *
	 * @param string $query
	 * @access private
	 */
	protected function prepareFrom( &$query )
	{
		$this->tables = "";
		$query .= " FROM ";
		foreach ($this->from as $k => $v) {
			$query .= ($k ? ", " : "") . $v;
			$this->tables .= " " . $v;
		}
		foreach ($this->joins as $j) {
			$query .= " " . $j[ "type" ] . " JOIN " . $j[ "table" ] . " ON (" . $j[ "cond" ] . ")";
			$this->tables .= " " . $j[ "table" ];
		}
		$this->prepareWhere( $query );
	}

	/**
	 * Prepares query from WHERE to end
	 *
	 * @param string $query
	 * @access private
	 */
	protected function prepareWhere( &$query )
	{
		
		if (count( $this->where )) {
			$query .= " WHERE ";
			
			$where = "";
			$was_or = false;
			
			foreach ($this->where as $k => $v) {
				switch ($v[ "t" ]) {
					case self::T_WHERE_SIMPLE :
						$where .= ($k && $was_or != 2 ? " AND " : "") . "(" . $v[ "cond" ] . ")";
						if (isset( $v[ "params" ] ) && count( $v[ "params" ] )) {
							foreach ($v[ "params" ] as $p)
								$this->params[] = $p;
						}
						if ($was_or == 2)
							$was_or = 1;
					break;
					case self::T_WHERE_TEST :
						if ($v[ "cond" ] == self::EQ && is_array( $v[ "param" ] ))
							$v[ "cond" ] = self::IN;
						$where .= ($k && $was_or != 2 ? " AND " : "") . "(" . $v[ "field" ] . $v[ "cond" ];
						if (is_array( $v[ "param" ] )) {
							$where .= "(";
							foreach ($v[ "param" ] as $k => $v) {
								$where .= ($k ? "," : "") . "?";
								$this->params[] = $v;
							}
							$where .= ")";
						} elseif ($v[ "param" ] instanceof self) {
							$where .= "(";
							$where .= $v[ "param" ]->prepareSelect();
							$this->tables .= $v[ "param" ]->tables;
							foreach ($v[ "param" ]->params as $p)
								$this->params[] = $p;
							$where .= ")";
						} else {
							$where .= "?";
							$this->params[] = $v[ "param" ];
						}
						$where .= ")";
						if ($was_or == 2)
							$was_or = 1;
					break;
					case self::T_WHERE_OR :
						$where .= ") OR (";
						$was_or = 2;
					break;
				}
			}
			
			if ($was_or)
				$where = "($where)";
			
			$query .= $where;
		
		}
		
		$this->prepareTail( $query );
	}

	/**
	 * Prepares group by, orders, limits and having
	 *
	 * @param string $query
	 * @access private
	 */
	protected function prepareTail( &$query )
	{
		if (count( $this->orders )) {
			$query .= " ORDER BY ";
			foreach ($this->orders as $k => $v) {
				$query .= ($k ? ", " : "") . $v;
			}
		}
		
		if (count( $this->group_by )) {
			$query .= " GROUP BY ";
			foreach ($this->group_by as $k => $v) {
				$query .= ($k ? ", " : "") . $v;
			}
		}
		
		if ($this->limit) {
			$query .= " LIMIT " . ($this->offset ? $this->offset . ", " : "") . $this->limit;
		}
		
		if ($this->having_condition) {
			$query .= " HAVING " . $this->having_condition;
			
			foreach ($this->having_params as $p) {
				$this->params[] = $p;
			}
		}
	}
}