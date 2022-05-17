<?php

class DBBuilder {

    /**
     * Table definition
     * 
     * @var array
     */
    private array $definition = [];

    /**
     * Last inserted column
     */
    private $lastColumn = null;

    /**
     * Create a DBBuilder instance
     * 
     * @return DBBuilder
     */
    public static function instance() : DBBuilder {
        return new DBBuilder();
    }

    function __construct() {
        $this->definition = [
            'name' => '',
            'columns' => []
        ];
    }

    /**
     * Set the name of the table.
     * 
     * @param string $name
     * 
     * @return DBBuilder
     */
    public function name(string $name) : DBBuilder {
        $this->definition['name'] = $name;
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
        $this->definition['columns'][$name] = [
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
        $this->definition['columns'][$this->lastColumn]['primary'] = true;
        return $this;
    }

    /**
     * Make the last column auto increment.
     * 
     * @return DBBuilder
     */
    public function autoIncrement() : DBBuilder {
        if (!$this->lastColumn) return $this;
        $this->definition['columns'][$this->lastColumn]['auto_increment'] = true;
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
        $this->definition['columns'][$this->lastColumn]['default'] = $value;
        return $this;
    }

    /**
     * Make the last column nullable.
     * 
     * @return DBBuilder
     */
    public function nullable() : DBBuilder {
        if (!$this->lastColumn) return $this;
        $this->definition['columns'][$this->lastColumn]['notnull'] = false;
        return $this;
    }

    /**
     * Make the last column unique.
     * 
     * @return DBBuilder
     */
    public function unique() : DBBuilder {
        if (!$this->lastColumn) return $this;
        $this->definition['columns'][$this->lastColumn]['unique'] = true;
        return $this;
    }

    /**
     * Specify that this column must not be used when serializing
     */
    public function notSerializable() {
        if (!$this->lastColumn) return $this;
        $this->definition['columns'][$this->lastColumn]['serialize'] = false;
        return $this;
    }

    /**
     * Make the last column a foreign key to another table's column.
     * 
     * @param string table(column)
     * 
     * @return DBBuilder
     */
    public function references(string $tableColumn) : DBBuilder {
        if (!$this->lastColumn) return $this;
        $this->definition['columns'][$this->lastColumn]['foreign'] = $tableColumn; 
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
        return DB::createTable($this->definition);
    }

}