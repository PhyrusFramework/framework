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
        return $this->db->run('DROP TABLE ' . $this->_name);
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

        $res = $this->db->run($q);

        if (!empty($definition['unique'])) {
            $this->db->run("ALTER TABLE $this->_name ADD CONSTRAINT $this->_name"."_$name"."_unique UNIQUE ($name)");
        }

        if (!empty($definition['foreign'])) {
            $this->db->run("ALTER TABLE $this->_name ADD FOREIGN KEY ($name) REFERENCES " . $definition['foreign']);
        }

        return $res;
    }

    public function query() : DBQuery {
        return new DBQuery($this->_name);
    }

    /**
     * Delete all rows from table.
     * 
     * @return DBQueryResult
     */
    public function empty() : DBQueryResult {
        return $this->db->run("DELETE FROM $this->_name");
    }

    /**
     * Check if this table exists
     * 
     * @return bool
     */
    public function exists() : bool {
        return DB::tableExists($this->_name);
    }

}