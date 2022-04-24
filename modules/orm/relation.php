<?php

class RelationORM {

    /**
     * Has the table been checked yet.
     * 
     * @var bool $__table_checked [Default false]
     */
    private bool $__table_checked = false;

    /**
     * Table definition.
     * 
     * @var array $__definition
     */
    private array $__definition = [];

    /**
     * Return the table definition.
     * 
     * @return array
     */
    protected function Definition() {
        return [];
    }

    /**
     * Create a relation from objects.
     * 
     * @param ORM[] ...$objects
     */
    public function __construct(...$objects) {

        if (sizeof($objects) == 1 && is_object($objects[0])) {
            $this->__absorbObject($objects[0]);
        }
        else {
            $result = $this->__find($objects);
            $this->__absorbObject($result);
        }
    }

    /**
     * Get the table definition.
     * 
     * @return array
     */
    private function __getDefinition() : array {
        if ($this->__definition != null) return $this->__definition;

        $def = $this->Definition();
        if (sizeof($def) == 0) return [];

        $table = $def['name'] ?? null;
        $columns = [];
        $primaries = [];
        foreach($def['columns'] as $k => $v) {

            $col = [];

            if (is_string($k)) {

                if (!class_exists($k)) {
                    continue;
                }
                $obj = new $k();

                $col = [
                    'type' => 'BIGINT',
                    'foreign' => $obj->getTable() . '(ID)',
                    'class' => $k
                ];

                $col['name'] = $v['name'] ?? $obj->reference_column();

                $props = ['notnull', 'primary', 'unique', 'primary'];
                foreach($props as $prop) {
                    if (isset($v[$prop]))
                        $col[$prop] = $v[$prop];
                }

            } else {
                $col = $v;
            }

            $columns[] = $col;

            if (isset($col['primary'])) {
                $primaries[] = $col['name'];
            }

        }

        if (empty($primaries)) {
            foreach($columns as &$col) {
                if (isset($col['class'])) {
                    $col['primary'] = true;
                }
            }
        }

        $this->__definition = [
            'name' => $table ?? $auxname,
            'columns' => $columns
        ];
        
        return $this->__definition;
    }

    /**
     * Get the table columns.
     * 
     * @return array
     */
    private function __columns() : array {
        return $this->__getDefinition()['columns'];
    }

    /**
     * Get the relation table.
     * 
     * @return string
     */
    public function getTable() : string {
        return $this->__getDefinition()['name'];
    }

    /**
     * Get class database table.
     * 
     * @return string
     */
    public static function Table() : string {
        $cl = get_called_class();
        $obj = new $cl();
        return $obj->getTable();
    }

    /**
     * Check if database exists.
     * 
     * @return bool Table existed
     */
    private function __checkDB() : bool {
        if ($this->__table_checked) return true;
        if (!Config::get('project.development_mode')) return true;

        $existed = true;
        if (!DB::tableExists($this->__getDefinition()['name'])){
            $existed = false;
            DB::createTable($this->__getDefinition());
        }
        $this->__table_checked = true;
        return $existed;
    }

    /**
     * Find a relation with these objects.
     * 
     * @param ORM[] $objects
     * 
     * @return mixed
     */
    private function __find($objects) {
        if (sizeof($objects) == 0) return null;

        $where = '';
        $parameters = [];
        foreach($objects as $obj) {
            if (is_object($obj)) {
                if (!empty($where))
                    $where .= ' AND ';
                $where .= $obj->reference_column() . ' = :' . $obj->reference_column();
                $parameters[$obj->reference_column()] = $obj->ID;
            }
        }

        $res = DB::query('SELECT * FROM ' . $this->getTable() . " WHERE $where LIMIT 1", $parameters);

        if (!$res->something) return new Generic($parameters);
        return $res->first;

    }

    /**
     * Transform DB row into RelationORM object.
     * 
     * @param Generic $row
     */
    private function __absorbObject(?Generic $row) {

        $cols = $this->__columns();
        foreach($cols as $col) {

            $name = $col['name'];
            $type = isset($col['type']) ? $col['type'] : 'BIGINT';

            $this->{$name} = isset($col['default']) ? $col['default'] : null;

            if (isset($row->{$name})) {

                $v = $row->{$name};
                if (strpos($type, 'INT') !== FALSE)
                    $v = intval($v);
                if (strpos($type, 'DECIMAL') !== FALSE || strpos($type, 'FLOAT') !== FALSE || strpos($type, 'DOUBLE') !== FALSE)
                    $v = floatval($v);

                $this->{$name} = $v;
            }
        }    

    }

    /**
     * Gets one of the model in the relation.
     * 
     * @param string $column
     * 
     * @return ORM
     */
    public function getModel(string $column) {
        $def = $this->__getDefinition();
        $class = null;
        foreach($def['columns'] as $col) {
            if ($col['name'] != $column) {
                continue;
            }

            if (isset($col['class'])) {
                $class = $col['class'];
            }
        }

        if ($class == null) {
            return null;
        }

        $obj = new $class();
        $table = $obj->getTable();
        $res = DB::query("SELECT * FROM $table WHERE ID = :id", [
            'id' => $this->{$column}
        ]);

        if (!$res->something) return null;
        return new $class($res->first);
    }

    private function getPrimaries() : array {
        $def = $this->__getDefinition();
        $primaries = [];

        foreach($def['columns'] as $col) {
            if (isset($col['primary'])) {
                $primaries[] = $col['name'];
            }
        }

        return $primaries;
    }

    /**
     * Get the WHERE condition to select this relation.
     * 
     * @return array [where, parameters]
     */
    private function whereForMe() : array {

        $primaries = $this->getPrimaries();
        
        if (empty($primaries)) {
            return [
                'where' => 'ID = :ID',
                'parameters' => [
                    'ID' => $this->ID
                ]
            ];
        }

        $where = '';
        $parameters = [];
        foreach($primaries as $p) {
            if ($where != '') {
                $where .= ' AND ';
            }

            $where .= "$p = :$p";
            $parameters[$p] = $this->{$p};
        }

        return [
            'where' => $where,
            'parameters' => $parameters
        ];
        
    }


    /**
     * Create or update this relation.
     */
    public function save() {
        $this->__checkDB();
        $t = $this->getTable();

        $cond = $this->whereForMe();
        $where = $cond['where'];
        $parameters = $cond['parameters'];

        $res = DB::query("SELECT * FROM $t WHERE $where", $parameters);

        if ($res->something) {
            $this->__update();
        }
        else {
            $this->__create();
        }

    }

    /**
     * Update this relation.
     */
    private function __update() {
        $cols = $this->__columns();

        $q = 'UPDATE ' . $this->getTable() . ' SET ';
        $parameters = [];

        $primaries = $this->getPrimaries();

        $i = 0;
        foreach($cols as $col) {
            $name = $col['name'];

            if (in_array($name, $primaries)) {
                $i += 1;
                continue;
            }

            $q .= "$name = :$name";
            $parameters[$name] = $this->{$name};

            if ($i - sizeof($primaries) < sizeof($cols) - sizeof($primaries) - 1) {
                $q .= ', ';
            }
            ++ $i;
        }

        if (sizeof($parameters) == 0) return;

        $cond = $this->whereForMe();

        $q .= ' ' . $cond['where'];

        foreach($cond['parameters'] as $k => $v) {
            $parameters[$k] = $v;
        }

        DB::query($q, $parameters);
    }

    /**
     * Create this relation.
     */
    private function __create() {
        $q = 'INSERT INTO ' . $this->getTable() . ' (';

        $columns = $this->__columns();
        $i = 0;
        foreach($columns as $col) {

            $q .= $col['name'];

            if ($i < sizeof($columns) - 1) {
                $q .= ', ';
            }
            ++$i;
        }

        $q .= ') VALUES (';
        $parameters = [];
        $i = 0;

        foreach($columns as $col) {

            $q .= ':'.$col['name'];

            $parameters[$col['name']] = $this->{$col['name']};

            if ($i < sizeof($columns) - 1) {
                $q .= ', ';
            }
            ++$i;
        }

        $q .= ');';

        DB::query($q, $parameters);

    }

    /**
     * Delete this relation.
     */
    public function delete() {
        $t = $this->getTable();
        $cond = $this->whereForMe();
        $res = DB::query("DELETE FROM $t " . $cond['where'], $cond['parameters']);
    }

    /**
     * Drop database table
     */
    public static function dropTable() {
        if (DB::tableExists(self::Table()))
            DBTable::instance(self::Table())->drop();
    }

    /**
     * Find a relation.
     * 
     * @param string $where [Default none] Where condition
     * @param array $parameters [Default empty] Query parameters
     * 
     * @return RelationORM
     */
    public static function findOne(string $where = '1', array $parameters = []) : ?RelationORM {

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
     * Find relations.
     * 
     * @param string $where [Default none] Where condition
     * @param array $parameters [Default empty] Query parameters
     * 
     * @return RelationORM[]
     */
    public static function find(string $where = '1', array $parameters = []) : array {

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
     * Delete relations.
     * 
     * @param string $where Where condition
     * @param array $parameters [Default empty] Query parameters
     */
    public static function deleteWhere(string $where, array $parameters = []) {
        $cl = get_called_class();
        $tmp = new $cl();
        DB::query('DELETE FROM ' . $tmp->getTable() . " WHERE $where", $parameters);
    }


    /**
     * Get a list of ORM objects by the relation.
     * 
     * @param string $column
     * @param string|ORM $where Where condition
     * @param array $parameters [Default empty] Query parameters
     * 
     * @return ORM[]
     */
    public static function modelByRelation(string $column, string $where, array $parameters = []) : array {

        $cl = get_called_class();
        $tmp = new $cl();
        $tmp->__checkDB();

        $col = null;
        $def = $tmp->__getDefinition();
        foreach($def['columns'] as $c) {
            if ($c['name'] == $column) {
                $col = $c;
                break;
            }
        }

        if ($col == null) {
            return [];
        }

        if (!isset($col['class'])) {
            return [];
        }

        $model = $col['class'];
        $m = new $model();

        $q = 'SELECT ' . $column . ' FROM ' . $tmp->getTable() . " WHERE $where";

        $res = DB::query('SELECT * FROM ' . $m->getTable() . " WHERE ID IN ($q)", $parameters);
        $list = [];
        foreach($res->result as $r) {
            $list[] = new $model($r);
        }
        return $list;

    }

    /**
     * Count realtions.
     * 
     * @param string $where condition
     * @param array $parameters [Default empty]
     * 
     * @return int
     */
    public static function count(string $where, array $parameters = []) : int {
        $cl = get_called_class();
        $tmp = new $cl();
        $res = DB::query('SELECT COUNT(*) AS count FROM ' . $tmp->getTable() . " WHERE $where", $parameters);
        return intval($res->first->count);
    }

}