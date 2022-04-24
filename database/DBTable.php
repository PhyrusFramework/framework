<?php

class DBTable {

    /**
     * @var string
     * 
     * Table name
     */
    private string $_name;

    /**
     * @var Database
     * 
     * Database to operate
     */
    private Database $db;

    function __construct(string $name, Database $db = null) {
        $this->_name = $name;
        global $DATABASE;
        $this->db = $db ? $db : $DATABASE;
    }

    /**
     * Create a DBTable instance.
     * 
     * @return DBTable
     */
    static function instance(string $name, Database $db = null) : DBTable {
        return new DBTable($name, $db);
    }

    /**
     * Create a new table in the database
     * 
     * @param array $definition
     * @param Database
     * 
     * @return DBTable
     */
    public static function create(array $definition, Database $db = null) : ?DBTable {
        if (!isset($definition['name'])) return null;

        global $DATABASE;
        $D = $db ? $db : $DATABASE;

        $D->createTable($definition);
        return DBTable::instance($definition['name'], $db);
    }

    /**
     * Delete this table.
     * 
     * @return DBQueryResult
     */
    public function drop() : DBQueryResult {
        return $this->db->query('DROP TABLE ' . $this->_name);
    }

    /**
     * Add a new column to the table.
     * 
     * @param array Column definition
     * @param string After other column
     * 
     * @return DBQueryResult
     */
    public function addColumn(array $definition) : ?DBQueryResult {

        $req = ['name', 'type'];
        foreach($req as $r) {
            if (!isset($definition[$r])) {
                return null;
            }
        }

        $name = $definition['name'];

        $q = "ALTER TABLE $this->_name ADD $name" . ' ' . $definition['type'];
        $q .= isset($definition['notnull']) ? ' NOT NULL' : '';
        $q .= isset($definition['auto_increment']) ? ' AUTO_INCREMENT' : '';

        if (isset($definition['position'])) {
            $q .= ' ' . $definition['position'];
        }

        $res = $this->db->query($q);

        if (!empty($definition['unique'])) {
            $this->db->query("ALTER TABLE $this->_name ADD CONSTRAINT $this->_name"."_$name"."_unique UNIQUE ($name)");
        }

        if (!empty($definition['foreign'])) {
            $this->db->query("ALTER TABLE $this->_name ADD FOREIGN KEY ($name) REFERENCES " . $definition['foreign']);
        }

        return $res;
    }

    /**
     * Drop table column
     * 
     * @param string Column name
     * 
     * @return DBQueryResult
     */
    public function dropColumn(string $name) : DBQueryResult {
        return $this->db->query("ALTER TABLE $this->_name DROP $name");
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

        $res = $this->db->query($query, $params);
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

        return $this->db->query($query, $values);
    }

    /**
     * Update table rows.
     * 
     * @param array $values
     * @param array $where
     * 
     * @return DBQueryResult
     */
    public function update (array $values, array $where = []) : DBQueryResult {
        $query = 'UPDATE ' . $this->_name . ' SET ';

        $keys = array_keys($values);
        for($i = 0; $i < sizeof($keys); ++$i) {
            $query .= $keys[$i] . ' = :' . $keys[$i];
            if ($i < sizeof($keys) - 1) {
                $query .= ', ';
            }
        }

        $params = [];
        foreach($values as $k => $v) {
            $params[$k] = $v;
        }

        if (sizeof($where) > 0) {
            $query .= ' WHERE ';

            $keys = array_keys($where);
            for($i = 0; $i < sizeof($keys); ++$i) {
                $query .= $keys[$i] . ' ' . $where[$keys[$i]][0] . ' :' . $keys[$i];
                if ($i < sizeof($keys) - 1) {
                    $query .= ' AND ';
                }
            }

            foreach($where as $k => $arr) {
                $params[$k] = $arr[1];
            }
        }

        return $this->db->query($query, $params);
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

        return $this->db->query($query, $params);
    }

    /**
     * Delete all rows from table.
     * 
     * @return DBQueryResult
     */
    public function empty() : DBQueryResult {
        return $this->db->query("DELETE FROM $this->_name");
    }

}