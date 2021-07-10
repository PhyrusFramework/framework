<?php

class DB {

    /**
     * Make a query to the current connected database
     * 
     * @param string $query
     * @param array $parameters [Optional]
     * 
     * @return DBQueryResult
     */
    public static function query(string $query, array $parameters = []) : DBQueryResult {

        global $DATABASE;
        if ($DATABASE == null) return DBQueryResult::empty();
        return $DATABASE->query($query, $parameters);

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
    public static function table_exists(string $table) : bool {
        global $DATABASE;
        if ($DATABASE == null) return false;
        return $DATABASE->table_exists($table);
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
    public static function select(string $table, array $options = [], array $parameters = []) : DBQueryResult {
        global $DATABASE;
        if ($DATABASE == null) return DBQueryResult::empty();
        return $DATABASE->select($table, $options, $parameters);
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
    public static function delete(string $table, string $where = null, array $parameters = []) : DBQueryResult  {
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
    public static function create_tables(array $tables) {
        global $DATABASE;
        if ($DATABASE == null) return;
        $DATABASE->create_tables($tables);
    }
    
    /**
     * Create a single table using DBGen definition.
     * 
     * @param array $table
     */
    public static function create_table(array $table) {
        global $DATABASE;
        if ($DATABASE == null) return;
        $DATABASE->create_table($table);
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

}