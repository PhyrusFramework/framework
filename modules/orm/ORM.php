<?php

abstract class ORM extends TableORM implements JsonSerializable {

    /**
     * ID
     * 
     * @var int $ID
     */
    public ?int $ID = 0;

    public function __construct($ID = null) {
        $this->__inflate();
        if ($ID == null) return;

        if (is_object($ID)) {
            $this->__absorbObject($ID);
        }
        else {
            $row = DB::query($this->getTable())
            ->where('ID', $ID)
            ->first();

            if (!$row) return;
            $this->__absorbObject($row);
        }

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
     * Add columns to the table.
     */
    protected function __addAdditionalColumns(&$columns) {

        array_unshift($columns, [
            'name' => 'ID',
            'type' => 'BIGINT',
            'unsigned' => true,
            'notnull' => true,
            'auto_increment' => true,
            'primary' => true
        ]);

        $columns[] = [
            'name' => 'created_at',
            'type' => 'DATETIME',
            'notnull' => true
        ];
    }

    /**
     * Find the ID by the time it was created.
     * 
     * @param string $creationTime
     * 
     * @return int
     */
    private function __findID(string $creationTime) : int {

        $res = DB::query($this->getTable())
        ->where('created_at', $creationTime)
        ->orderBy('ID', 'DESC')
        ->first();

        if (!$res) return 0;
        $this->{'ID'} = intval($res->ID);
        return $this->ID;

    }

    /**
    * Has this model been already inserted?
     * 
     * @return bool
     */
    public function isCreated() : bool {
        return isset($this->{'ID'}) && $this->ID > 0;
    }

    /**
     * Creation date.
     * 
     * @return string
     */
    public function creationDate() : string {
        if (!$this->isCreated()) return '';
        return $this->created_at;
    }

    /**
     * Exists in the database?
     * 
     * @return bool
     */
    public function exists() : bool {
        if (!$this->isCreated()) return false;
        return DB::query($this->getTable())
        ->where('ID', $this->ID)
        ->count() > 0;
    }

    /**
     * Delete.
     * 
     * @return ORM self
     */
    public function delete() : ORM {
        if (!$this->isCreated()) return $this;
        DB::query($this->getTable())
        ->where('ID', $this->ID)
        ->delete();

        return $this;
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
     * 
     * @return ORM
     */
    protected function __update($columns = []) : ORM {

        $q = DB::query($this->getTable())
        ->where('ID', $this->ID);

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
            $cols = $this->__columns();
        } else {
            $aux = $cols;
            $cols = [];
            foreach($this->__columns() as $col) {
                if (in_array($col['name'], $aux)) {
                    $cols[] = $col;
                }
            }
        }

        foreach($cols as $col) {

            $name = $col['name'];

            if ($name == 'ID') {
                continue;
            }

            $val = $this->{$name};
            if (!empty($col['allowHTML'])) {
                $val = InsecureString::instance($val);

                if (empty($col['allowJs'])) {
                    $val->removeScriptTags();
                }
            }

            $q->set($name, $val);
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

        if (empty($this->{'created_at'})) {
            $this->{'created_at'} = now();
        }

        foreach($columns as $col) {
            $name = $col['name'];

            if ($name == 'ID') {
                continue;
            }

            $val = $this->{$name};
            if (!empty($col['allowHTML'])) {
                $val = InsecureString::instance($val);

                if (empty($col['allowJs'])) {
                    $val->removeScriptTags();
                }
            }

            $q->set($name, $val);
        }

        $q->insert();
        $this->__findID($this->created_at);
    }

    /**
     * Convert ORM object to array.
     * 
     * @return array
     */
    public function toArray(...$columns) {

        $arr = [
            'ID' => $this->ID,
            'created_at' => $this->created_at
        ];

        if (empty($columns)) {

            $def = new DBBuilder();
            $this->Definition($def);
            $def = $def->toArray();

            foreach($def as $table => $cols) {
                foreach($cols as $col) {
                    $arr[$col['name']] = $this->{$col['name']};
                }
            }

            return $arr;
        }

        foreach($columns as $col) {
            $arr[$col] = $this->{$col};
        }
        return $arr;
    }

    ////////// Static methods

    /**
     * Find the object with this ID.
     * 
     * @param ID
     * 
     * @return ORM|null
     */
    public static function findID($ID) {
        return self::findOne('ID = :ID', ['ID' => intval($ID)]);
    }

    /**
     * Generate a dictionary array where the key is a property of the model, by default the ID.
     * 
     * @param callable Function to apply query conditions
     * @param string Property to be used as key, by default ID
     * 
     * @return array Array of models
     */
    public static function dictionary(callable $queryTransform = null, string $property = 'ID') {
        $q = self::query();
        if ($queryTransform) {
            $queryTransform($q);
        }
        $list = $q->get();
        $dic = [];

        foreach($list as $m) {
            $dic[$m->{$property}] = $m;
        }

        return $dic;
    }

}