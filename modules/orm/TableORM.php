<?php

class TableORM extends stdClass implements JsonSerializable {

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
    protected array $__definition = [];

    /**
     * Data used to avoid infinite loop when serializing other models.
     * 
     * @var array $__serializersTree
     */
    private array $__serializersTree = [];

    /**
     * Get the table definition.
     * 
     */
    protected function Definition(DBBuilder $builder) {
        $builder->name(get_called_class());
    }

    public function __construct($row = null) {
        $this->__inflate();
        if ($row == null) return;
        $this->__absorbObject($row);
    }

    public function __toString() {
        return JSON::stringify($this->jsonSerialize());
    }

    /**
     * Serialize this model avoiding infinite loop serializing other models.
     * 
     * return array
     */
    private function serializeWithTree($tree) : array {
        $this->__serializersTree = $tree;
        return $this->jsonSerialize();
    }

    /**
     * Get the tree of the serialized models in this iteration.
     * 
     * @return array
     */
    private function getSerializersTree() : array {
        $tree = $this->__serializersTree;
        $cl = get_called_class();
        if (!isset($tree[$cl])) {
            $tree[$cl] = [$this->ID];
        } else {
            $tree[$cl][] = $this->ID;
        }
        return $tree;
    }

    public function jsonSerialize() : mixed {
        $value = [];
        $cols = $this->__columns();
        foreach($cols as $col) {
            if (isset($col['serialize']) && $col['serialize'] === false) {
                continue;
            }

            if (isset($col['serializeRelation'])) {
                $cl = $col['serializeRelation'];
                $id = $this->{$col['name']};

                if (empty($id)) continue;

                if (isset($this->__serializersTree[$cl])
                && in_array($id, $this->__serializersTree[$cl])) {
                    $value[$col['name']] = $this->{$col['name']};
                    continue;
                }

                if (class_exists($cl)) {
                    $m = $cl::findOne('ID = :ID', [
                        'ID' => $this->{$col['name']}
                    ]);

                    if (!$m) continue;

                    $n = $col['name'];
                    $n = str_replace('_id', '', $n);
                    $n = str_replace('Id', '', $n);

                    $value[$n] = $m->serializeWithTree($this->getSerializersTree());
                    continue;
                }
            }

            $value[$col['name']] = $this->{$col['name']};
        }
        return $value;
    }

    /**
     * Get the table name.
     * 
     * @return string
     */
    public function getTable() : string {
        return array_keys($this->__getDefinition())[0];
    }

    /**
     * Assign default values.
     */
    protected function __inflate() {
        $def = $this->__getDefinition();

        $this->createdAt = datenow();
        foreach($def as $table => $columns) {
            foreach($columns as $col) {
                if (!isset($col['name'])) continue;

                if (isset($col['default'])) {
                    $this->{$col['name']} = $col['default'];
                } else {
                    if (empty($col['notnull'])) {
                        $this->{$col['name']} = null;
                    } else {
                        $type = strtolower($col['type'] ?? 'VARCHAR');
                        if ($type == 'text' || strpos($type, 'char') !== FALSE) {
                            $this->{$col['name']} = '';
                        } else {
                            $this->{$col['name']} = 0;
                        }
                    }
                }
            }
            break;
        }
    }

    /**
     * Absorb values from a DB result.
     * 
     * @param $row
     */
    protected function __absorbObject($row) {
        $cols = $this->__columns();
        foreach($cols as $col) {
            $name = $col['name'];
            $type = '_' . $col['type'];

            if (isset($row->{$name})) {

                $v = $row->{$name};
                if (strpos($type, 'INT') !== FALSE)
                    $v = intval($v);
                if (strpos($type, 'DECIMAL') !== FALSE 
                || strpos($type, 'FLOAT') !== FALSE 
                || strpos($type, 'DOUBLE') !== FALSE)
                    $v = floatval($v);

                $this->{$name} = $v;
            }
        }

    }

    /**
     * Get the table definition.
     * 
     * @return array
     */
    private function __getDefinition() : array {
        if ($this->__definition != null) return $this->__definition;

        $def = new DBBuilder();
        $this->Definition($def);
        $def = $def->toArray();

        $tableName = '';
        $cols = [];
        foreach($def as $table => $columns) {
            $tableName = $table;
            $cols = $columns;
        }

        $this->__addAdditionalColumns($cols);

        if (empty($tableName)) {
            $this->__definition = [
                $tableName => []
            ];
        } else {
            $this->__definition = [
                $tableName => $cols
            ];
        }

        return $this->__definition;
    }

    /**
     * To be overridden. Add columns to the table.
     */
    protected function __addAdditionalColumns(array &$columns) {
        return;
    }

    /**
     * Get table columns.
     * 
     * @return array
     */
    protected function __columns() : array {
        if (empty($this->__definition)) {
            $this->__getDefinition();
        }

        return $this->__definition[$this->getTable()];
    }

    /**
     * Create model table if not exists.
     * 
     * @return bool existed already.
     */
    public static function CreateTable() : bool {
        $cl = get_called_class();
        $obj = new $cl();
        return $obj->CheckTable();
    }

    /**
     * Check if DB exists or create it.
     * 
     * @return bool existed
     */
    public function CheckTable() : bool {
        if ($this->__table_checked) return true;
        if (!Config::get('project.development_mode')) return true;

        $existed = true;
        if (!DB::tableExists($this->getTable())){
            $existed = false;
            DB::createTables($this->__getDefinition());
        } else {
            // Check columns
            $definitionColumns = $this->__columns();
            $tableColumns = DB::run('SHOW COLUMNS FROM `' . $this->getTable() . '`')->result;

            $tableObj = DBTable::instance($this->getTable());

            $missing = [];
            $position = 'FIRST';
            foreach($definitionColumns as $col) {
                $found = false;
                foreach($tableColumns as $tcol) {
                    if ($col['name'] == $tcol->Field) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $missing[] = [
                        'column' => $col,
                        'position' => $position
                    ];
                }
                $position = 'AFTER ' . $col['name'];
            }

            $was = Config::get('database.debug');
            Config::set('database.debug', false);
            foreach($missing as $col) {

                $coldef = [];
                foreach($col['column'] as $k => $v) {
                    $coldef[$k] = $v;
                }
                $coldef['position'] = $col['position'];

                try {
                    $tableObj->addColumn($coldef);
                } catch (Exception $e) {}
            }
            Config::set('database.debug', $was);

        }
        $this->__table_checked = true;
        return $existed;
    }

    /**
     * Generate a selector query for this row.
     * 
     * @return DBQuery
     */
    private function __selector() : DBQuery {
        $q = DB::query($this->getTable());

        $cols = $this->__columns();
        foreach($cols as $col) {
            $n = $col['name'];

            if (!empty($this->{$n})) {
                $q->where($n, $this->{$n});
            }
        }

        return $q;
    }

    /**
     * Delete
     * 
     * @return TableORM self
     */
    public function delete() : TableORM {
        $this->__selector()->delete();
        return $this;
    }

    /**
     * Create or update this object.
     * 
     * @param array ...$columns
     */
    public function save(...$columns) {
        $this->CheckTable();

        $found = $this->__selector()->first();

        if ($found) {
            $this->__update($columns);
        }
        else {
            $this->__create();
        }

        return $this;
    }

    /**
     * Update
     * 
     * @param array ...$columns
     * 
     * @return TableORM
     */
    protected function __update($columns = []) : TableORM {

        $q = $this->__selector();
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


        foreach($cols as $name) {
            $q->set($name, $this->{$name});
        }

        $q->update();

        return $this;
    }

    /**
     * Create.
     */
    protected function __create() {

        $q = DB::query($this->getTable());

        $columns = $this->__columns();

        foreach($columns as $col) {
            $name = $col['name'];
            $q->set($name, $this->{$name});
        }

        $q->insert();
    }

    /**
     * Convert ORM object to array.
     * 
     * @return array
     */
    public function toArray(...$columns) {

        $arr = [];

        if (empty($columns)) {

            $def = new DBBuilder();
            $this->Definition($def);
            $def = $def->toArray();

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

     /**
     * Get another ORM object referenced by a field of this object.
     * 
     * @param string $column
     * @param string $class
     * @param string $otherObjectColumn
     * 
     * @return ORM|null
     */
    public function getRelated(string $column, string $class = '', string $otherObjectColumn = 'ID') {

        if (!isset($this->{$column})) {
            return null;
        }

        $cl = $class;
        if ($cl == '') {
            // Try to guess class by definition serializeRelation column
            $cols = $this->__columns();
            foreach($cols as $col) {
                if ($col['name'] != $column) continue;
                if (!isset($col['serializeRelation'])) continue;

                $cl = $col['serializeRelation'];
                break;
            }
            if (empty($cl)) return null;
        }

        return $cl::findOne("$otherObjectColumn = :value", [
            'value' => $this->{$column}
        ]);

    }

    ////////// Static methods

    /**
     * Drop database table
     */
    public static function dropTable() {
        if (DB::tableExists(self::Table()))
            DBTable::instance(self::Table())->drop();
    }

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
     * @return mixed
     */
    public static function findOne(string $where = '1', array $parameters = []) {

        $cl = get_called_class();
        $tmp = new $cl();
        $tmp->CheckTable();
        $q = 'SELECT * FROM `' . $tmp->getTable() . "` WHERE $where LIMIT 1";

        $q = DB::run($q, $parameters);
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
     * @return array
     */
    public static function find(string $where = '1', array $parameters = []) : array {

        $cl = get_called_class();
        $tmp = new $cl();
        $tmp->CheckTable();
        $q = 'SELECT * FROM `' . $tmp->getTable() . "` WHERE $where";

        $q = DB::run($q, $parameters);
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
        $tmp->CheckTable();
        DB::run('DELETE FROM `' . $tmp->getTable() . "` WHERE $where", $parameters);
    }

    /**
     * Count objects of this model.
     * 
     * @param string $where [Default none]
     * @param array $parameters [Default empty] Query parameters.
     * 
     * @return int
     */
    public static function count(string $where = '1', array $parameters = []) : int {
        $cl = get_called_class();
        $tmp = new $cl();
        $tmp->CheckTable();
        $res = DB::run('SELECT COUNT(*) AS count FROM `' . $tmp->getTable() . "` WHERE $where", $parameters);
        return intval($res->first->count);
    }

    /**
     * Get a paginated response for this model.
     * 
     * @param array $options
     * @param string $where
     * @param array $parameters
     * 
     * @return array
     */
    public static function paginate(array $options = [
        'page' => 0,
        'offset' => -1,
        'pageSize' => 10
    ], string $where = '1', array $parameters = []) : array {

        $total = self::count($where, $parameters);

        $cl = get_called_class();
        $tmp = new $cl();
        $t = $tmp->getTable();

        $l = $options['pageSize'] ?? 10;
        if (isset($options['offset']) && $options['offset'] != -1) {
            $o = $options['offset'];
        } else {
            $o = ($options['page'] ?? 0) * $l;
        }

        $limit = "LIMIT $l OFFSET $o";

        $res = self::find($where . " $limit", $parameters);

        return ApiResponse::paginate($res, [
            'total' => $total,
            'pageSize' => $l,
            'offset' => $o,
            'page' => $options['page'] ?? (floor($o / $l))
        ]);
    }

    /**
     * Convert a database result array to objects of this ORM class.
     * 
     * @param array array
     * 
     * @return array ORMs
     */
    public static function parseArray(array $array) : array {
        $cl = get_called_class();
        return arr($array)->map(function($item) use ($cl) {
            return new $cl($item);
        });
    }

    /**
     * Get a query for this model.
     * 
     * @return DBQuery
     */
    public static function query() : DBQuery {
        $cl = get_called_class();
        $sample = new $cl();
        return DB::query($sample->getTable())->setClass($cl);
    }

}