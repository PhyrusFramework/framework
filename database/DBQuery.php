<?php

class DBQuery {

    /**
     * @var DATABASE for operations.
     */
    private $db;

    /**
     * @var string table name
     */
    private $table = '';

    /**
     * @var array Columns to select.
     */
    private array $selects = [];

    /**
     * @var array conditions
     */
    private array $wheres = [
        [] // First where
    ];

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
     * @var string table order
     */
    private string $order = '';

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

    function __construct(string $table, $db = null) {
        global $DATABASE;
        $this->db = $db == null ? $DATABASE : $db;
        $this->table = $table;
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
    public function orderBy(string $order) : DBQuery {
        $this->order = $order;
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

        $last = sizeof($this->wheres) - 1;
        $arr = &$this->wheres[$last];
        $arr[] = [
            'type' => 'simple',
            'column' => $column,
            'operator' => $value == null ? '=' : $valueOrOperator,
            'value' => $value == null ? $valueOrOperator : $value
        ];

        return $this;

    }

    /**
     * Split the previous conditions and the following with an OR operator.
     * 
     * @return DBQuery self
     */
    public function or() : DBQuery {

        $this->wheres[] = [];

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

        $last = sizeof($this->wheres) - 1;
        $arr = &$this->wheres[$last];
        $arr[] = [
            'type' => 'raw',
            'query' => $line,
            'params' => $params
        ];
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

        $last = sizeof($this->wheres) - 1;
        $arr = &$this->wheres[$last];
        $arr[] = [
            'type' => 'wherein',
            'in' => true,
            'column' => $column,
            'subquery' => $subquery
        ];
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
        $last = sizeof($this->wheres) - 1;
        $arr = &$this->wheres[$last];
        $arr[] = [
            'type' => 'wherein',
            'in' => false,
            'column' => $column,
            'subquery' => $subquery
        ];
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
            return new $cl($item);
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
        return new $cl($res[0]);
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
     * Builds the query and returns [query, params]
     * 
     * @param string operation type 'get'|'insert'|'update'|'delete'|'count'
     * @param int automatically managed, ignore.
     * 
     * @return array
     */
    public function buildQuery(string $action, int $paramCounter = 0) {

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
                    $query .= ' ' . $this->selects[$i];

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
            $query .= " INTO `$this->table`";
        } else if ($action == 'update') {
            $query .= ' `' . $this->table . '`';
        } else {
            $query .= " FROM `$this->table`";

            foreach($this->joins as $table => $on) {
                $query .= " JOIN `$table` ON $on";
            }
        }

        $query .= ' ';
        $params = [];

        // SETS
        if ($action == 'insert') {

            $query .= '(';

            $count = 0;
            foreach($this->sets as $k => $v) {
                $query .= $k;

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
                    $query .= "$k = :p_$counter";
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

        }

        // WHEREs
        if ( (sizeof($this->wheres) && sizeof($this->wheres[0])) 
            && $action != 'insert') {

            $query .= ' WHERE (';

            $first = true;
            foreach($this->wheres as $conditions) {

                if (!$first) {
                    $query .= ') OR (';
                }

                $count = 0;
                foreach($conditions as $condition) {

                    if (!isset($condition['type'])) continue;
                    $type = $condition['type'];

                    if ($type == 'simple') {
                        $col = $condition['column'];
                        $op = $condition['operator'];
                        $v = $condition['value'];

                        $query .= "$col $op :p_$counter";
                        $params["p_$counter"] = $v;
                        ++ $counter;
                        $first = false;
                    }

                    else if ($type == 'wherein') {
                        $col = $condition['column'];
                        $in = $condition['in'];
                        $sub = $condition['subquery'];
                        $sub = $sub->buildQuery('select', $counter + 1);

                        $counter = $sub[2];
                        $query .= "$col " . ($in ? 'IN ' : 'NOT IN ');
                        $query .= $sub[0];

                        // Params
                        foreach($sub[1] as $k => $v) {
                            $params[$k] = $v;
                        }
                    }

                    else if ($type == 'raw') {
                        $q = $condition['query'];
                        $pms = $condition['params'];

                        $query .= $q;
                        foreach($pms as $k => $v) {
                            $params[$k] = $v;
                        }
                    }

                    ++ $count;

                    if ($count < sizeof($conditions)) {
                        $query .= ' AND ';
                    }

                }

            }

            $query .= ') ';
        }

        // EXTRAS
        if ($action == 'select') {

            if ($this->group != '') {
                $query .= " GROUP BY $this->group";

                if ($this->having != '') {
                    $query .= " HAVING $this->having";
                }
            }

            if ($this->order != '') {
                $query .= " ORDER BY $this->order";
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