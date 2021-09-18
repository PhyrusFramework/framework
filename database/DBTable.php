<?php

class DBTable {

    private string $_name;

    function __construct(string $name) {
        $this->_name = $name;
    }

    static function instance(string $name) {
        return new DBTable($name);
    }

    public function drop() {
        return DB::query('DROP TABLE ' . $this->_name);
    }

    public function select($columns, $where = []) {
        $query = 'SELECT '; 

        if (is_array($columns)) {
            $keys = array_keys($columns);
            for($i = 0; $i < sizeof($keys); ++$i) {
                $query .= $keys[$i];
                if ($i < sizeof($keys) - 1) {
                    $query .= ', ';
                }
            }
        } else {
            $query .= $columns;
        }

        $query .= ' FROM ' . $this->_name . ' WHERE ';

        $keys = array_keys($where);
        for($i = 0; $i < sizeof($keys); ++$i) {
            $query .= $keys[$i] . ' ' . $where[$keys[$i]][0] . ' :' . $keys[$i];
            if ($i < sizeof($keys) - 1) {
                $query .= ' AND ';
            }
        }

        $params = [];
        foreach($where as $k => $arr) {
            $params[$k] = $arr[1];
        }

        $res = DB::query($query, $params);
        return $res->result;
    }

    public function insert($values) {
        $query = 'INSERT INTO ' . $this->_name . '(';

        $keys = array_keys($values);
        for($i = 0; $i < sizeof($keys); ++$i) {
            $query .= $keys[$i];
            if ($i < sizeof($keys) - 1) {
                $query .= ', ';
            }
        }
        
        $query .= ') VALUES (';
        for($i = 0; $i < sizeof($keys); ++$i) {
            $query .= ':' . $keys[$i];
            if ($i < sizeof($keys) - 1) {
                $query .= ', ';
            }
        }
        $query .= ')';

        return DB::query($query, $values);
    }

    public function update ($values, $where) {
        $query = 'UPDATE ' . $this->_name . ' SET ';

        $keys = array_keys($values);
        for($i = 0; $i < sizeof($keys); ++$i) {
            $query .= $keys[$i] . ' = :' . $keys[$i];
            if ($i < sizeof($keys) - 1) {
                $query .= ', ';
            }
        }

        $query .= ' WHERE ';

        $keys = array_keys($where);
        for($i = 0; $i < sizeof($keys); ++$i) {
            $query .= $keys[$i] . ' ' . $where[$keys[$i]][0] . ' :' . $keys[$i];
            if ($i < sizeof($keys) - 1) {
                $query .= ' AND ';
            }
        }

        $params = [];
        foreach($where as $k => $arr) {
            $params[$k] = $arr[1];
        }

        return DB::query($query, $params);
    }

    public function delete ($where) {
        $query = 'DELETE FROM ' . $this->_name . ' WHERE ';

        $keys = array_keys($where);
        for($i = 0; $i < sizeof($keys); ++$i) {
            $query .= $keys[$i] . ' ' . $where[$keys[$i]][0] . ' :' . $keys[$i];
            if ($i < sizeof($keys) - 1) {
                $query .= ' AND ';
            }
        }

        $params = [];
        foreach($where as $k => $arr) {
            $params[$k] = $arr[1];
        }

        return DB::query($query, $params);
    }

}