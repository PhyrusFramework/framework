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
     * @var DBCondition condition
     */
    private DBCondition $condition;

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
        $this->condition = new DBCondition();
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
     * @param string Column name
     * @param mixed value or operator
     * @param mixed value if operator is used
     * 
     * @return DBQuery self
     */
    public function where(string $column, $valueOrOperator = '=', $value = null) : DBQuery {

        $operator = $value === null ? '=' : $valueOrOperator;
        $val = $value === null ? $valueOrOperator : $value;
        if ($valueOrOperator === null) {
            $operator = 'IS';
            $val = null;
        }
        else if (gettype($valueOrOperator) == 'string' && 
        in_array(strtolower($valueOrOperator), ['is', 'is not']) && 
        $value === null) {
            $operator = $valueOrOperator;
            $val = null;
        }

        if ($val instanceof DBQuery) {
            $this->condition->add([
                'type' => 'subquery',
                'column' => $column,
                'operator' => $operator,
                'subquery' => $val
            ]);
        } else {
            $this->condition->add([
                'type' => 'simple',
                'column' => $column,
                'operator' => $operator,
                'value' => $val
            ]);
        }

        return $this;

    }

    /**
     * Add a condition
     * 
     * @param string Column name
     * @param mixed value or operator
     * @param mixed value if operator is used
     * 
     * @return DBQuery self
     */
    public function orWhere(string $column, $valueOrOperator = '=', $value = null) : DBQuery {
        $this->or(function($q) use ($column, $valueOrOperator, $value) {
            $q->where($column, $valueOrOperator, $value);
        });

        return $this;
    }

    /**
     * Split the previous conditions and the following with an OR operator.
     * 
     * @return DBQuery self
     */
    public function or(callable $block) : DBQuery {

        $this->condition->openBlock('OR');
        $block($this);
        $this->condition->closeBlock();

        return $this;
    }

    public function and(callable $block) : DBQuery {

        $this->condition->openBlock('AND');
        $block($this);
        $this->condition->closeBlock();
        
        return $this;

    }

    /**
     * Add a string SQL condition
     * 
     * @param string condition
     * @param array parameters
     * 
     * @return DBQuery self
     */
    public function rawWhere(string $line, array $params = []) : DBQuery {

        $this->condition->add([
            'type' => 'raw',
            'query' => $line,
            'params' => $params
        ]);

        return $this;
    }

    /**
     * Add a WHERE IN condition.
     * 
     * @param string column
     * @param DBQuery subquery
     * 
     * @return DBQuery self
     */
    public function whereIn(string $column, DBQuery $subquery) : DBQuery {

        $this->condition->add([
            'type' => 'wherein',
            'in' => true,
            'column' => $column,
            'subquery' => $subquery
        ]);
        return $this;
    }

    /**
     * Add a WHERE NOT IN condition
     * 
     * @param string column
     * @param DBQuery subquery
     * 
     * @return DBQuery self
     */
    public function whereNotIn(string $column, DBQuery $subquery) : DBQuery {

        $this->condition->add([
            'type' => 'wherein',
            'in' => false,
            'column' => $column,
            'subquery' => $subquery
        ]);

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
    public function set(string $column, $value, bool $quotes = true) : DBQuery {
        $this->sets[$column] = [
            'value' => $value,
            'wrap' => $quotes
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
        $query = $this->buildQuery('select');
        $res = $this->db->run($query[0], $query[1])->result;

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
        $query = $this->buildQuery('select');
        $res = $this->db->run($query[0], $query[1])->result;
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
        $query = $this->buildQuery('delete');
        return $this->db->run($query[0], $query[1]);
    }

    /**
     * Run an insert operation
     * 
     * @return DBQueryResult
     */
    public function insert() : DBQueryResult {
        $query = $this->buildQuery('insert');
        return $this->db->run($query[0], $query[1]);
    }

    /**
     * Run an update operation
     * 
     * @return DBQueryResult
     */
    public function update() : DBQueryResult {
        $query = $this->buildQuery('update');
        return $this->db->run($query[0], $query[1]);
    }

    /**
     * Count results according to conditions.
     * 
     * @return int
     */
    public function count() : int {
        $query = $this->buildQuery('count');
        return intval($this->db->run($query[0], $query[1])->first->count);
    }

    /**
     * Prepare the name for the query.
     * 
     * @param string Table name
     * 
     * @return string Prepared name
     */
    private function tbName(string $name) : string {

        if (strpos($name, ' ') !== FALSE ||
        strpos($name, '(') !== FALSE) {
            return $name;
        }

        if (strpos($name, '.') !== FALSE) {

            $parts = explode('.', $name);
            $str = '';
            for($i = 0; $i < count($parts); ++$i) {
                
                $p = $parts[$i];
                if (trim($p) == '*') $str .= $p;
                else $str .= "`$p`";

                if ($i < count($parts) - 1) {
                    $str .= '.';
                }

            }

            return $str;

        }
        
        if (trim($name) == '*') return $name;
        return "`$name`";
    }

    /**
     * Builds the query and returns [query, params]
     * 
     * @param string operation type 'get'|'insert'|'update'|'delete'|'count'
     * @param int automatically managed, ignore.
     * 
     * @return array
     */
    public function buildQuery(string $action = 'select', int $paramCounter = 0) {

        $counter = $paramCounter + 0;

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
                    $query .= ' ' . $this->tbName($s);

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
            $query .= ' INTO ' . $this->tbName($this->table);
        } else if ($action == 'update') {
            $query .= ' ' . $this->tbName($this->table);
        } else {
            $query .= ' FROM ' . $this->tbName($this->table);

            if (!empty($this->tableAlias)) {
                $query .= " AS " . $this->tbName($this->tableAlias);
            }

            foreach($this->joins as $table => $on) {
                $query .= " JOIN " . $this->tbName($table) . " ON $on";
            }
        }

        $query .= ' ';
        $params = [];

        // SETS
        if ($action == 'insert') {

            $query .= '(';

            $count = 0;
            foreach($this->sets as $k => $v) {
                $query .= $this->tbName($k);

                if ($count < sizeof($this->sets) - 1) {
                    $query .= ', ';
                }

                ++ $count;
            }

            $query .= ') VALUES (';

            $count = 0;
            foreach($this->sets as $k => $v) {

                if ($v['wrap']) {
                    $query .= ":p_$counter";
                    $params["p_$counter"] = $v['value'];
                    ++ $counter;
                } else {
                    $query .= $v['value'];
                }

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

                if ($v['wrap']) {
                    $query .= $this->tbName($k) . " = :p_$counter";
                    $params["p_$counter"] = $v['value'];
                    ++ $counter;    
                } else {
                    $query .= $this->tbName($k) . " = " . $v['value'];
                }

                if ($count < sizeof($this->sets) - 1) {
                    $query .= ', ';
                }

                ++ $count;
            }

        }

        // WHEREs
        if ($action != 'insert' && $this->condition->notEmpty()) {
            $r = $this->condition->toString($params, $counter);
            $counter = $r[1];
            $query .= empty($r[0]) ? '' : ' WHERE ' . $r[0];
        }

        // EXTRAS
        if ($action == 'select') {

            if ($this->group != '') {
                $query .= " GROUP BY " . $this->tbName($this->group);

                if ($this->having != '') {
                    $query .= " HAVING $this->having";
                }
            }

            if ($this->orderColumn != '') {
                $query .= " ORDER BY ". $this->tbName($this->orderColumn) ." $this->orderDirection";
            }

            if ($this->limit > 0) {
                $query .= " LIMIT $this->limit";
            }

            if ($this->offset > 0) {
                $query .= " OFFSET $this->offset";
            }

        }

        return [$query, $params, $counter];

    }

}