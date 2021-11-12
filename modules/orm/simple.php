<?php

class ORM {

    /**
     * Check if table is checked.
     * 
     * @var bool $__table_checked
     */
    private bool $__table_checked = false;

    /**
     * Table definition.
     * 
     * @var array $__definition
     */
    private array $__definition = [];

    /**
     * ID
     * 
     * @var int $ID
     */
    public ?int $ID = 0;

    /**
     * Get the table definition.
     * 
     * @return array
     */
    protected function Definition() {
        return [
            'name' => get_called_class(),
            'columns' => []
        ];
    }

    public function __construct($ID = null) {
        $this->__inflate();
        if ($ID == null) return;

        if (is_object($ID)) {
            $this->__absorbObject($ID);
        }
        else {
            $res = DB::query('SELECT * FROM '.$this->getTable().' WHERE ID = :id', ['id' => $ID]);
            if (!$res->something) return;

            $this->__absorbObject($res->first);
        }

    }

    /**
     * Get the table name.
     * 
     * @return string
     */
    public function getTable() : string {
        return $this->__getDefinition()['name'];
    }

    /**
     * Get the column making reference to this model.
     * 
     * @return string
     */
    public function reference_column() {
        return strtolower($this->getTable()) . '_id';
    }

    /**
     * Assign default values.
     */
    private function __inflate() {
        $def = $this->__getDefinition();

        $this->ID = 0;
        $this->createdAt = datenow();
        foreach($def['columns'] as $col) {
            if (!isset($col['name'])) continue;
            $this->{$col['name']} = isset($col['default']) ? $col['default'] : '';
        }
    }

    /**
     * Absorb values from a DB result.
     * 
     * @param Generic $row
     */
    private function __absorbObject(Generic $row) {
        $cols = $this->__columns();
        foreach($cols as $col) {
            $name = $col['name'];
            $type = '_' . $col['type'];

            $this->{$name} = null;

            if (isset($row->{$name})) {

                $v = $row->{$name};
                if (strpos($type, 'INT'))
                    $v = intval($v);
                if (strpos($type, 'DECIMAL') || strpos($type, 'FLOAT') || strpos($type, 'DOUBLE'))
                    $v = floatval($v);

                $this->{$name} = $v;
            }
        }

        $this->ID = intval($this->ID);
    }

    /**
     * Get the table definition.
     * 
     * @return array
     */
    private function __getDefinition() : array {
        if ($this->__definition != null) return $this->__definition;

        $def = $this->Definition();
        if (!isset($def['columns'])) return [];
        if (isset($def['primary'])) unset($def['primary']);
        if (!isset($def['name'])) $def['name'] = get_called_class();

        $columns = $def['columns'];
        $columns[] = [
            'name' => 'createdAt',
            'type' => 'DATETIME',
            'notnull' => true
        ];

        $def['columns'] = $columns;
        $this->__definition = $def;
        return $this->__definition;
    }

    /**
     * Get table columns.
     * 
     * @return array
     */
    private function __columns() : array {

        $columns = $this->__getDefinition()['columns'];
        if ($columns == null) return [];
        array_unshift($columns, [
            'name' => 'ID',
            'type' => 'BIGINT',
            'notnull' => true,
            'auto_increment' => true
        ]);
        return $columns;
    }

    /**
     * Create table if not exists.
     * 
     * @return bool existed
     */
    public function CheckTable() : bool {
        return $this->__checkDB();
    }

    /**
     * Check if DB exists or create it.
     * 
     * @return bool existed
     */
    private function __checkDB() : bool {
        if ($this->__table_checked) return true;
        if (!Config::get('development_mode')) return false;

        if (!DB::table_exists($this->getTable())){
            DB::create_table($this->__getDefinition());
        }
        $this->__table_checked = true;
        return false;
    }

    /**
     * Find the ID by the time it was created.
     * 
     * @param string $creationTime
     * 
     * @return int
     */
    private function __findID(string $creationTime) : int {

        $res = DB::query('SELECT * FROM '.$this->getTable().' WHERE createdAt = :date ORDER BY ID DESC LIMIT 1', [
            'date' => $creationTime
        ]);

        if (!$res->something) return 0;
        $this->{'ID'} = intval($res->first->ID);
        return $this->ID;

    }

    /**
    * Has this model been already inserted?
     * 
     * @return bool
     */
    public function isCreated() : bool {
        return isset($this->{'ID'});
    }

    /**
     * Creation date.
     * 
     * @return string
     */
    public function creationDate() : string {
        if (!$this->isCreated()) return '';
        return $this->createdAt();
    }

    /**
     * Exists in the database?
     * 
     * @return bool
     */
    public function exists() : bool {
        if (!$this->isCreated()) return false;
        $res = DB::query('SELECT * FROM '.$this->getTable()." WHERE ID = $this->ID LIMIT 1");
        return $res->something;
    }

    /**
     * Delete.
     */
    public function delete() {
        if (!$this->isCreated()) return;
        DB::query('DELETE FROM '.$this->getTable()." WHERE ID = $this->ID");
    }

    /**
     * Create or update this object.
     * 
     * @param array ...$columns
     */
    public function save(...$columns) {
        $this->__checkDB();

        if ($this->exists()) {
            return $this->__update($columns);
        }
        else {
            return $this->__create();
        }
    }

    /**
     * Update.
     * 
     * @param array ...$columns
     */
    private function __update($columns = []) {

        $cols = [];

        foreach($columns as $col) {

            if (is_array($col)) {
                foreach($col as $c) {
                    $cols[] = $c;
                }
            } else {
                $cols[] = $col;
            }
        }

        if (sizeof($cols) == 0) {
            $cols = [];
            $colls = $this->__columns();
            foreach($colls as $col) {
                $cols[] = $col['name'];
            }
        }

        $q = 'UPDATE ' . $this->getTable() . ' SET ';
        $parameters = [];

        $i = 0;
        foreach($cols as $name) {
            if ($name == 'ID') {
                $i += 1;
                continue;
            }

            $q .= "$name = :$name";
            $parameters[$name] = $this->{$name};

            if ($i < sizeof($cols) - 1) {
                $q .= ', ';
            }
            $i += 1;
        }

        $q .= ' WHERE ID = :ID';
        $parameters['ID'] = $this->ID;

        DB::query($q, $parameters);
        return $this->ID;
    }

    /**
     * Create.
     */
    private function __create() {
        $q = 'INSERT INTO ' . $this->getTable() . ' (';

        $columns = $this->__columns();
        $i = 0;
        foreach($columns as $col) {
            if ($col['name'] == 'ID') {
                $i += 1;
                continue;
            }

            $q .= $col['name'];

            if ($i < sizeof($columns) - 1) {
                $q .= ', ';
            }
            $i += 1;
        }

        $q .= ') VALUES (';
        $parameters = [];

        foreach($columns as $col) {
            if ($col['name'] == 'ID') continue;
            if ($col['name'] == 'createdAt') continue;

            $q .= ':'.$col['name'].', ';

            $parameters[$col['name']] = $this->{$col['name']};
        }

        $q .= ' :createdAt);';

        $now = datenow();
        $parameters['createdAt'] = $now;

        $res = DB::query($q, $parameters);

        return $this->__findID($now);

    }

    ////////// Static methods

    /**
     * Get the table name.
     * 
     * @return string
     */
    public static function Table() : string {
        $cl = get_called_class();
        $sample = new $cl();
        return $sample->getTable();
    }

    /**
     * Find one object of this class.
     * 
     * @param string $where [Default none]
     * @param array $parameters [Default empty] Query parameters.
     * 
     * @return ORM
     */
    public static function findOne(string $where = '1', array $parameters = []) {

        $cl = get_called_class();
        $tmp = new $cl();
        $tmp->__checkDB();
        $q = 'SELECT * FROM ' . $tmp->getTable() . " WHERE $where LIMIT 1";

        $q = DB::query($q, $parameters);
        if ($q->something) {
            $o = new $cl($q->first);;
            return $o;
        }
        return null;
    }

    /**
     * Find objects of this class.
     * 
     * @param string $where [Default none]
     * @param array $parameters [Default empty] Query parameters
     * 
     * @return ORM[]
     */
    public static function find(string $where = '1', array $parameters = []) {

        $cl = get_called_class();
        $tmp = new $cl();
        $tmp->__checkDB();
        $q = 'SELECT * FROM ' . $tmp->getTable() . " WHERE $where";

        $q = DB::query($q, $parameters);
        $list = [];
        foreach($q->result as $row) {
            $list[] = new $cl($row);
        }
        return $list;
    }

    /**
     * Delete objects of this model.
     * 
     * @param string $where
     * @param array $parameters [Default empty] Query parameters.
     */
    public static function deleteWhere(string $where, array $parameters = []) {
        $cl = get_called_class();
        $tmp = new $cl();
        DB::query('DELETE FROM ' . $tmp->getTable() . " WHERE $where", $parameters);
    }

    /**
     * Count objects of this model.
     * 
     * @param string $where [Default none]
     * @param array $parameters [Default empty] Query parameters.
     * 
     * @return int
     */
    public static function count(string $where = '1', array $parameters = []) {
        $cl = get_called_class();
        $tmp = new $cl();
        $res = DB::query('SELECT COUNT(*) AS count FROM ' . $tmp->getTable() . " WHERE $where", $parameters);
        return intval($res->first->count);
    }

    /**
     * Convert ORM object to array.
     * 
     * @return array
     */
    public function toArray(...$columns) {

        $arr = [
            'ID' => $this->ID,
            'createdAt' => $this->createdAt
        ];

        if (empty($columns)) {
            $def = $this->Definition();
            foreach($def['columns'] as $col) {
                $arr[$col['name']] = $this->{$col['name']};
            }
            return $arr;
        }

        foreach($columns as $col) {
            $arr[$col] = $this->{$col};
        }
        return $arr;
    }


}