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
     * What to add on conversion to JSON
     * 
     * @var array
     */
    private $__onJson = [
        'translations' => null,
        'metas' => null,
        'resources' => null
    ];

    /**
     * Get actions to do on JSON serialization.
     * 
     * @return Generic
     */
    public function onJSON() : Generic {

        $gen = new Generic();

        $gen
        ->set('addTranslations', function($language = null) use ($gen) {
            $this->__onJson['translations'] = $language;
            return $gen;
        })
        ->set('addMetas', function(...$names) use ($gen) {
            $this->__onJson['metas'] = sizeof($names) > 0 ? $names : [];
            return $gen;
        })
        ->set('addResources', function(...$types) use ($gen) {
            $this->__onJson['resources'] = sizeof($types) > 0 ? $types : [];
            return $gen;
        });

        return $gen;

    }

    public function jsonSerialize() {
        $value = parent::jsonSerialize();

        if ($this->__onJson['translations'] !== null) {
            $trans = $this->getTranslations($this->__onJson['translations']);
            $value['translations'] = $trans;
        }

        if ($this->__onJson['metas'] !== null) {
            $metas = $this->getMetas(...$this->__onJson['metas']);
            $value['meta'] = $metas;
        }

        if ($this->__onJson['resources'] !== null) {
            $res = $this->getResourcesByTypes(...$this->__onJson['resources']);
            $value['resources'] = $res;
        }

        return $value;
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

        if (!Config::get('project.development_mode')) return true;

        $existed = true;
        if (!DB::tableExists($table)){
            $existed = false;
            self::$_tables_checked[$table] = true;

            DB::createTable($this->meta_table(), [
                [
                    'name' => $this->reference_column(),
                    'type' => 'BIGINT',
                    'notnull' => true,
                    'foreign' => $this->getTable() . '(ID)',
                    'primary' => true
                ],
                [
                    'name' => 'meta_key',
                    'type' => 'VARCHAR(100)',
                    'notnull' => true,
                    'primary' => true
                ],
                [
                    'name' => 'meta_value',
                    'type' => 'TEXT'
                ],
                [
                    'name' => 'createdAt',
                    'type' => 'DATETIME'
                ]
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

        if (!Config::get('project.development_mode')) return true;

        $existed = true;
        if (!DB::tableExists($table)){
            $existed = false;
            self::$_tables_checked[$table] = true;

            DB::createTable($this->translations_table(), [
                [
                    'name' => $this->reference_column(),
                    'type' => 'BIGINT',
                    'notnull' => true,
                    'foreign' => $this->getTable() . '(ID)',
                    'primary' => true
                ],
                [
                    'name' => 'name',
                    'type' => 'VARCHAR(100)',
                    'notnull' => true,
                    'primary' => true
                ],
                [
                    'name' => 'locale',
                    'type' => 'VARCHAR(10)',
                    'notnull' => true,
                    'primary' => true
                ],
                [
                    'name' => 'value',
                    'type' => 'TEXT'
                ]
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

        if (!Config::get('project.development_mode')) return true;

        $existed = true;
        if (!DB::tableExists($table)){
            $existed = false;
            self::$_tables_checked[$table] = true;

            DB::createTable($this->resources_table(), [
                [
                    'name' => $this->reference_column(),
                    'type' => 'BIGINT',
                    'notnull' => true,
                    'foreign' => $this->getTable() . '(ID)',
                ],
                [
                    'name' => 'type',
                    'type' => 'VARCHAR(100)',
                    'notnull' => true
                ],
                [
                    'name' => 'position',
                    'type' => 'INT',
                    'notnull' => true
                ],
                [
                    'name' => 'file',
                    'type' => 'TEXT'
                ]
            ]); 
        }

        return $existed;
    }

    /**
     * Delete this object.
     * 
     * @return AdvancedORM
     */
    public function delete() : AdvancedORM {

        $ref = $this->reference_column();

        $t = $this->meta_table();
        if (DB::tableExists($t)) {

            DB::query($t)
            ->where($ref, $this->ID)
            ->delete();
        }

        $t = $this->translations_table();
        if (DB::tableExists($t)) {
            DB::query($t)
            ->where($ref, $this->ID)
            ->delete();        }

        $t = $this->resources_table();
        if (DB::tableExists($t)) {
            DB::query($t)
            ->where($ref, $this->ID)
            ->delete();
        }

        parent::delete();
        return $this;
    }

    /**
     * Set meta value
     * 
     * @param string $name
     * @param mixed $value
     * 
     * @return AdvancedORM
     */
    public function setMeta(string $name, $value) : AdvancedORM {
        $this->__metas[$name] = $value;

        $this->__checkDB_meta();

        $t = $this->meta_table();
        $ref = $this->reference_column();

        if ($value == null) {
            DB::query($t)
            ->where($ref, $this->ID)
            ->where('meta_key', $name)
            ->delete();
            return $this;
        }

        $res = DB::query($t)
        ->where($ref, $this->ID)
        ->where('meta_key', $name)
        ->limit(1)
        ->get();

        $v = $value;
        if (is_array($value)) {
            $v = new InsecureString($value);
        } else if (is_bool($value)) {
            $v = $value ? '1' : '0';
        } else if (!$value instanceof InsecureString) {
            $v = "$v";
        }

        if (sizeof($res)) {
            DB::query($t)
            ->set('meta_value', $v)
            ->set('createdAt', datenow())
            ->where($ref, $this->ID)
            ->where('meta_key', $name)
            ->update();
        }
        else {
            DB::query($t)
            ->set($ref, $this->ID)
            ->set('meta_key', $name)
            ->set('meta_value', $v)
            ->set('createdAt', datenow())
            ->insert();
        }

        return $this;
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

        $res = DB::query($t)
        ->where($ref, $this->ID)
        ->where('meta_key', $name)
        ->limit(1)
        ->get();

        if (!sizeof($res)) return $default;

        $value = $res[0]->meta_value;
        if (is_array($default)) {
            if ($json = JSON::isJSON($value)) {
                $value = $json;
            }
        }

        $this->__metas[$name] = $value;
        return $value;
    }

    /**
     * Get all or specified metas for this object.
     * 
     * @param array names
     * 
     * @return array
     */
    public function getMetas(...$names) {

        $t = $this->meta_table();
        $ref = $this->reference_column();

        if (empty($names)) {

            $res = DB::query($t)
            ->where($ref, $this->ID)
            ->get();

        } else {

            $res = DB::query($t)
            ->where($ref, $this->ID)
            ->where('meta_key', 'IN', $names)
            ->get();

        }

        $arr = [];
        foreach($res as $row) {
            $this->__metas[$row->meta_key] = $row->meta_value;
            $arr[$row->meta_key] = $row->meta_value;
        }

        return $arr;

    }

    /**
     * Set object translation.
     * 
     * @param string $name
     * @param string $locale
     * @param string $value
     * 
     * @return AdvancedORM
     */
    public function setTranslation(string $name, string $locale, $value) : AdvancedORM {
        if (!isset($this->__translations[$locale]))
            $this->__translations[$locale] = [];
        $this->__translations[$locale][$name] = $value;

        $this->__checkDB_translations();

        $t = $this->translations_table();
        $ref = $this->reference_column();

        if ($value == null) {

            DB::query($t)
            ->where($ref, $this->ID)
            ->where('name', $name)
            ->where('locale', $locale)
            ->delete();

            return $this;
        }

        $res = DB::query($t)
        ->where($ref, $this->ID)
        ->where('name', $name)
        ->where('locale', $locale)
        ->count();

        if ($res == 0) {
            DB::query($t)
            ->set('value', $value)
            ->where($ref, $this->ID)
            ->where('name', $name)
            ->where('locale', $locale)
            ->update();
        }
        else {
            DB::query($t)
            ->set($ref, $this->ID)
            ->set('name', $name)
            ->set('locale', $locale)
            ->set('value', $value)
            ->insert();
        }

        return $this;
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

        $res = DB::query($t)
        ->where($ref, $this->ID)
        ->where('name', $name)
        ->where('locale', $locale)
        ->first();

        if (!$res)
            return $default;

        if (!isset($this->__translations[$locale]))
            $this->__translations[$locale] = [];

        $this->__translations[$locale][$name] = $res->value;

        return $res->value;

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

                $res = DB::query($t)
                ->where($ref, $this->ID)
                ->where('name', $name)
                ->first();
            
                if (!$res)
                    return $default;
        
                return $res->value;

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

        $query = DB::query($tr)->where($ref, $this->ID);

        if ($language != null) {

            $query->where('locale', $language);
            $res = $query->get();
    
            $translations = [];

            foreach($res as $row) {    
                $translations[$row->name] = $row->value;
            }
    
            return $translations;

        }

        $res = $query->get();

        $translations = [];

        foreach($res as $row) {
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
            $res = DB::query($t)
            ->where($ref, $this->ID)
            ->orderBy('position ASC')
            ->get();
        }
        else {
            if (!$refresh && isset($this->__resources[$type]))
                return $this->__resources[$type];

            $res = DB::query($t)
            ->where($ref, $this->ID)
            ->where('type', $type)
            ->orderBy('position ASC')
            ->get();

        }

        $list = [];
        foreach($res as $r) {
            $list[] = new ORMResource($r, $this);
        }

        if ($type != null)
            $this->__resources[$type] = $list;
            
        return $list;

    }

    /**
     * Get all files for this object or filter by type.
     * 
     * @return array
     */
    public function getResourcesByTypes(...$types) : array {

        $this->__checkDB_resources();

        $t = $this->resources_table();
        $ref = $this->reference_column();

        if (sizeof($types) == 0) {
            $res = DB::query($t)
            ->where($ref, $this->ID)
            ->orderBy('position ASC')
            ->get();
            
            $list = [];

            foreach($res as $row) {
                if (!isset($list[$row->type])) {
                    $list[$row->type] = [];
                }

                $list[$row->type][] = $row->file;
            }

            return $list;

        }

        $res = DB::query($t)
        ->where($ref, $this->ID)
        ->where('type', 'IN', $types)
        ->orderBy('position ASC');

        $list = [];

        foreach($res->result as $row) {
            $list[] = $row->file;
        }

        return $list;

    }

    /**
     * Get a single resource.
     * 
     * @param string $type
     * @param int $position [Default first]
     * 
     * @return ORMResource|null
     */
    public function getResource(string $type, int $position = 1) {
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

        $res = DB::query($t)
        ->where($ref, $this->ID)
        ->where('file', $file)
        ->first();

        if (!$res) return null;
        return new ORMResource($res, $this);
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
        $res = DB::query($t)
        ->where($ref, $this->ID)
        ->where('type', $type)
        ->orderBy('position DESC')
        ->first();

        $position = $res ? intval($res->position) + 1 : 1;

        DB::query($t)
        ->set($ref, $this->ID)
        ->set('type', $type)
        ->set('file', $file)
        ->set('position', $position)
        ->insert();

        $res = DB::query($t)
        ->where($ref, $this->ID)
        ->where('type', $type)
        ->where('file', $file)
        ->orderBy('position DESC')
        ->first();

        if ($res) {
            $resource = new ORMResource($res, $this);
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

        $q = DB::query($t)
        ->where($ref, $this->ID);

        if ($type != null) {
            $q->where('type', $type);
        }

        $q->delete();

        if ($type == null) $this->__resources = [];
        else $this->__resources[$type] = [];
    }

    /**
     * Override parent dropTable().
     * Drops all tables.
     */
    public static function dropTable() {
        self::dropTables();
    }
    
    /**
     * Drop database tables
     */
    public static function dropTables() {
        $cl = get_called_class();
        $sample = new $cl();

        if (DB::tableExists($sample->translations_table()))
        DBTable::instance($sample->translations_table())->drop();

        if (DB::tableExists($sample->resources_table()))
        DBTable::instance($sample->resources_table())->drop();

        if (DB::tableExists($sample->meta_table()))
        DBTable::instance($sample->meta_table())->drop();

        if (DB::tableExists($sample->getTable()))
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
                $operator = '=';
                if ($value != null) {
        
                    if (is_string($value)) {
                        if (strpos($value, '%') === FALSE) {
                            $operator = '=';
                        } else {
                            $operator = 'LIKE';
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

        $res = DB::query($metat)
        ->select("$t.*")
        ->join($t, "$t.ID = $metat.$ref")
        ->where("$metat.meta_key", $name)
        ->orderBy("$order $direction")
        ->get();

        $list = [];
        foreach($res as $row) {
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

        $q = DB::query($tr)
        ->join($t, "$t.ID = $tr.$ref")
        ->where("$tr.name", $name)
        ->orderBy("$tr.value $direction");

        if (isset($options['locale'])) {
            $q->where('locale', $options['locale']);

        } else if (isset($options['locales'])) {
            $q->where('locale', 'IN', $options['locales']);
        }

        $res = $q->get();

        $list = [];
        foreach($res as $row) {
            $list[] = new $cl($row);
        }
        return $list;

    }

    /**
     * Find the object with this ID.
     * 
     * @param int ID
     * 
     * @return AdvancedORM|null
     */
    public static function findID(int $ID) {
        return parent::findID($ID);
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
            $res = DB::query($this->__table)
            ->where('ID', $ID)
            ->first();

            if (!$res) return;

            $this->__absorbObject($res);

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

        DB::query($t)
        ->where('ID', $this->ID)
        ->delete();

        DB::query($t)
        ->set('position', 'position - 1', false)
        ->where($ref, $this->__object->ID)
        ->where('type', $this->type)
        ->where('position', $this->position)
        ->update();
    }

    /**
     * Set resource file.
     * 
     * @param string $file
     */
    public function setFile(string $file) {
        $t = $this->__table;
        $this->file = $file;

        DB::query($t)
        ->set('file', $file)
        ->where('ID', $this->ID)
        ->update();
    }

    /**
     * Get the resource position.
     * 
     * @return int $position
     */
    public function getPosition() : int {
        return $this->position;
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
            DB::query($t)
            ->set('position', 'position + 1', false)
            ->where($ref, $this->__object->ID)
            ->where('type', $this->type)
            ->where('position', '<=', $destination->position)
            ->update();
        }
        else {
            DB::query($t)
            ->set('position', 'position - 1', false)
            ->where($ref, $this->__object->ID)
            ->where('type', $this->type)
            ->where('position', '>=', $destination->position)
            ->update();
        }

        DB::query($t)
        ->set('position', $destination->position)
        ->where('ID', $this->ID)
        ->update();

        $this->position = $destination->position;
        $this->__object->getResources($this->type, true);
    }

}