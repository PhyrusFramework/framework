<?php

class ORM implements JsonSerializable {

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
     * Data used to avoid infinite loop when serializing other models.
     * 
     * @var array $__serializersTree
     */
    private array $__serializersTree = [];

    /**
     * ID
     * 
     * @var int $ID
     */
    public ?int $ID = 0;

    /**
     * Get the table definition.
     * 
     */
    protected function Definition(DBBuilder $builder) {
        $builder->name(get_called_class());
    }

    public function __construct($ID = null) {
        $this->__inflate();
        if ($ID == null) return;

        if (is_object($ID)) {
            $this->__absorbObject($ID);
        }
        else {
            $res = DB::run('SELECT * FROM '.$this->getTable().' WHERE ID = :id', ['id' => $ID]);
            if (!$res->something) return;

            $this->__absorbObject($res->first);
        }

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

    public function jsonSerialize() {
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

        $def = new DBBuilder();
        $this->Definition($def);
        $def = $def->toArray();
        
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
     * Create model table if not exists.
     * 
     * @return bool existed already.
     */
    public static function CreateTable() : bool {
        $cl = get_called_class();
        $obj = new $cl();
        return $obj->checkTable();
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
            DB::createTable($this->__getDefinition());
        } else {
            // Check columns
            $definitionColumns = $this->__columns();
            $tableColumns = DB::run('SHOW COLUMNS FROM ' . $this->getTable())->result;

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

            foreach($missing as $col) {

                $coldef = [];
                foreach($col['column'] as $k => $v) {
                    $coldef[$k] = $v;
                }
                $coldef['position'] = $col['position'];

                $tableObj->addColumn($coldef);
            }

        }
        $this->__table_checked = true;
        return $existed;
    }

    /**
     * Find the ID by the time it was created.
     * 
     * @param string $creationTime
     * 
     * @return int
     */
    private function __findID(string $creationTime) : int {

        $res = DB::run('SELECT * FROM '.$this->getTable().' WHERE createdAt = :date ORDER BY ID DESC LIMIT 1', [
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
        $res = DB::run('SELECT * FROM '.$this->getTable()." WHERE ID = $this->ID LIMIT 1");
        return $res->something;
    }

    /**
     * Delete.
     */
    public function delete() {
        if (!$this->isCreated()) return;
        DB::run('DELETE FROM '.$this->getTable()." WHERE ID = $this->ID");
    }

    /**
     * Create or update this object.
     * 
     * @param array ...$columns
     */
    public function save(...$columns) {
        $this->CheckTable();

        if ($this->exists()) {
            $this->__update($columns);
        }
        else {
            $this->__create();
        }

        return $this;
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

        DB::run($q, $parameters);
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

        $res = DB::run($q, $parameters);

        $this->__findID($now);

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
     * Drop database table
     */
    public static function dropTable() {
        if (DB::tableExists(self::Table()))
            DBTable::instance(self::Table())->drop();
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
     * Find the object with this ID.
     * 
     * @param int ID
     * 
     * @return ORM|null
     */
    public static function findID(int $ID) {
        return self::findOne('ID = :ID', ['ID' => intval($ID)]);
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
        $tmp->CheckTable();
        $q = 'SELECT * FROM ' . $tmp->getTable() . " WHERE $where LIMIT 1";

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
     * @return ORM[]
     */
    public static function find(string $where = '1', array $parameters = []) {

        $cl = get_called_class();
        $tmp = new $cl();
        $tmp->CheckTable();
        $q = 'SELECT * FROM ' . $tmp->getTable() . " WHERE $where";

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
        DB::run('DELETE FROM ' . $tmp->getTable() . " WHERE $where", $parameters);
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
        $tmp->CheckTable();
        $res = DB::run('SELECT COUNT(*) AS count FROM ' . $tmp->getTable() . " WHERE $where", $parameters);
        return intval($res->first->count);
    }

    public static function CRUD($route = null) {
        $cl = get_called_class();
        $crud = new CRUD($route ? $route : (new $cl())->getTable());

        $gen = new Generic();

        $gen->set('middleware', function($middleware) use ($gen, $crud) {
            $crud->middleware($middleware);
            return $gen;
        });

        $gen->set('generate', function() use ($gen, $crud) {
            $crud->generate();
            return $gen;
        });

        $gen->set('list', function($param = true) use ($gen, $crud, $cl) {

            $crud->list(function() use ($param, $cl) {

                $req = new RequestData();
                if (is_callable($param)) {
                    return $param($req);
                }
                $list = $cl::find();
                return $list;

            });

            return $gen;

        });

        $gen->set('get', function($param = true) use ($gen, $crud, $cl) {

            $crud->get(function($id) use ($param, $cl) {
                
                $obj = $cl::findID($id);

                if ($obj == null) {
                    response_die('not-found');
                }

                $req = new RequestData();

                if (is_callable($param)) {
                    return $param($req, $obj);
                }
                return $obj;

            });

            return $gen;

        });

        $gen->set('create', function($param = true) use ($gen, $crud, $cl) {

            $crud->create(function() use ($param, $cl) {

                $req = new RequestData();
                if (is_callable($param)) {
                    return $param($req);
                }
                $obj = new $cl($req);
                $obj->save();
                return $obj;

            });

            return $gen;

        });

        $gen->set('edit', function($param = true) use ($gen, $crud, $cl) {
                
            $crud->get(function($id) use ($param, $cl) {
                
                $obj = $cl::findID($id);
                if ($obj == null) {
                    response_die('not-found');
                }

                $req = new RequestData();

                if (is_callable($param)) {
                    return $param($req, $obj);
                }
                $req = new RequestData();
                $obj = new $cl($req);
                $obj->ID = $id;
                $obj->save();
                return $obj;

            });

            return $gen;

        });

        $gen->set('delete', function($param = true) use ($gen, $crud, $cl) {
                
            $crud->get(function($id) use ($param, $cl) {
                
                $obj = $cl::findID($id);
                if ($obj == null) {
                    response_die('not-found');
                }

                $req = new RequestData();

                if (is_callable($param)) {
                    return $param($req, $obj);
                }
                $obj->delete();
                return $obj;

            });

            return $gen;

        });

        return $gen;
    }

}