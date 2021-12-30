<?php

use Medoo\Medoo;

class DATABASE
{
    /**
     * [Managed by framework] Database object
     * 
     * @var Medoo $db
     */
    private Medoo $db;

    /**
     * [Managed by framework] Database credentials
     * 
     * @var Generic $credentials
     */
    private Generic $credentials;

    /**
     * [Managed by framework] Database name
     * 
     * @var string $dbname
     */
    private string $dbname;

    public function __construct($values)
    {
        $type = 'mysql';
        if (!empty($values['type']))
            $type = $values['type'];

        try {
            $this->dbname = $values['database'];

            $credentials = [
                'type' => $type,
                'host' => $values['host'] ?? 'localhost',
                'database' => $values['database'],
                'server' => $values['host'] ?? 'localhost',
                'username' => $values['username'],
                'password' => $values['password']
            ];
            $this->credentials = new Generic($credentials);

            $DATABASE = new Medoo($credentials);

            $this->db = $DATABASE;
        }
        catch(Exception $e)
        {
            return null;
        }
    }

    /**
     * Close database connection.
     */
    public function close_connection() {
        $this->db->pdo = null;
        $this->db = null;
    }

    /**
     * Make a query to the Database.
     * 
     * @param string $query
     * @param array $parameters [Optional]
     * 
     * @return DBQueryResult
     */
    public function query(string $query, array $parameters = []) : DBQueryResult {
        
        if (!isset($this->{'db'})) {
            throw new FrameworkException('Database not connected', 'Database connection not stablished. Please check your credentials in config.json');
        }

        $q = $this->prepareQuery($query, $parameters);
        $result = $this->db->query( $q );

        $result = $result == null ? [] : $result->fetchAll();

        return new DBQueryResult($q, $this->db->error, $result);
    }

    /**
     * Prepare a string to be inserted in the database.
     * 
     * @param string $value
     * @param bool $wrap [Default true] Wrap with single quotes?
     * 
     * @return string
     */
    public static function text(string $value, bool $wrap = true) : string {
        $v = str_replace("\\'", "'", $value);
        $v = e($v);
        $v = str_replace("'", "''", $v);
        return $wrap ? "'$v'" : $v;
    }

    /**
     * Prepares a variable to be inserted in a query depending on the type
     * and secures the value to avoid sql injections.
     * 
     * @param mixed $value
     * @param mixed quotes [Default true] Add quotes if is string
     * 
     * @return mixed
     */
    public static function prepare($value, $quotes = true) {
        
        $v = $value;

        if ($v === null) {
            return 'NULL';
        }
        if (is_bool($v)) {
            return $v ? '1' : '0';
        }
        if (is_numeric($v) || is_int($v) || is_float($v)) {
            return $v;
        }
        if ($v instanceof InsecureString) {
            return ($quotes ? "'" : '') . $v->getString() . ($quotes ? "'" : '');
        }
        if (is_string($v)) {
            return Database::text($v, $quotes);
        }
        if ($v instanceof JSON) {
            return Database::text($v->string(), $quotes);
        }
        if (is_array($value) || gettype($value) == 'Arr') {

            if (gettype($value) == 'Arr')
                $value = $value->getArray();
            
            $q = '(';
            for($i = 0; $i < sizeof($value); ++$i) {
                $q .= Database::prepare($value[$i]);

                if ($i < sizeof($value) - 1) {
                    $q .= ', ';
                }
            }
            $q .= ')';
            return $q;

        }

        return Database::text("$v", false);

    }

    /**
     * Insert parameters into the query.
     * 
     * @param string $query
     * @param array $parameters
     * 
     * @return string
     */
    private function prepareQuery(string $query, array $parameters = []) : string {

        $str = '';
        $current = '';
        $open = false;
        for($i = 0; $i < strlen($query); ++$i) {
            if (!$open) {
 
                if ($query[$i] == ':') {
                    $open = true;
                } else {
                    $str .= $query[$i];
                }

            } else {

                if (ctype_alpha($query[$i]) || $query[$i] == '_') {
                    $current .= $query[$i];
                } else {
                    if (!empty($current)) {
                        if (isset($parameters[$current])) {
                            $str .= Database::prepare($parameters[$current]);
                        } else {
                            $str .= ":$current";
                        }
                        $current = '';
                    }
                    $open = false;
                    $str .= $query[$i];
                }

            }
        }
        if ($open) {
            if (isset($parameters[$current])) {
                $str .= Database::prepare($parameters[$current]);
            } else {
                $str .= ":$current";
            }
            $open = false;
        }

        return $str;

    }

    /**
     * Make a query and get directly and array of results.
     * 
     * @param string $query
     * @param array $parameters
     * 
     * @return array
     */
    public function result(string $query, array $parameters = []) : array {
        
        $res = $this->query($query, $parameters);
        return $res->result;
    }

    /**
     * Get a single row (first found)
     * 
     * @param string $query
     * @param array $parameters
     * 
     * @return Generic
     */
    public function row(string $query, array $parameters = []) {
        
        $res = $this->query($query, $parameters);
        return $res->something ? $res->first : null;
    }

    /**
     * Check if table exists.
     * 
     * @param string $table
     * 
     * @return bool
     */
    public function table_exists(string $table) : bool {
        $db = $this->dbname;
        
        $q = $this->query("SELECT COUNT(*) AS x FROM information_schema.TABLES WHERE (TABLE_SCHEMA = '$db') AND (TABLE_NAME='$table')");

        if (sizeof($q->result) == 0) return false;

        try{
            $val = $q->result[0]->x;
            $val = intval($val);
            if ($val == 0) return false;
            return true;
        }
        catch(Exception $e)
        {
            return false;
        }
        return false;
    }

    /**
     * Make a select query.
     * 
     * @param string $table
     * @param array $options [columns, table, where, limit, offset]
     * @param array $parameters [Optional]
     * 
     * @return DBQueryResult
     */
    public function select(string $table, array $options = [], array $parameters = []) : DBQueryResult {
        $options = arr($options)->force([
            'columns' => '*',
            'where' => null,
            'limit' => null,
            'offset' => null
        ]);

        $q = 'SELECT ' . $options['columns'] . ' FROM ' . $table;
        
        if ($options['where'] != null) {
            $q .= ' WHERE ' . $options['where'];
        }

        if ($options['limit'] != null) {
            $q .= ' LIMIT ' . $options['limit'];
        }

        if ($options['offset'] != null) {
            $q .= ' OFFSET ' . $options['offset'];
        }

        return $this->query($q, $parameters);

    }

    /**
     * Get a single value.
     * 
     * @param string $value Columns name
     * @param string $table Table name
     * @param string $where Where condition (without WHERE)
     * @param array $parameters
     * 
     * @return mixed
     */
    public function value(string $value, string $table, string $where, array $parameters = []) {
        $q = "SELECT $value FROM $table WHERE $where";
        $res = $this->query($q, $parameters);

        return $res->something ? $res->first->{$value} : null;
    }

    /**
     * Count rows in a table.
     * 
     * @param string $table Table name
     * @param string $where [Optional]
     * @param array $parameters [Optional]
     * 
     * @return int
     */
    public function count(string $table, string $where = null, array $parameters = []) : int {

        $q = "SELECT COUNT(*) AS count FROM $table";
        if (!empty($where))
            $q .= " WHERE $where";

        $res = $this->query($q, $parameters);
        if (!$res->something) return 0;
        return intval($res->first->count);

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
    public function delete(string $table, string $where = null, array $parameters = []) {

        $q = "DELETE FROM $table";
        if (!empty($where)) {
            $q .= " WHERE $where";
        }

        return $this->query($q, $parameters);
    }

    /**
     * Insert a new row in the database
     * 
     * @param string $table
     * @param array $columns
     * 
     * @return DBQueryResult
     */
    public function insert(string $table, array $columns = []) {

        $q = "INSERT INTO $table (";
        $i = 0;
        foreach($columns as $k => $v) {
            $i += 1;
            $q .= $k;

            if ($i < sizeof($columns)) {
                $q .= ', ';
            } else {
                $q .= ') VALUES (';
            }
        }

        $i = 0;
        foreach($columns as $k => $v) {
            $i += 1;
            $q .= ":$k";

            if ($i < sizeof($columns)) {
                $q .= ', ';
            } else {
                $q .= ')';
            }
        }

        return $this->query($q, $columns);
    }
    
    /**
     * Update rows in the database.
     * 
     * @param string $table
     * @param array $where Columns and values
     * @param array $values Columns and values
     * 
     * @return DBQueryResult
     */
    public function update(string $table, array $where, array $values) {

        $q = "UPDATE $table SET ";

        $parameters = [];
        
        $i = 0;
        foreach($values as $k => $v) {
            $parameters["value_$k"] = $v;
            $i += 1;

            $q .= "$k = :value_$k";

            if ($i < sizeof($values)) {
                $q .= ', ';
            } else {
                $q .= ' WHERE ';
            }
        }

        $i = 0;
        foreach($where as $k => $v) {
            $parameters["where_$k"] = $v;
            $i += 1;

            $q .= "$k = :where_$k";

            if ($i < sizeof($where)) {
                $q .= ' AND ';
            }
        }

        return $this->query($q, $parameters);

    }

    /**
     * Create tables using DBGen definition.
     * 
     * @param array $tables
     */
    public function create_tables(array $tables) {

        foreach($tables as $table)
        {
            $this->create_table($table);
        }
    
    }
    
    /**
     * Create a single table using DBGen definition.
     * 
     * @param array $table
     * 
     * @return DBQueryResult.
     */
    public function create_table(array $table) : DBQueryResult {

        $name = $table['name'];
        $q = "CREATE TABLE $name (";

        $primary = '';
        foreach($table['columns'] as $col) {
            if (!empty($col['primary'])) {
                if (!$primary == '') {
                    $primary .= ', ';
                }
                $primary .= $col['name'];
            }
        }

        if (empty($primary))
            $q .= 'ID BIGINT NOT NULL AUTO_INCREMENT, ';

        $uniques = [];
        $foreign = [];
        foreach($table['columns'] as $field)
        {
            $fname = $field['name'];
            $type = isset($field['type']) ? $field['type'] : 'BIGINT';
            
            $q .= "$fname $type";
    
            if (!empty($field['notnull']) && $field['notnull'])
                $q .= ' NOT NULL';

            if (!empty($field['auto_increment']) && $field['auto_increment'])
                $q .= ' AUTO_INCREMENT ';
    
            if (!empty($field['unique']) && $field['unique'])
                $uniques[] = $fname;
    
            if (!empty($field['foreign']))
            {
                $foreign[] = [
                    'field' => $fname,
                    'reference' => $field['foreign']
                ];
            }
    
            $q .= ', ';
        }
        foreach($uniques as $u)
        {
            $q .= "UNIQUE($u), ";
        }

        if (empty($primary))
            $q .= 'PRIMARY KEY(ID)';
        else
            $q .= "PRIMARY KEY($primary)";
    
        foreach($foreign as $f)
        {
            $q .= ', FOREIGN KEY(' . $f['field'] . ') REFERENCES ' . $f['reference'];
        }
    
        $q .= ');';
        $res = $this->query($q);

        return $res;
    }

    /**
     * Generates a .sql backup.
     * 
     * @param string $output file
     * @param array Options
     */
    public function backup(string $output, array $options = []) {

        $def = [
            'host' => $this->credentials->host,
            'username' => $this->credentials->username,
            'password' => $this->credentials->password,
            'database' => $this->credentials->database,
            'charset' => $options['charset'] ?? 'utf8',
            'file' => $output,
            'zip' => $options['zip'] ?? false,
            'disableForeignKeyChecks' => true,
            'batchSize' => $options['batchSize'] ?? 500
        ];
    
        $arr = arr($def)->force($options)->getArray();
    
        $backupDatabase = new Backup_Database($arr);
        $backupDatabase->backupTables($options['tables'] ?? '*');
    }


}