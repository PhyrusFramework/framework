<?php

class DBTable {

    /**
     * @var string
     * 
     * Table name
     */
    private string $_name;

    function __construct(string $name) {
        $this->_name = $name;
    }

    /**
     * Create a DBTable instance.
     * 
     * @return DBTable
     */
    static function instance(string $name) : DBTable {
        return new DBTable($name);
    }

    public static function create(array $definition) : ?DBTable {
        if (!isset($definition['name'])) return null;
        DB::create_table($definition);
        return DBTable::instance($definition['name']);
    }

    /**
     * Delete this table.
     * 
     * @return DBQueryResult
     */
    public function drop() : DBQueryResult {
        return DB::query('DROP TABLE ' . $this->_name);
    }

    /**
     * Add a new column to the table.
     * 
     * @param string Column name
     * @param string Column type
     * @param string After other column
     * 
     * @return DBQueryResult
     */
    public function addColumn(string $name, string $type, $after = null) : DBQueryResult {
        return DB::query("ALTER TABLE $this->_name ADD $name $type " . 
            ($after == null ? '' : ($after == 'FIRST' ? 'FIRST' : "AFTER $after") ));
    }

    /**
     * Drop table column
     * 
     * @param string Column name
     * 
     * @return DBQueryResult
     */
    public function dropColumn(string $name) : DBQueryResult {
        return DB::query("ALTER TABLE $this->_name DROP $name");
    }

    /**
     * Select columns from this table.
     * 
     * @param mixed $columns
     * @param array $where
     * 
     * @return array
     */
    public function select($columns, $where = [], $options = []) : array {
        $query = 'SELECT '; 

        if (is_array($columns)) {

            for($i = 0; $i < sizeof($columns); ++$i) {
                $query .= $columns[$i];

                if ($i < sizeof($columns) - 1) {
                    $query .= ', ';
                }
            }
        } else {
            $query .= $columns;
        }

        $query .= ' FROM ' . $this->_name . ' WHERE ';

        $keys = array_keys($where);
        for($i = 0; $i < sizeof($keys); ++$i) {

            $operator = '=';
            if (is_array($where[$keys[$i]])) {
                $operator = $where[$keys[$i]][0];
            }

            $query .= $keys[$i] . " $operator :" . $keys[$i];
            if ($i < sizeof($keys) - 1) {
                $query .= ' AND ';
            }
        }

        $params = [];
        foreach($where as $k => $arr) {
            if (is_array($arr))
            $params[$k] = $arr[1];
            else
            $params[$k] = $arr;
        }

        $operations = [
            'groupby' => ' GROUP BY {{param}}',
            'orderby' => ' ORDER BY {{param}}',
            'offset' => ' OFFSET {{param}}',
            'limit' => ' LIMIT {{param}}'
        ];

        foreach($operations as $op => $com) {
            if (isset($options[$op])) {
                $query .= str_replace('{{param}}', $options[$op], $com);
            }
        }

        $res = DB::query($query, $params);
        return $res->result;
    }

    /**
     * Insert new values into the table.
     * 
     * @param array $values
     * 
     * @return DBQueryResult
     */
    public function insert(array $values) : DBQueryResult {
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

    /**
     * Update table rows.
     * 
     * @param array $values
     * @param array $where
     * 
     * @return DBQueryResult
     */
    public function update (array $values, array $where) : DBQueryResult {
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

    /**
     * Delete rows from this table.
     * 
     * @param array $where
     * 
     * @return DBQueryResult
     */
    public function delete (array $where) : DBQueryResult {
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