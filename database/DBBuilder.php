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
    public static function instance() : DBBuilder {
        return new DBBuilder();
    }

    function __construct() {
        $this->definition = [];
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
        $this->definition[$this->name][$name] = [
            'name' => $name,
            'type' => $type,
            'notnull' => true
        ];
        $this->lastColumn = $name;
        return $this;
    }

    /**
     * Alias for column method
     * 
     * @param string column name
     * @param string column type
     * 
     * @return DBBuilder
     */
    public function col(string $name, string $type = 'VARCHAR(200)') : DBBuilder {
        return $this->column($name, $type);
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
            $t .= '(' . ($column ? $column : 'ID') . ')';
        }
        $this->definition[$this->name][$this->lastColumn]['foreign'] = $t;
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

}