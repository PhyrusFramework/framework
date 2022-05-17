<?php

class DBQuery {

    private $db;

    private $table = '';

    private $selects = [];

    private $wheres = [];
    private $whereins = [];

    private $sets = [];

    private $join = '';

    private $limit = 0;
    private $offset = 0;

    private $order = '';
    private $group = '';
    private $having = '';

    function __construct(string $table, $db = null) {
        global $DATABASE;
        $this->db = $db == null ? $DATABASE : $db;
        $this->table = $table;
    }

    public static function instance(string $table, $db = null) {
        return new DBQuery($table, $db);
    }

    public function select(...$columns) {
        foreach($columns as $col) {
            $this->selects[] = $col;
        }
        return $this;
    }

    public function limit(int $limit) {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset) {
        $this->offset = $offset;
        return $this;
    }

    public function orderBy(string $order) {
        $this->order = $order;
        return $this;
    }

    public function groupBy(string $group) {
        $this->group = $group;
        return $this;
    }

    public function having(string $having) {
        $this->having = $having;
        return $this;
    }

    public function where($column, $valueOrOperator = '=', $value = null) {

        if (is_array($column)) {
            $w = [];
            foreach($column as $c => $condition) {
                $w[$c] = is_array($condition) ? [$condition[0], $condition[1]] : ['=', $condition];
            }

            $this->wheres[] = $w;
        }

        $v = $value == null ? ['=', $valueOrOperator] : [$valueOrOperator, $value];
        $this->wheres[$column] = $v;
        return $this;

    }

    public function whereIn(string $column, DBQuery $subquery) {
        $this->whereins[] = [
            'in' => true,
            'column' => $column,
            'subquery' => $subquery
        ];
    }

    public function whereNotIn(string $column, DBQuery $subquery) {
        $this->whereins[] = [
            'in' => true,
            'column' => $column,
            'subquery' => $subquery
        ];
    }

    public function set(string $column, $value) {
        $this->sets[$column] = $value;
        return $this;
    }

    public function join(string $table, string $on) {
        $this->join = "JOIN $table ON $on";
    }

    public function get() {
        $query = $this->buildQuery('select');
        return $this->db->run($query[0], $query[1])->result;
    }

    public function first() {
        $this->limit(1);
        $query = $this->buildQuery('select');
        $res = $this->db->run($query[0], $query[1])->result;
        if (sizeof($res) == 0) return null;
        return $res[0];
    }

    public function delete() {
        $query = $this->buildQuery('delete');
        return $this->db->run($query[0], $query[1]);
    }

    public function insert() {
        $query = $this->buildQuery('insert');
        return $this->db->run($query[0], $query[1]);
    }

    public function update() {
        $query = $this->buildQuery('update');
        return $this->db->run($query[0], $query[1]);
    }

    public function count() {
        $query = $this->buildQuery('count');
        return $this->db->run($query[0], $query[1]);
    }

    public function buildQuery(string $action) {

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
                    $query .= $this->selects[$i];

                    if ($i < sizeof($this->selects) - 1) {
                        $query .= ', ';
                    }
                }
            }
        } else if ($action == 'count') {
            $query .= ' COUNT(*)';
        }

        // TABLE NAME
        if ($action == 'insert') {
            $query .= " INTO $this->table";
        } else if ($action == 'update') {
            $query .= ' ' . $this->table;
        } else {
            $query .= " FROM $this->table";
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
                    $count .= ', ';
                }

                ++ $count;
            }

            $query .= ') VALUES (';

            $count = 0;
            foreach($this->sets as $k => $v) {
                $query .= ":$k";
                $params[$k] = $v;

                if ($count < sizeof($this->sets) - 1) {
                    $count .= ', ';
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
                $query .= "$k = :$k";
                $params[$k] = $v;

                if ($count < sizeof($this->sets) - 1) {
                    $count .= ', ';
                }

                ++ $count;
            }

        }

        // WHEREs
        if (sizeof($wheres) > 0 && $action != 'insert') {

            $query .= ' WHERE ';

            $count = 0;
            foreach($this->wheres as $k => $where) {

                // AND where: name => ['=', $name]
                if (!is_array($where[0])) {

                    $operator = $where[0];
                    $value = $where[1];

                    $query .= "$k $operator :w_$k";
                    $params["w_$k"] = $value;
                }

                // OR where: [ 'name' => ['=', $name], 'email' => ['=', $email] ]
                else {

                    $query .= '(';
                    $count2 = 0;
                    foreach($where as $c => $or_condition) {

                        $operator = $or_condition[0];
                        $value = $or_condition[1];

                        $query .= "$k $operator :w_$k";
                        $params["w_$k"] = $value;

                        if ($count2 < sizeof($where) - 1) {
                            $query .= ' OR ';
                        }

                        ++ $count2;
                    }
                    $query .= ')';

                }

                if ($count < sizeof($this->wheres) - 1) {
                    $query .= ' AND ';
                }
                ++ $count;

            }

        }

        // WHEREINS

        if (sizeof($this->whereins) > 0 && $action != 'insert') {

            if (sizeof($this->wheres) > 0) {
                $query .= ' AND ';
            }

            foreach($this->whereins as $w) {

                $sq = $w['subquery']->buildQuery();
                $s = $sq[0];
                $p = $sq[1];

                $query .= $w['column'] . ' ' . ($w['in'] ? 'IN' : 'NOT IN') . " ($s)";

                foreach($p as $k => $v) {
                    $params[$k] = $v;
                }

            }

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

        return [$query, $params];

    }

}