<?php

class DB {

    /**
     * Trigger autoload to use Database classes.
     * Nothing else.
     */
    public static function connect() {
        // Nothing, just triggers autoload
    }

    /**
     * Make a query to the current connected database
     * 
     * @param string $query
     * @param array $parameters [Optional]
     * 
     * @return DBQueryResult
     */
    public static function run(string $query, array $parameters = []) : DBQueryResult {

        global $DATABASE;
        if ($DATABASE == null) return DBQueryResult::empty();
        return $DATABASE->run($query, $parameters);

    }

    /**
     * Create a Query object for a table
     * 
     * @param string $table
     * 
     * @return DBQuery
     */
    public static function query(string $table) : DBQuery {
        return new DBQuery($table);
    }

    /**
     * Get the returned rows of a query to the current connected Database.
     * 
     * @param string $query
     * @param array $parameters [Optional]
     * 
     * @return Generic[]
     */
    public static function result(string $query, array $parameters = []) : array {

        global $DATABASE;
        if ($DATABASE == null) return [];
        return $DATABASE->result($query, $parameters);

    }

    /**
     * Get a single row (first found)
     * 
     * @param string $query
     * @param array $parameters
     * 
     * @return Generic
     */
    public static function row(string $query, array $parameters = []) {
        
        global $DATABASE;
        if ($DATABASE == null) return [];
        return $DATABASE->row($query, $parameters);
    }

    /**
     * Check if table exists in the current connected database.
     * 
     * @param string $table
     * 
     * @return bool
     */
    public static function tableExists(string $table) : bool {
        global $DATABASE;
        if ($DATABASE == null) return false;
        return $DATABASE->tableExists($table);
    }

    /**
     * Count rows in the current connected database.
     * 
     * @param string $table
     * @param string $where
     * @param array $parameters [Optional]
     * 
     * @return int
     */
    public static function count(string $table, string $where = null, array $parameters = []) : int {
        global $DATABASE;
        if ($DATABASE == null) return 0;
        return $DATABASE->count($table, $where, $parameters);
    }

    /**
     * Make a select query on the current connected database.
     * 
     * @param array $options [columns, table, where, limit, offset]
     * @param array $parameters
     * 
     * @return DBQueryResult
     */
    public static function select(string $table, $columns = '*', array $where = [], $options = []) : array {
        global $DATABASE;
        if ($DATABASE == null) return DBQueryResult::empty();
        return $DATABASE->select($table, $columns, $where, $options);
    }

    /**
     * Get a single value from the current connected database.
     * 
     * @param string $value Column name.
     * @param string $table
     * @param string $where
     * @param array $parameters [Optional]
     * 
     * @return mixed
     */
    public static function value(string $value, string $table, string $where, array $parameters = []) {
        global $DATABASE;
        if ($DATABASE == null) return null;
        return $DATABASE->value($value, $table, $where, $parameters);
    }

    /**
     * Delete rows from a table
     * 
     * @param string $table
     * @param string $where [Optional]
     * @param array $parameters [Optional]
     * 
     * @return DBQueryResult
     */
    public static function delete(string $table, array $where = [], array $parameters = []) : DBQueryResult  {
        global $DATABASE;
        if ($DATABASE == null) return DBQueryResult::empty();
        return $DATABASE->delete($table, $where, $parameters);
    }

    /**
     * Insert a new row into a table
     * 
     * @param string $table
     * @param array $columns
     * 
     * @return DBQueryResult
     */
    public static function insert(string $table, array $columns = []) : DBQueryResult  {
        global $DATABASE;
        if ($DATABASE == null) return DBQueryResult::empty();
        return $DATABASE->insert($table, $columns);
    }

    /**
     * Update rows in a table
     * 
     * @param string $table
     * @param array $where
     * @param array $values
     * 
     * @return DBQueryResult
     */
    public static function update(string $table, array $where, array $values) : DBQueryResult {
        global $DATABASE;
        if ($DATABASE == null) return DBQueryResult::empty();
        return $DATABASE->update($table, $where, $values);
    }

    /**
     * Create tables using DBGen definition.
     * 
     * @param array $tables
     */
    public static function createTables(array $tables) {
        global $DATABASE;
        if ($DATABASE == null) return;
        $DATABASE->createTables($tables);
    }
    
    /**
     * Create a single table using DBGen definition.
     * 
     * @param array $table
     */
    public static function createTable(array $table) {
        global $DATABASE;
        if ($DATABASE == null) return;
        $DATABASE->createTable($table);
    }

    /**
     * Generates a .sql backup.
     * 
     * @param string $output file
     * @param array Options
     */
    public static function backup(string $output, array $options = []) {
        global $DATABASE;
        if ($DATABASE == null) return;
        $DATABASE->backup($output, $options);
    }

    /**
     * Generate a DBTable object for a table.
     * 
     * @param string $name
     * 
     * @return DBTable
     */
    public static function table(string $name) {
        global $DATABASE;
        if ($DATABASE == null) return null;
        return $DATABASE->table($name);
    }

}