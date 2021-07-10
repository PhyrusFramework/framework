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
     */
    private function __checkDB_meta() {

        $table = $this->meta_table();
        
        if (isset(self::$_tables_checked[$table])) {
            return true;
        }

        if (!Config::get('development_mode')) return false;

        if (!DB::table_exists($table)){

            self::$_tables_checked[$table] = true;

            DB::create_table([
                'name' => $this->meta_table(),
                'columns' => [
                    [
                        'name' => $this->reference_column(),
                        'type' => 'INT',
                        'notnull' => true
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
     */
    private function __checkDB_translations() {

        $table = $this->translations_table();
        
        if (isset(self::$_tables_checked[$table])) {
            return true;
        }

        if (!Config::get('development_mode')) return false;

        if (!DB::table_exists($table)){

            self::$_tables_checked[$table] = true;

            DB::create_table([
                'name' => $this->translations_table(),
                'columns' => [
                    [
                        'name' => $this->reference_column(),
                        'type' => 'INT',
                        'notnull' => true
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
     */
    private function __checkDB_resources() {

        $table = $this->resources_table();
        
        if (isset(self::$_tables_checked[$table])) {
            return true;
        }

        if (!Config::get('development_mode')) return false;

        if (!DB::table_exists($table)){

            self::$_tables_checked[$table] = true;

            DB::create_table([
                'name' => $this->resources_table(),
                'columns' => [
                    [
                        'name' => $this->reference_column(),
                        'type' => 'INT',
                        'notnull' => true
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
    }

    /**
     * Delete this object.
     */
    public function delete() {

        $ref = $this->reference_column();

        $t = $this->meta_table();
        DB::query("DELETE FROM $t WHERE $ref = :ID", [
            'ID' => $this->ID
        ]);

        $t = $this->translations_table();
        DB::query("DELETE FROM $t WHERE $ref = :ID", [
            'ID' => $this->ID
        ]);

        $t = $this->resources_table();
        DB::query("DELETE FROM $t WHERE $ref = :ID", [
            'ID' => $this->ID
        ]);

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
     * Get object by meta
     * 
     * @param string $name
     * @param string $value
     * 
     * @return AdvancedORM[]
     */
    public static function getByMeta(string $metakey, string $metavalue = null) : array {

        $cl = get_called_class();
        $tmp = new $cl();

        $q2 = 'SELECT ' . $tmp->reference_column() . ' FROM ' . $tmp->meta_table() . ' WHERE meta_key = :meta';
        if ($metavalue != null) {
            $q2 .= ' AND meta_value = :value';
        }
        $q = 'SELECT * FROM ' . $tmp->getTable() . " WHERE ID IN ($q2)";

        $res = DB::query($q, [
            'meta' => $metakey,
            'value' => $metavalue
        ]);

        $list = [];
        foreach($res->result as $r) {
            $list[] = new $cl($r);
        }
        return $list;

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
            $this->__resources[$type][] = new ORMResource($res->first);
        }
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

    // STATIC

    /**
     * Find models by meta. Optionally specify the value.
     * 
     * @param string $meta
     * @param mixed $value = null
     */
    public static function byMeta(string $meta, $value = null) {

        $cl = get_called_class();
        $sample = new $cl();
        $t = $sample->getTable();
        $metat = $sample->meta_table();
        $ref = $sample->reference_column();

        $sub = "SELECT $ref FROM $metat WHERE meta_key = :key";
        $params = ['key' => $meta];
        if ($value == null) {
            $sub .= ' AND meta_value = :value';
            $params['value'] = $value;
        }

        $res = DB::query("SELECT * FROM $t WHERE ID IN ($sub)");

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