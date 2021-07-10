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
     * Primary keys.
     * 
     * @var array $__primaries
     */
    private array $__primaries = [];

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
        $auxname = '';
        $columns = [];
        $primary = '';
        foreach($def['columns'] as $v) {

            if (is_array($v)) {
                $columns[] = $v;
            }
            else {
                $obj = new $v();
                $name = $obj->reference_column();
                
                if (!empty($auxname))
                    $auxname .= '_';
                $auxname .= strtolower($obj->getTable());

                $columns[] = [
                    'name' => $name,
                    'type' => 'INT',
                    'notnull' => true
                ];
                
                if (!empty($primary))
                    $primary .= ', ';
                $primary .= $name;

                $this->__primaries[] = $name;
            }

        }

        $this->__definition = [
            'name' => $table ?? $auxname,
            'columns' => $columns,
            'primary' => $primary
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
     * Check if database exists.
     * 
     * @return bool Table existed
     */
    private function __checkDB() : bool {
        if ($this->__table_checked) return true;
        if (!Config::get('development_mode')) return false;

        if (!DB::table_exists($this->__getDefinition()['name'])){
            DB::create_table($this->__getDefinition());
        }
        $this->__table_checked = true;
        return false;
    }

    /**
     * Get the WHERE condition to select this relation.
     * 
     * @param array $parameters Query parameters
     * 
     * @return string
     */
    protected function whereThis(array &$parameters) : string {

        $where = '';
        foreach($this->__primaries as $pr) {
            if (!empty($where))
                $where .= ' AND ';
            $where .= "$pr = :$pr";

            if (isset($this->{$pr}))
                $parameters[$pr] = $this->{$pr};
            else
                $parameters[$pr] = 'NULL';
        }

        return "WHERE $where";

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
            $type = '_' . $col['type'];

            $this->{$name} = isset($col['default']) ? $col['default'] : null;

            if (isset($row->{$name})) {

                $v = $row->{$name};
                if (strpos($type, 'INT'))
                    $v = intval($v);
                if (strpos($type, 'DECIMAL') || strpos($type, 'FLOAT') || strpos($type, 'DOUBLE'))
                    $v = floatval($v);

                $this->{$name} = $v;
            }
        }    

    }

    /**
     * Gets one of the model in the relation.
     * 
     * @param string $class
     * 
     * @return ORM
     */
    public function getModel(string $class) {
        $obj = new $class();
        $table = $obj->getTable();
        $ref = $obj->reference_column();
        $res = DB::query("SELECT * FROM $table WHERE $ref = :id", [
            'id' => $this->{$ref}
        ]);

        if (!$res->something) return null;
        return new $class($res->first);
    }


    /**
     * Create or update this relation.
     */
    public function save() {
        $this->__checkDB();
        $t = $this->getTable();

        $parameters = [];
        $where = $this->whereThis($parameters);
        $res = DB::query("SELECT * FROM $t $where", $parameters);

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

        $i = 0;
        foreach($cols as $col) {
            $name = $col['name'];

            if (in_array($name, $this->__primaries)) {
                $i += 1;
                continue;
            }

            $q .= "$name = :$name";
            $parameters[$name] = $this->{$name};

            if ($i - sizeof($this->__primaries) < sizeof($cols) - sizeof($this->__primaries) - 1) {
                $q .= ', ';
            }
            ++ $i;
        }

        if (sizeof($parameters) == 0) return;

        $q .= ' ' . $this->whereThis($parameters);

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
        $parameters = [];
        $where = $this->whereThis($parameters);
        $res = DB::query("DELETE FROM $t $where", $parameters);
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
     * Find relations by once of the models.
     * 
     * @param ORM $model Name of the model you want to get.
     * @param string $where Where condition
     * @param array $parameters [Default empty] Query parameters
     * 
     * @return RelationORM[]
     */
    public static function findRelationsFor($model, string $where = '', array $parameters = []) : array {
        
        return self::find($model->reference_column() . ' = :ID' . (empty($where) ? '' : " AND $where"), 
        ['ID' => $model->ID]);

    }

    /**
     * Find an object of one of the Models by relation.
     * 
     * @param string $model Name of the model you want to get.
     * @param string|ORM $where Where condition
     * @param array $parameters [Default empty] Query parameters
     * 
     * @return ORM[]
     */
    public static function getByRelation(string $model, $where, array $parameters = []) : array {

        $cl = get_called_class();
        $tmp = new $cl();
        $tmp->__checkDB();

        $m = new $model();

        $w = is_string($where) ? $where : "ID = $where->ID";

        $q = 'SELECT ' . $m->reference_column() . ' FROM ' . $tmp->getTable() . " WHERE $w";

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