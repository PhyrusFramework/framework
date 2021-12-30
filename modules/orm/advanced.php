<?php

class AdvancedORM extends ORM {

    /**
     * Cached object meta
     * 
     * @var array
     */
    private array $__metas = [];

    /**
     * Cached object translations
     * 
     * @var array
     */
    private array $__translations = [];

    /**
     * Cached object resources
     * 
     * @var array
     */
    private array $__resources = [];

    public function __construct($ID = null) {
        parent::__construct($ID);
    }

    /**
     * Check if DB exists or create it.
     * 
     * @param bool Check also other tables: meta, translations, resources.
     * 
     * @return bool existed
     */
    public function CheckTable($extraTables = false) : bool {
        $existed = parent::CheckTable();

        if ($extraTables) {
            $this->__checkDB_meta();
            $this->__checkDB_translations();
            $this->__checkDB_resources();
        }

        return $existed;
    }

    /**
     * Tables that have already checked if they exist.
     * 
     * @var array $_tables_checked
     */
    private static array $_tables_checked = [];

    /**
     * Get the name of the meta table.
     * 
     * @return string
     */
    public function meta_table() : string {
        return $this->getTable() . '_meta';
    }

    /**
     * Check if the meta table exists and if it doesn't, create it.
     * 
     * @return bool existed
     */
    private function __checkDB_meta() {

        $table = $this->meta_table();
        
        if (isset(self::$_tables_checked[$table])) {
            return true;
        }

        if (!Config::get('development_mode')) return true;

        $existed = true;
        if (!DB::table_exists($table)){
            $existed = false;
            self::$_tables_checked[$table] = true;

            DB::create_table([
                'name' => $this->meta_table(),
                'columns' => [
                    [
                        'name' => $this->reference_column(),
                        'type' => 'BIGINT',
                        'notnull' => true,
                        'foreign' => $this->getTable() . '(ID)'
                    ],
                    [
                        'name' => 'meta_key',
                        'type' => 'VARCHAR(100)',
                        'notnull' => true
                    ],
                    [
                        'name' => 'meta_value',
                        'type' => 'TEXT'
                    ]
                ],
                'primary' => $this->reference_column() . ', meta_key'
            ]);
        }

        return $existed;
    }

    /**
     * Get the name of the translations table.
     * 
     * @return string
     */
    public function translations_table() : string {
        return $this->getTable() . '_translations';
    }

    /**
     * Check if the translations table exists and if it doesn't, create it.
     * 
     * @return bool existed
     */
    private function __checkDB_translations() {

        $table = $this->translations_table();
        
        if (isset(self::$_tables_checked[$table])) {
            return true;
        }

        if (!Config::get('development_mode')) return true;

        $existed = true;
        if (!DB::table_exists($table)){
            $existed = false;
            self::$_tables_checked[$table] = true;

            DB::create_table([
                'name' => $this->translations_table(),
                'columns' => [
                    [
                        'name' => $this->reference_column(),
                        'type' => 'BIGINT',
                        'notnull' => true,
                        'foreign' => $this->getTable() . '(ID)'
                    ],
                    [
                        'name' => 'name',
                        'type' => 'VARCHAR(100)',
                        'notnull' => true
                    ],
                    [
                        'name' => 'locale',
                        'type' => 'VARCHAR(10)',
                        'notnull' => true
                    ],
                    [
                        'name' => 'value',
                        'type' => 'TEXT'
                    ]
                ],
                'primary' => $this->reference_column() . ', name, locale'
            ]);
        }

        return $existed;
    }

    /**
     * Get the name of the resources table.
     * 
     * @return string
     */
    protected function resources_table() : string {
        return $this->getTable() . '_resources';
    }

    /**
     * Check if the resources table exists, and if it doesn't, create it.
     * 
     * @return bool existed
     */
    private function __checkDB_resources() {

        $table = $this->resources_table();
        
        if (isset(self::$_tables_checked[$table])) {
            return true;
        }

        if (!Config::get('development_mode')) return true;

        $existed = true;
        if (!DB::table_exists($table)){
            $existed = false;
            self::$_tables_checked[$table] = true;

            DB::create_table([
                'name' => $this->resources_table(),
                'columns' => [
                    [
                        'name' => $this->reference_column(),
                        'type' => 'BIGINT',
                        'notnull' => true,
                        'foreign' => $this->getTable() . '(ID)'
                    ],
                    [
                        'name' => 'type',
                        'type' => 'VARCHAR(100)',
                        'notnull' => true
                    ],
                    [
                        'name' => 'file',
                        'type' => 'TEXT'
                    ],
                    [
                        'name' => 'position',
                        'type' => 'INT',
                        'notnull' => true
                    ]
                ]
            ]); 
        }

        return $existed;
    }

    /**
     * Delete this object.
     */
    public function delete() {

        $ref = $this->reference_column();

        $t = $this->meta_table();
        if (DB::table_exists($t)) {
            DB::query("DELETE FROM $t WHERE $ref = :ID", [
                'ID' => $this->ID
            ]);
        }

        $t = $this->translations_table();
        if (DB::table_exists($t)) {
            DB::query("DELETE FROM $t WHERE $ref = :ID", [
                'ID' => $this->ID
            ]);
        }

        $t = $this->resources_table();
        if (DB::table_exists($t)) {
            DB::query("DELETE FROM $t WHERE $ref = :ID", [
                'ID' => $this->ID
            ]);
        }

        parent::delete();
    }

    /**
     * Set meta value
     * 
     * @param string $name
     * @param mixed $value
     */
    public function setMeta(string $name, $value) {
        $this->__metas[$name] = $value;

        $this->__checkDB_meta();

        $t = $this->meta_table();
        $ref = $this->reference_column();

        if ($value == null) {
            DB::query("DELETE FROM $t WHERE $ref = :ID AND meta_key = :name", [
                'ID' => $this->ID,
                'name' => $name
            ]);
            return;
        }

        $res = DB::query("SELECT * FROM $t WHERE $ref = :ID AND meta_key = :name LIMIT 1", [
            'name' => $name,
            'ID' => $this->ID
        ]);

        $v = $value;
        if (is_array($value)) {
            $v = new InsecureString($value);
        } else if (is_bool($value)) {
            $v = $value ? '1' : '0';
        } else if (!$value instanceof InsecureString) {
            $v = "$v";
        }

        if ($res->something) {
            DB::query("UPDATE $t SET meta_value = :value WHERE $ref = :ID AND meta_key = :name", [
                'ID' => $this->ID,
                'name' => $name,
                'value' => $v
            ]);
        }
        else {
            DB::query("INSERT INTO $t ($ref, meta_key, meta_value) VALUES (:ID, :name, :value)", [
                'ID' => $this->ID,
                'name' => $name,
                'value' => $v
            ]);
        }
    }

    /**
     * Get meta value
     * 
     * @param string $name
     * @param mixed $default [Default empty string]
     * 
     * @return mixed
     */
    public function getMeta(string $name, $default = '') {
        if (isset($this->__metas[$name]))
            return $this->__metas[$name];

        $t = $this->meta_table();
        $ref = $this->reference_column();
        $res = DB::query("SELECT * FROM $t WHERE $ref = :ID AND meta_key = :name LIMIT 1", [
            'ID' => $this->ID,
            'name' => $name
        ]);

        if (!$res->something) return $default;

        $value = $res->first->meta_value;
        if (is_array($default)) {
            if ($json = JSON::isJSON($value)) {
                $value = $json;
            }
        }

        $this->__metas[$name] = $value;
        return $value;
    }

    /**
     * Set object translation.
     * 
     * @param string $name
     * @param string $locale
     * @param string $value
     */
    public function setTranslation(string $name, string $locale, $value) {
        if (!isset($this->__translations[$locale]))
            $this->__translations[$locale] = [];
        $this->__translations[$locale][$name] = $value;

        $this->__checkDB_translations();

        $t = $this->translations_table();
        $ref = $this->reference_column();

        if ($value == null) {
            DB::query("DELETE FROM $t WHERE $ref = :ID AND name = :name AND locale = :locale", [
                'ID' => $this->ID,
                'name' => $name,
                'locale' => $locale
            ]);
            return;
        }

        $res = DB::query("SELECT * FROM $t WHERE $ref = :ID AND name = :name AND locale = :locale LIMIT 1", [
            'name' => $name,
            'ID' => $this->ID,
            'locale' => $locale
        ]);

        if ($res->something) {
            DB::query("UPDATE $t SET value = :value WHERE $ref = :ID AND name = :name AND locale = :locale", [
                'ID' => $this->ID,
                'name' => $name,
                'locale' => $locale,
                'value' => $value
            ]);
        }
        else {
            DB::query("INSERT INTO $t ($ref, name, locale, value) VALUES (:ID, :name, :locale, :value)", [
                'ID' => $this->ID,
                'name' => $name,
                'locale' => $locale,
                'value' => $value
            ]);
        }
    }

    /**
     * Get object translation
     * 
     * @param string $name
     * @param string $locale
     * @param mixed $default
     * 
     * @return mixed
     */
    public function getTranslation(string $name, string $locale, $default = '') {

        if (isset($this->__translations[$locale]) && isset($this->__translations[$locale][$name]))
            return $this->__translations[$locale][$name];

        $this->__checkDB_translations();

        $t = $this->translations_table();
        $ref = $this->reference_column();
    
        $res = DB::query("SELECT * FROM $t WHERE $ref = :ID AND name = :name AND locale = :locale LIMIT 1", [
            'name' => $name,
            'ID' => $this->ID,
            'locale' => $locale
        ]);

        if (!$res->something)
            return $default;

        if (!isset($this->__translations[$locale]))
            $this->__translations[$locale] = [];

        $this->__translations[$locale][$name] = $res->first->value;

        return $res->first->value;

    }

    /**
     * If the translation in any of the selected languages.
     * 
     * @param string $name
     * @param string $locale Preferred locale
     * @param mixed $default
     * 
     * @return mixed
     */
    public function getAnyTranslation(string $name, array $locales, $default = '') {

        $this->__checkDB_translations();

        foreach($locales as $locale) {

            if ($locale != '*') {
                $v = $this->getTranslation($name, $locale, null);
                if (!empty($v)) {
                    return $v;
                }
            } else {

                $t = $this->translations_table();
                $ref = $this->reference_column();
            
                $res = DB::query("SELECT * FROM $t WHERE $ref = :ID AND name = :name LIMIT 1", [
                    'name' => $name,
                    'ID' => $this->ID
                ]);
        
                if (!$res->something)
                    return $default;
        
                return $res->first->value;

            }
        }

        return $default;
    }

    /**
     * Get a list of all translations for this object.
     * 
     * @param string Specific language (Optional)
     * 
     * @return array
     */
    public function getTranslations($language = null) {

        $cl = get_called_class();
        $sample = new $cl();
        $sample->__checkDB_translations();

        $t = $sample->getTable();
        $tr = $sample->translations_table();
        $ref = $sample->reference_column();

        $query = "SELECT * FROM $tr WHERE $ref = :ID";
        if ($language != null) {
            $query .= ' AND locale = :locale';

            $res = DB::query($query, [
                'ID' => $this->ID,
                'locale' => $language
            ]);
    
            $translations = [];

            foreach($res->result as $row) {    
                $translations[$row->name] = $row->value;
            }
    
            return $translations;

        }

        $res = DB::query($query, [
            'ID' => $this->ID
        ]);

        $translations = [];

        foreach($res->result as $row) {
            if (!isset($translations[$row->locale])) {
                $translations[$row->locale] = [];
            }

            $translations[$row->locale][$row->name] = $row->value;
        }

        return $translations;

    }

    /**
     * Get object resources.
     * 
     * @param string $type [Default all]
     * @param bool $refresh [Default false] Reload cache
     * 
     * @return ORMResource[]
     */
    public function getResources(string $type = null, bool $refresh = false) : array {

        $this->__checkDB_resources();

        $t = $this->resources_table();
        $ref = $this->reference_column();

        if ($type == null) {
            $res = DB::query("SELECT * FROM $t WHERE $ref = :ID ORDER BY position ASC", [
                'ID' => $this->ID
            ]);
        }
        else {
            if (!$refresh && isset($this->__resources[$type]))
                return $this->__resources[$type];

            $res = DB::query("SELECT * FROM $t WHERE $ref = :ID AND type = :type ORDER BY position ASC", [
                'ID' => $this->ID,
                'type' => $type
            ]);

        }
        

        $list = [];
        foreach($res->result as $r) {
            $list[] = new ORMResource($r, $this);
        }

        if ($type != null)
            $this->__resources[$type] = $list;
            
        return $list;

    }

    /**
     * Get a single resource.
     * 
     * @param string $type
     * @param int $position [Default first]
     * 
     * @return ORMResource
     */
    public function getResource(string $type, int $position = 1) : ORMResource {
        $resources = $this->getResources($type);

        $r = null;
        foreach($resources as $resource) {
            if ($resource->position == $position)
                return $resource;

            if ($r == null)
                $r = $resource;
        }

        return $r;
    }

    /**
     * Get a resource by ID
     * 
     * @param mixed $ID
     * 
     * @return ORMResource
     */
    public function getResourceByID($ID) : ORMResource {
        return new ORMResource($ID, $this);
    }

    /**
     * Get a resource by file.
     * 
     * @param string $file
     * 
     * @return ORMResource
     */
    public function getResourceByFile(string $file) : ORMResource {
        $t = $this->resources_table();
        $ref = $this->reference_column();

        $res = DB::query("SELECT * FROM $t WHERE $ref = :ID AND file = :file", [
            'ID' => $this->ID,
            'file' => $file
        ]);

        if (!$res->something) return null;
        return new ORMResource($res->first, $this);
    }

    /**
     * Add a resource to this object.
     * 
     * @param string $type
     * @param string $file
     * 
     * @return ORMResource
     */
    public function addResource(string $type, string $file) {

        $this->__checkDB_resources();

        $t = $this->resources_table();
        $ref = $this->reference_column();

        // get top position
        $res = DB::query("SELECT * FROM $t WHERE $ref = :ID AND type = :type ORDER BY position DESC LIMIT 1", [
            'ID' => $this->ID,
            'type' => $type
        ]);

        $position = $res->something ? intval($res->first->position) + 1 : 1;

        DB::query("INSERT INTO $t ($ref, type, file, position) VALUES (:ID, :type, :file, :position)", [
            'ID' => $this->ID,
            'type' => $type,
            'file' => $file,
            'position' => $position
        ]);

        $res = DB::query("SELECT * FROM $t WHERE $ref = :ID AND type = :type AND file = :file DESC LIMIT 1", [
            'ID' => $this->ID,
            'type' => $type,
            'file' => $file
        ]);

        if ($res->something) {
            $resource = new ORMResource($res->first);
            $this->__resources[$type][] = $resource;
            return $resource;
        }
        return null;
    }

    /**
     * Delete object resources.
     * 
     * @param string $type [Default all]
     */
    public function deleteResources(string $type = null) {
        $t = $this->resources_table();
        $ref = $this->reference_column();

        $q = "DELETE FROM $t WHERE $ref = :ID";
        if ($type != null)
            $q .= ' AND type = :type';

        DB::query($q, [
            'ID' => $this->ID,
            'type' => $type
        ]);

        if ($type == null) $this->__resources = [];
        else $this->__resources[$type] = [];
    }

    /**
     * Override parent dropTable().
     * Drops all tables.
     */
    public static function dropTable() {
        $this->dropTables();
    }
    
    /**
     * Drop database tables
     */
    public static function dropTables() {
        $cl = get_called_class();
        $sample = new $cl();

        if (DB::table_exists($sample->translations_table()))
        DBTable::instance($sample->translations_table())->drop();

        if (DB::table_exists($sample->resources_table()))
        DBTable::instance($sample->resources_table())->drop();

        if (DB::table_exists($sample->meta_table()))
        DBTable::instance($sample->meta_table())->drop();

        if (DB::table_exists($sample->getTable()))
        DBTable::instance($sample->getTable())->drop();
    }

    // STATIC

    // SEARCH METHODS

    /**
     * Find elements by translation
     * 
     * @param string $name
     * @param mixed $value
     * @param mixed $locales
     * 
     * @return array
     */
    public static function byTranslation(string $name, $value = null, $locales = null) : array {

        if (!isset($options['value'])) {
            return [];
        }

        $cl = get_called_class();
        $sample = new $cl();
        $sample->__checkDB_translations();

        $t = $sample->getTable();
        $tr = $sample->translations_table();
        $ref = $sample->reference_column();

        $params = [
            'name' => $name
        ];

        $subq = "SELECT $ref FROM $tr WHERE name = :name";
        if ($value != null) {
            $params['value'] = $value;

            if (is_string($value)) {
                if (strpos($value, '%') === FALSE) {
                    $subq .= ' AND value = :value';
                } else {
                    $subq .= ' AND value LIKE :value';
                }
            } else if (is_array($value)) {
                $subq .= ' AND value IN :value';
            }

        }

        if ($locales != null) {
            $params['locales'] = $locales;

            if (is_string($locales)) {
                $subq .= ' AND locale = :locales';
            } else if (is_array($locales)) {
                $subq .= ' AND locale IN :locales';
            }

        }

        $query = "ID IN ($subq)";
        return $cl::find($query, $params);
    }

    /**
     * Find models by meta. Optionally specify the value.
     * 
     * @param array $metas
     * 
     * @return array
     */
    public static function byMeta(...$meta_sets) : array {

        $cl = get_called_class();
        $sample = new $cl();
        $t = $sample->getTable();
        $metat = $sample->meta_table();
        $ref = $sample->reference_column();

        $where = '';
        $params = [];

        foreach($meta_sets as $metas) {

            if ($where != '') {
                $where .= ' OR ';
            }

            $current = '';

            foreach($metas as $k => $v) {

                if (is_string($k)) {
                    $meta = $k;
                    $value = $v;
                    if (is_int($v)) {
                        $value = "$v";
                    }
                } else {
                    $meta = $v;
                    $value = null;
                }
    
                $sub = "SELECT $ref FROM $metat WHERE meta_key = :$meta";
                $params[$meta] = $meta;
                if ($value != null) {
        
                    if (is_string($value)) {
                        if (strpos($value, '%') === FALSE) {
                            $operator .= '=';
                        } else {
                            $operator .= 'LIKE';
                        }
                    } else if (is_array($value)){
                        $operator = 'IN';
                    }
        
                    $sub .= " AND meta_value $operator :$meta".'_value';
                    $params[$meta.'_value'] = $value;
                }
    
                if ($current != '') {
                    $current .= ' AND ';
                }
                $current .= "ID IN ($sub)";
    
            }

            if (sizeof($metas) > 1) {
                $where .= '(';
            }
            $where .= $current;
            if (sizeof($metas) > 1) {
                $where .= ')';
            }

        }

        return $cl::find($where, $params);

    }

    /**
     * Get list of objects sorted by a meta value.
     * 
     * @param string meta-key
     * @param string ASC/DESC, OFFSET, LIMIT
     * 
     * @return array
     */
    public static function sortByMeta(string $name, array $options = []) : array {

        $cl = get_called_class();
        $sample = new $cl();
        $t = $sample->getTable();
        $metat = $sample->meta_table();
        $ref = $sample->reference_column();

        $direction = $options['sort'] ?? 'ASC';

        $order = empty($options['asNumber']) ?
            "$metat.meta_value" : "CAST($metat.meta_value AS INT)";

        $q = "SELECT $t.* FROM $t INNER JOIN $metat ON $t.ID = $metat.$ref WHERE $metat.meta_key = :name ORDER BY $order $direction";

        $params = [
            'name' => $name
        ];

        if (isset($options['params'])) {
            foreach($options['params'] as $k => $v) {
                $params[$k] = $v;
            }
        }

        $res = DB::query($q, $params);

        $list = [];
        foreach($res->result as $row) {
            $list[] = new $cl($row);
        }
        return $list;

    }

    /**
     * Get list of objects sorted by a meta value.
     * 
     * @param string meta-key
     * @param string ASC/DESC, OFFSET, LIMIT
     * 
     * @return array
     */
    public static function sortByTranslation(string $name, array $options = []) : array {

        $cl = get_called_class();
        $sample = new $cl();
        $t = $sample->getTable();
        $tr = $sample->translations_table();
        $ref = $sample->reference_column();

        $direction = $options['sort'] ?? 'ASC';

        $params = [
            'name' => $name
        ];

        $lq = '';
        if (isset($options['locale'])) {
            $params['locales'] = $options['locale'];
            $lq = 'AND locale = :locales';
        } else if (isset($options['locales'])) {
            $params['locales'] = $options['locales'];
            $lq = 'AND locale IN :locales';
        }

        $q = "SELECT $t.* FROM $t INNER JOIN $tr ON $t.ID = $tr.$ref WHERE $tr.name = :name $lq ORDER BY $tr.value $direction";

        $res = DB::query($q, $params);

        $list = [];
        foreach($res->result as $row) {
            $list[] = new $cl($row);
        }
        return $list;

    }

}

class ORMResource {

    /**
     * Table for this resources.
     * 
     * @var string $__table
     */
    private string $__table;

    /**
     * Advanced ORM object.
     * 
     * @var AdvancedORM $__object
     */
    private $__object;

    /**
     * ID
     * 
     * @var int $ID
     */
    private int $ID;

    /**
     * File
     * 
     * @var stirng $file
     */
    private string $file;

    /**
     * Resource position in the list of resources of this type.
     * 
     * @var int $position
     */
    private int $position;

    /**
     * Resource type.
     * 
     * @var string $type
     */
    private string $type;

    public function __get($name) {
        if ($name == '__table') return;
        if ($name == '__object') return;
        return $this->{$name};
    }

    /**
     * @param mixed $ID Resource ID
     * @param AdvancedORM $object
     */
    public function __construct($ID, $object) {

        $this->__table = $object->getTable() . '_resources';
        $this->__object = $object;

        if (is_object($ID)) {
            $this->__absorbObject($ID);
        }
        else {
            $res = DB::query("SELECT * FROM $this->__table WHERE ID = :ID", [
                'ID' => $ID
            ]);

            if (!$res->something) return;

            $this->__absorbObject($res->first);

        }

    }

    /**
     * Turn Database result into a ORMResource object.
     * 
     * @param Generic $obj
     */
    private function __absorbObject(Generic $obj) {
        $this->ID = $obj->ID;
        $this->file = $obj->file;
        $this->position = intval($obj->position);
        $this->type = $obj->type;
    }

    /**
     * Delete this resource.
     */
    public function delete() {
        $t = $this->__table;
        $ref = $this->__object->reference_column();

        DB::query("DELETE FROM $t WHERE ID = :ID", ['ID' => $this->ID]);

        DB::query("UPDATE $t SET position = position - 1 WHERE $ref = :ID AND type = :type AND position > :position",
        [
            'ID' => $this->__object->ID,
            'type' => $this->type,
            'position' => $this->position
        ]);
    }

    /**
     * Set resource file.
     * 
     * @param string $file
     */
    public function setFile(string $file) {
        $t = $this->__table;
        $this->file = $file;

        DB::query("UPDATE $t SET file = :file WHERE ID = :ID", [
            'ID' => $this->ID,
            'file' => $file
        ]);
    }

    /**
     * Move the position of this resource up.
     */
    public function moveUp() {
        $this->moveTo($this->position - 1);
    }

    /**
     * Move the position of this resource down.
     */
    public function moveDown() {
        $this->moveTo($this->position + 1);
    }

    /**
     * Move this resource to the first position.
     */
    public function moveToTop() {
        $this->moveTo($this->position - 0);
    }

    /**
     * Move this resource to the last position.
     */
    public function moveToBottom() {
        $resources = $this->__object->getResources($this->type);
        $last = $resources[sizeof($resources) - 1];

        $this->moveTo($last->position);
    }

    /**
     * Move this resource to another position.
     * 
     * @param int $position
     */
    public function moveTo(int $position) {
        if ($position < 1) return;
        if ($position == $this->position) return;

        $resources = $this->__object->getResources($this->type);
        $last = $resources[sizeof($resources) - 1];

        if ($position > $last->position)
            $position = $last->position;

        $destination = $last;
        for($i = sizeof($resources) - 1; $i >= 0; --$i) {
            $res = $resources[$i];
            if ($res->position == $position) {
                $destination = $res;
                break;
            }
        }

        if ($destination->position == $this->position) return;

        // Exchange positions
        $t = $this->__table;
        $ref = $this->__object->reference_column();
        if ($destination->position < $this->position) {
            DB::query("UPDATE $t SET position = position + 1 WHERE $ref = :ID AND type = :type AND position <= :position", [
                'ID' => $this->__object->ID,
                'type' => $this->type,
                'position' => $destination->position
            ]);
        }
        else {
            DB::query("UPDATE $t SET position = position - 1 WHERE $ref = :ID AND type = :type AND position >= :position", [
                'ID' => $this->__object->ID,
                'type' => $this->type,
                'position' => $destination->position
            ]);
        }

        DB::query("UPDATE $t SET position = :position WHERE ID = :ID", [
            'ID' => $this->ID,
            'position' => $destination->position
        ]);

        $this->position = $destination->position;
        $this->__object->getResources($this->type, true);
    }

}