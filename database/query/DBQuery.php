<?php

class DBQuery {

    /**
     * @var DATABASE for operations.
     */
    private $db;

    /**
     * @var string table name
     */
    private string $table = '';

    /**
     * @var string table alias
     */
    private string $tableAlias = '';

    /**
     * @var array Columns to select.
     */
    private array $selects = [];

    /**
     * @var DBConditionGroup condition
     */
    private DBConditionGroup $condition;

    /**
     * @var bool Next condition uses OR
     */
    private bool $_next_condition_is_or = false;

    /**
     * @var array values to set
     */
    private array $sets = [];

    /**
     * @var array table joins
     */
    private array $joins = [];

    /**
     * @var int result page size
     */
    private int $limit = 0;

    /**
     * @var int offset displacement
     */
    private int $offset = 0;

    /**
     * @var string table order column
     */
    private string $orderColumn = '';

    /**
     * @var string table order direction
     */
    private string $orderDirection = 'ASC';

    /**
     * @var string group by
     */
    private string $group = '';

    /**
     * @var string group by having
     */
    private string $having = '';

    /**
     * @var string class to parse results
     */
    private string $class = '';

    /**
     * @var array additional serialization properties for class
     */
    private array $classAdditionalProperties = [];

    function __construct(string $table, $db = null) {
        global $DATABASE;
        $this->db = $db == null ? $DATABASE : $db;
        $this->table = $table;
        $this->condition = new DBConditionGroup();
    }

    /**
     * Create a DBQuery object
     * 
     * @var string table name
     * @var Database? database
     */
    public static function instance(string $table, $db = null) {
        return new DBQuery($table, $db);
    }

    /**
     * Change table for this query.
     * 
     * @param string Table name
     * 
     * @return DBQuery
     */
    public function from(string $table) : DBQuery {
        $this->table = $table;
        return $this;
    }

    /**
     * Set the class used to parse results.
     * 
     * @param string Class name
     * 
     * @return DBQuery
     */
    public function setClass(string $class) : DBQuery {
        $this->class = $class;
        return $this;
    }

    /**
     * Add serialization property to the model.
     * 
     * @param string column name
     * @param string type string|int|float|bool|json
     * @param ?string property name
     * 
     * @return DBQuery
     */
    public function map(string $column, $type = 'default', $propertyPath = null) {
        $this->classAdditionalProperties[$column] = [
            'type' => strpos($column, '_id') !== FALSE ? 'int' : ($type == 'default' ? 'string' : $type),
            'path' => $propertyPath ?? $column
        ];
        return $this;
    }

    /**
     * Set an alias for the current table.
     * 
     * @param string alias
     * 
     * @return DBQuery
     */
    public function tableAs(string $alias) : DBQuery {
        $this->tableAlias = $alias;
        return $this;
    }

    /**
     * Specify which columns to select.
     * 
     * @var array columns
     * 
     * @return DBQuery self
     */
    public function select(...$columns) : DBQuery {
        foreach($columns as $col) {
            $this->selects[] = $col;
        }
        return $this;
    }

    /**
     * Set the result page size
     * 
     * @var int limit
     * 
     * @return DBQuery self
     */
    public function limit(int $limit) : DBQuery {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set the selection offset displacement
     * 
     * @var int offset
     * 
     * @return DBQuery self
     */
    public function offset(int $offset) : DBQuery {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Set the selection order
     * 
     * @var string order
     * 
     * @return DBQuery self
     */
    public function orderBy(string $orderColumn, string $direction = 'ASC') : DBQuery {
        $this->orderColumn = $orderColumn;
        $this->orderDirection = $direction;
        return $this;
    }

    /**
     * Set the selection group by
     * 
     * @param string group by
     * 
     * @return DBQuery self
     */
    public function groupBy(string $group) : DBQuery {
        $this->group = $group;
        return $this;
    }

    /**
     * Set the group by having condition
     * 
     * @param string condition
     * 
     * @return DBQuery self
     */
    public function having(string $having) : DBQuery {
        $this->having = $having;
        return $this;
    }

    /**
     * Add a condition
     * 
     * @param string|callable Column name or group of conditions
     * @param mixed value or operator
     * @param mixed value if operator is used
     * 
     * @return DBQuery self
     */
    public function where(string|callable $column, $valueOrOperator = '=', $value = null) : DBQuery {
        $this->condition->where($column, $valueOrOperator, $value);
        return $this;

    }

    /**
     * Add a condition
     * 
     * @param string|callable Column name or group of conditions
     * @param mixed value or operator
     * @param mixed value if operator is used
     * 
     * @return DBQuery self
     */
    public function orWhere(string|callable $column, $valueOrOperator = '=', $value = null) : DBQuery {
        $this->condition->orWHere($column, $valueOrOperator, $value);
        return $this;
    }

    /**
     * Add a raw SQL condition.
     * 
     * @param string SQL Condition
     * 
     * @return DBQuery
     */
    public function whereRaw(string $sql) : DBQuery {
        $this->condition->whereRaw($sql);
        return $this;
    }

    /**
     * Add a raw SQL condition.
     * 
     * @param string SQL Condition
     * 
     * @return DBQuery
     */
    public function orWhereRaw(string $sql) : DBQuery {
        $this->condition->orWhereRaw($sql);
        return $this;
    }

    /**
     * Add condition where column is in list or subquery.
     * 
     * @param string Column name
     * @param array|DBQuery List or subquery
     * 
     * @return DBQuery
     */
    public function whereIn(string $column, array|DBQuery $value) : DBQuery {
        $this->condition->whereIn($column, $value);
        return $this;
    }

    /**
     * Add condition where column is NOT in list or subquery.
     * 
     * @param string Column name
     * @param array|DBQuery List or subquery
     * 
     * @return DBQuery
     */
    public function whereNotIn(string $column, array|DBQuery $value) : DBQuery {
        $this->condition->whereNotIn($column, $value);
        return $this;
    }

    /**
     * Add condition where column is in list or subquery.
     * 
     * @param string Column name
     * @param array|DBQuery List or subquery
     * 
     * @return DBQuery
     */
    public function orWhereIn(string $column, array|DBQuery $value) : DBQuery {
        $this->condition->orWhereIn($column, $value);
        return $this;
    }

    /**
     * Add condition where column is NOT in list or subquery.
     * 
     * @param string Column name
     * @param array|DBQuery List or subquery
     * 
     * @return DBQuery
     */
    public function orWhereNotIn(string $column, array|DBQuery $value) : DBQuery {
        $this->condition->orWhereNotIn($column, $value);
        return $this;
    }

    /**
     * Set a column's value for insert or update operation.
     * 
     * @param string column
     * @param mixed value
     * @param bool wrap string value with quotes?
     * 
     * @return DBQuery self
     */
    public function set(string $column, $value, bool $raw = true) : DBQuery {
        $this->sets[$column] = [
            'value' => $value,
            'wrap' => !$raw
        ];
        return $this;
    }

    /**
     * Join a table.
     * 
     * @param string table
     * @param string join condition
     * 
     * @return DBQuery self
     */
    public function join(string $table, string $on) : DBQuery {
        $this->joins[$table] = $on;
        return $this;
    }

    /**
     * Add additional serialization properties to the model.
     * 
     * @param TableORM model
     * @param Generic query result
     */
    private function _addSerializationProperties($model, $result) {
        foreach($this->classAdditionalProperties as $column => &$data) {

            $val = $result->{$column};
            if ($val !== null) {
                $type = $data['type'];

                if ($type == 'int')
                    $val = intval($val);
                else if ($type == 'float') 
                    $val = floatval($val);
                else if ($type == 'bool')
                    $val = "$val" == '1';
                else if ($type == 'json')
                    $val = JSON::parse($val);
            }

            $model->addSerializationProperty($data['path'], $val);
        }
    }

    /**
     * Run the query and get the result.
     * 
     * @return array
     */
    public function get() : array {
        $query = $this->toString('select');
        $res = $this->db->run($query)->result;

        if (empty($this->class)) return $res;

        return arr($res)->map(function($item) {
            $cl = $this->class;
            $obj = new $cl($item);
            $this->_addSerializationProperties($obj, $item);
            return $obj;
        });
    }

    /**
     * Run the query and get the first element if any
     */
    public function first() {
        $this->limit(1);
        $query = $this->toString('select');
        $res = $this->db->run($query)->result;
        if (sizeof($res) == 0) return null;

        if (empty($this->class)) {
            return $res[0];
        }
        $cl = $this->class;
        $obj = new $cl($res[0]);
        $this->_addSerializationProperties($obj, $res[0]);
        return $obj;
    }

    /**
     * Run a delete operation
     * 
     * @return DBQueryResult
     */
    public function delete() : DBQueryResult {
        $query = $this->toString('delete');
        return $this->db->run($query);
    }

    /**
     * Run an insert operation
     * 
     * @return DBQueryResult
     */
    public function insert(array $columns = []) : DBQueryResult {

        foreach($columns as $key => $value) {
            $this->set($key, $value);
        }

        $query = $this->toString('insert');
        return $this->db->run($query);
    }

    /**
     * Run an update operation
     * 
     * @return DBQueryResult
     */
    public function update(array $columns = []) : DBQueryResult {

        foreach($columns as $key => $value) {
            $this->set($key, $value);
        }

        $query = $this->toString('update');
        return $this->db->run($query);
    }

    /**
     * Count results according to conditions.
     * 
     * @return int
     */
    public function count() : int {
        $query = $this->toString('count');
        return intval($this->db->run($query)->first->count);
    }

    /**
     * Builds the query and returns [query, params]
     * 
     * @param string operation type 'get'|'insert'|'update'|'delete'|'count'
     * 
     * @return string
     */
    public function toString(string $action = 'select') : string {

        $query = 'SELECT';
        if ($action == 'delete') {
            $query = 'DELETE';
        } else if ($action == 'update') {
            $query = 'UPDATE';
        } else if ($action == 'insert') {
            $query = 'INSERT';
        }

        // SELECT COLUMNS
        if ($action == 'select') {
            
            if (sizeof($this->selects) == 0) {
                $query .= ' *';
            } else {
                for($i = 0; $i < sizeof($this->selects); ++$i) {
                    $s = $this->selects[$i];
                    $query .= ' ' . Database::columnName($s);

                    if ($i < sizeof($this->selects) - 1) {
                        $query .= ',';
                    }
                }
            }
        } else if ($action == 'count') {
            $query .= ' COUNT(*) as count';
        }

        // TABLE NAME
        if ($action == 'insert') {
            $query .= ' INTO ' . Database::columnName($this->table);
        } else if ($action == 'update') {
            $query .= ' ' . Database::columnName($this->table);
        } else {
            $query .= ' FROM ' . Database::columnName($this->table);

            if (!empty($this->tableAlias)) {
                $query .= " AS " . Database::columnName($this->tableAlias);
            }

            foreach($this->joins as $table => $on) {
                $query .= " JOIN " . Database::columnName($table) . " ON $on";
            }
        }

        $query .= ' ';
        $params = [];

        // SETS
        if ($action == 'insert') {

            $query .= '(';

            $count = 0;
            foreach($this->sets as $k => $v) {
                $query .= Database::columnName($k);

                if ($count < sizeof($this->sets) - 1) {
                    $query .= ', ';
                }

                ++ $count;
            }

            $query .= ') VALUES (';

            $count = 0;
            foreach($this->sets as $k => $v) {

                $query .= $v['wrap'] ? Database::prepare($v['value']) : $v['value'];

                if ($count < sizeof($this->sets) - 1) {
                    $query .= ', ';
                }

                ++ $count;
            }

            $query .= ')';

            return [$query, $params];

        }

        if ($action == 'update') {

            $query .= 'SET ';

            $count = 0;
            foreach($this->sets as $k => $v) {

                $query .= Database::columnName($k) . ' = ' . ($v['wrap'] ? Database::prepare($v['value']) : $v['value']);

                if ($count < sizeof($this->sets) - 1) {
                    $query .= ', ';
                }

                ++ $count;
            }

        }

        // WHEREs
        if ($action != 'insert') {
            $con = $this->condition->toString();
            if (!empty($con)) {
                $query .= "WHERE $con";
            }
        }

        // EXTRAS
        if ($action == 'select') {

            if ($this->group != '') {
                $query .= " GROUP BY " . Database::columnName($this->group);

                if ($this->having != '') {
                    $query .= " HAVING $this->having";
                }
            }

            if ($this->orderColumn != '') {
                $query .= " ORDER BY ". Database::columnName($this->orderColumn) ." $this->orderDirection";
            }

            if ($this->limit > 0) {
                $query .= " LIMIT $this->limit";
            }

            if ($this->offset > 0) {
                $query .= " OFFSET $this->offset";
            }

        }

        return $query;

    }

}