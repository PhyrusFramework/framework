<?php

class DBBuilder {

    /**
     * @var array Columns definition
     */
    private array $definition = [];

    /**
     * @var mixed Last inserted column
     */
    private $lastColumn = null;

    /**
     * @var string Current table name
     */
    private string $name = '';

    /**
     * Create a DBBuilder instance
     * 
     * @return DBBuilder
     */
    public static function instance($name = null) : DBBuilder {
        return new DBBuilder($name);
    }

    function __construct($name = null) {
        $this->definition = [];
        
        if ($name) {
            $this->name($name);
        }
    }

    /**
     * Set the name of the table.
     * 
     * @param string $name
     * 
     * @return DBBuilder
     */
    public function name(string $name) : DBBuilder {
        $this->name = $name;
        $this->definition[$name] = [];
        return $this;
    }

    /**
     * Specify a new column for the table.
     * 
     * @param string column name
     * @param string column type
     * 
     * @return DBBuilder
     */
    public function column(string $name, string $type = 'VARCHAR(255)') : DBBuilder {

        $t = strtolower($type) == 'bool' ? 'TINYINT(1)' : $type;

        $this->definition[$this->name][$name] = [
            'name' => $name,
            'type' => $t,
            'notnull' => true
        ];
        $this->lastColumn = $name;
        return $this;
    }

    /**
     * Alias for column method
     * 
     * @param string Column name
     * @param string Column type
     * 
     * @return DBBuilder
     */
    public function col(string $name, string $type = 'VARCHAR(200)') : DBBuilder {
        return $this->column($name, $type);
    }

    /**
     * Change the name of the last column.
     * 
     * @param string Column name
     * 
     * @return DBBuilder
     */
    public function columnName(string $name) : DBBuilder {
        if (!$this->lastColumn) return $this;

        $obj = $this->definition[$this->name][$this->lastColumn];
        $obj['name'] = $name;

        $this->definition[$this->name][$this->lastColumn] = null;
        unset($this->definition[$this->name][$this->lastColumn]);

        $this->definition[$this->name][$name] = $obj;

        $this->lastColumn = $name;
        return $this;
    }

    /**
     * Set last column type
     * 
     * @return DBBuilder
     */
    public function type(string $type) : DBBuilder {
        if (!$this->lastColumn) return $this;
        $t = strtolower($type) == 'bool' ? 'TINYINT(1)' : $type;
        $this->definition[$this->name][$this->lastColumn]['type'] = $t;
        return $this;
    }

    /**
     * Add a column with the format of an ID
     * 
     * @param string column name
     * 
     * @return DBBuilder
     */
    public function idColumn(string $name = 'ID') : DBBuilder {
        return $this->column($name, 'BIGINT')->unsigned();
    }

    /**
     * Make the last column a primary key.
     * 
     * @return DBBuilder
     */
    public function primary() : DBBuilder {
        if (!$this->lastColumn) return $this;
        $this->definition[$this->name][$this->lastColumn]['primary'] = true;
        return $this;
    }

    /**
     * Make the last column auto increment.
     * 
     * @return DBBuilder
     */
    public function autoIncrement() : DBBuilder {
        if (!$this->lastColumn) return $this;
        $this->definition[$this->name][$this->lastColumn]['auto_increment'] = true;
        return $this;
    }

    /**
     * Set last column's default value
     * 
     * @param mixed $value
     * 
     * @return DBBuilder
     */
    public function default($value) : DBBuilder {
        if (!$this->lastColumn) return $this;
        $this->definition[$this->name][$this->lastColumn]['default'] = $value;
        return $this;
    }

    /**
     * Set last column number as unsigned
     * 
     * @return DBBuilder
     */
    public function unsigned() : DBBuilder {
        if (!$this->lastColumn) return $this;
        $this->definition[$this->name][$this->lastColumn]['unsigned'] = true;
        return $this;
    }

    /**
     * Make the last column nullable.
     * 
     * @return DBBuilder
     */
    public function nullable() : DBBuilder {
        if (!$this->lastColumn) return $this;
        $this->definition[$this->name][$this->lastColumn]['notnull'] = false;
        return $this;
    }

    /**
     * Make the last column unique.
     * 
     * @return DBBuilder
     */
    public function unique() : DBBuilder {
        if (!$this->lastColumn) return $this;
        $this->definition[$this->name][$this->lastColumn]['unique'] = true;
        return $this;
    }

    /**
     * Specify that this column must not be used when serializing.
     * 
     * @return DBBuilder
     */
    public function notSerializable() : DBBuilder {
        if (!$this->lastColumn) return $this;
        $this->definition[$this->name][$this->lastColumn]['serialize'] = false;
        return $this;
    }

    /**
     * Specify that this column must serialize another object.
     * 
     * @param string $references
     * 
     * @return DBBuilder
     */
    public function serializeRelation(string $references) : DBBuilder {
        if (!$this->lastColumn) return $this;
        $this->definition[$this->name][$this->lastColumn]['serializeRelation'] = $references;
        return $this;
    }

    /**
     * Make the last column a foreign key to another table's column.
     * 
     * @param string table
     * @param string column (otional)
     * 
     * @return DBBuilder
     */
    public function references(string $table, $column = null) : DBBuilder {
        if (!$this->lastColumn) return $this;
        $t = $table;
        if (strpos($t, '(') === FALSE) {
            $t = "`$t`(" . ($column ? "`$column`" : '`ID`') . ')';
        }
        $this->definition[$this->name][$this->lastColumn]['foreign'] = $t;
        return $this;
    }

    /**
     * Creates a column referencing the ID of another table.
     * 
     * @param string name
     * @param string table
     * 
     * @return DBBuilder
     */
    public function foreignId(string $name, string $table = '') : DBBuilder {
        $this->idColumn($name);
        $t = empty($table) ? str_replace('_id', 's', $name) : $table;
        $this->references($t);
        return $this;
    }

    /**
     * Allow HTML characters in this text column
     * 
     * @return DBBuilder
     */
    public function allowHTML() : DBBuilder {
        if (!$this->lastColumn) return $this;
        $this->definition[$this->name][$this->lastColumn]['allowHTML'] = true;
        return $this;
    }

    /**
     * Allow Javascript in a HTML column
     * 
     * @return DBBuilder
     */
    public function allowJs() : DBBuilder {
        if (!$this->lastColumn) return $this;
        $this->definition[$this->name][$this->lastColumn]['allowJs'] = true;
        return $this;
    }

    /**
     * Get the table definition as an array.
     * 
     * @return array
     */
    public function toArray() : array {
        return $this->definition;
    }

    /**
     * Get the table definition as a JSON string
     * 
     * @return string
     */
    public function toJSON() : string {
        return JSON::stringify($this->definition);
    }

    /**
     * Create this table in the current database.
     */
    public function execute() {
        return DB::createTables($this->definition);
    }

    /**
     * Parse DocComments from model attribute.
     * 
     * @param string DocComment
     * @param string Property
     * 
     * @return array
     */
    public function parseDoc(string $docComment, string $prop = '', array &$docsObject = []) : array {

        $classDocs = empty($prop);

        $start = strpos($docComment, '[DB');
        if ($start === FALSE) {
            return $docsObject;
        }

        $config = substr($docComment, $start + 3);

        // Start parsing

        if (!$classDocs) {
            $this->column($prop);
        }

        $propsWritten = 0;

        $inValue = false;
        $inParenthesis = 0;
        $currentKey = '';
        $currentValue = '';
        for($i = 0; $i < strlen($config); ++$i) {
            $c = $config[$i];

            if ($c == ']') {
                // End of [DB...
                break;
            }

            if (!$inValue) {
                
                if ($c == ' ') {
                    if ($currentKey != '') {
                        $propsWritten += 1;
                        $this->setPropertyForCaching($docsObject, $currentKey, true, $prop);
                        $this->resolveDocProperty($currentKey, true, $classDocs);
                        $currentKey = '';
                    }
                    continue;
                }
                if ($c == '=') {
                    $inValue = true;
                    continue;
                }
                $currentKey .= $c;

            } else {

                if ($inParenthesis == 0) {

                    if ($c == '(') {
                        $inParenthesis += 1;
                    }
                    else if ($c == ' ') {
                        $inValue = false;
                        $propsWritten += 1;
                        $this->setPropertyForCaching($docsObject, $currentKey, $currentValue, $prop);
                        $this->resolveDocProperty($currentKey, $currentValue, $classDocs);
                        $currentKey = '';
                        $currentValue = '';
                        continue;
                    }
                }
                else {
                    if ($c == ')') {
                        $inParenthesis -= 1;
                    }
                    else if ($c == '(') {
                        $inParenthesis += 1;
                    }
                }


                $currentValue .= $c;
                
            }
        }
        if ($currentKey != '') {
            $val = $currentValue == '' ? true : $currentValue;
            $propsWritten += 1;
            $this->setPropertyForCaching($docsObject, $currentKey, $val, $prop);
            $this->resolveDocProperty($currentKey, $val, $classDocs);
        }
        
        /**
         * If no property was written in the cache object, write at least one so it's noted,
         * otherwise this columns would not be created when using cached version.
         */
        if ($propsWritten == 0) {
            $this->setPropertyForCaching($docsObject, '_placeholder', true, $prop);
        }

        return $docsObject;
    }

    /**
     * Save the property into an array object so it can be cached later.
     * 
     * @param array Current array object to store the property
     * @param string Name of the key
     * @param string Value
     * @param string Name of the column. If empty, then it's a table setting.
     */
    private function setPropertyForCaching(array &$docsObject, string $key, $value, string $prop = '') {
        
        if (empty($prop)) {
            // It's a table setting
            $docsObject[$key] = $value;
        }
        else {
            // It's a column setting
            $cols = $docsObject['_columns'] ?? [];
            $propSettings = $cols[$prop] ?? [];
            $propSettings[$key] = $value;
            $cols[$prop] = &$propSettings;
            $docsObject['_columns'] = &$cols;
        }

    }

    /**
     * Use cached version of Model ORM table definition to define columns.
     * 
     * @param array|string Cached content.
     */
    public function resolveCachedObject(array|string $obj) {
        $arr = is_string($obj) ? JSON::parse($obj) : $obj;

        foreach($arr as $key => $val) {
            if ($key == '_columns') continue;
            $this->resolveDocProperty($key, $val[1], $val[0]);
        }

        if (isset($arr['_columns'])) {
            foreach($arr['_columns'] as $col => $settings) {
                $this->col($col);

                foreach($settings as $key => $val) {
                    if ($key == '_placeholder') continue;
                    $this->resolveDocProperty($key, $val);
                }
            }
        }
    }

    /**
     * Given a key and a value, do the corresponding operation on the table definition.
     * 
     * @param string Name of the property
     * @param string|bool Value
     * @param bool It's a table property?
     */
    public function resolveDocProperty(string $name, string|bool $value, $class = false) {

        if ($name == 'table') {
            $this->name($value);
        }
        else if ($name == 'name') {
            if (!$class) {
                $this->columnName($value);
            } else {
                $this->name($value);
            }
        }
        else if ($name == 'type') {
            $this->type($value);
        }
        else if ($name == 'unique') {
            $this->unique();
        }
        else if ($name == 'primary') {
            $this->primary();
        }
        else if ($name == 'autoIncrement') {
            $this->autoIncrement();
        }
        else if ($name == 'default') {
            $this->default(trim($value, "\""));
        }
        else if ($name == 'nullable') {
            $this->nullable();
        }
        else if ($name == 'unsigned') {
            $this->unsigned();
        }
        else if ($name == 'notSerializable') {
            $this->notSerializable();
        }
        else if ($name == 'references') {
            $this->references($value);
        }
        else if ($name == 'class') {
            $this->serializeRelation($value);
        }
        else if ($name == 'html') {
            $this->allowHTML();
        }
        else if ($name == 'js') {
            $this->allowJs();
        }

    }

}