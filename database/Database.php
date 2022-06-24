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
    public function run(string $query, array $parameters = []) : DBQueryResult {
        
        if (!isset($this->{'db'})) {
            throw new FrameworkException('Database not connected', 'Database connection not stablished. Please check your credentials in config.json');
        }

        $q = $this->prepareQuery($query, $parameters);
        
        try {
            $result = $this->db->query( $q );
        } catch(Exception $e) {
            throw new FrameworkException("SQL Query error: `$q`, ERROR: " . ($this->db->error ?? 'unknown, possibly foreign keys.'), $q);
        }

        $result = $result == null ? [] : $result->fetchAll();

        return new DBQueryResult($q, $this->db->error, $result);
    }

    /**
     * Create a Query object for a table
     * 
     * @param string $table
     * 
     * @return DBQuery
     */
    public function query(string $table) : DBQuery {
        return new DBQuery($table);
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
        
        $res = $this->run($query, $parameters);
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
        
        $res = $this->run($query, $parameters);
        return $res->something ? $res->first : null;
    }

    /**
     * Check if table exists.
     * 
     * @param string $table
     * 
     * @return bool
     */
    public function tableExists(string $table) : bool {
        $db = $this->dbname;
        
        $q = $this->run("SELECT COUNT(*) AS x FROM information_schema.TABLES WHERE (TABLE_SCHEMA = '$db') AND (TABLE_NAME='$table')");

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
     * Create tables using DBGen definition.
     * 
     * @param array $tables
     */
    public function createTables(array $tables) {

        foreach($tables as $table)
        {
            $this->createTable($table);
        }
    
    }
    
    /**
     * Create a single table using DBGen definition.
     * 
     * @param array $table
     * 
     * @return DBQueryResult.
     */
    public function createTable(array $table) : DBQueryResult {

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
        $res = $this->run($q);

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

    /**
     * Generate a DBTable object for a table.
     * 
     * @param string $name
     * 
     * @return DBTable
     */
    public static function table(string $name) : DBTable {
        return new DBTable($name);
    }


}