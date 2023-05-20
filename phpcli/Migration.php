<?php

class Migration {

    function __construct(callable $migrate, callable $undo) {

        global $_MIGRATION_MODE;

        if ($_MIGRATION_MODE == 'migrate') {
            $migrate();
        } else if ($_MIGRATION_MODE == 'undo') {
            $undo();
        }

    }

    private static function run($__file__) {
        include($__file__);
    }

    private static function getStore() {

        if (DBConnected()) {
            return new MigrationDBStore();
        } else {
            return new MigrationJSONStore();
        }
    }

    static function reset() {
        self::getStore()->reset();
    }
    
    static function migrate($file = null, $force = false) {

        global $_MIGRATION_MODE;
        $_MIGRATION_MODE = 'migrate';
    
        $path = Path::root() . '/' . Definitions::get('migrations');
        if (!is_dir($path) && !file_exists($path)) return;

        $store = self::getStore();
    
        $files = $file == null ? Folder::instance($path)->subfiles('php') : ["$path/$file.php"]; 
        $notExecuted = $force ? $files : $store->filterNotExecuted($files, $file != null);

        $names = [];

        foreach($notExecuted as $file) {
            $name = File::instance($file)->name(false);
            if (!file_exists($file)) {
                if ($file != null && defined('USING_CLI')) {
                    echo "The migration '$name' does not exist.\n";
                }
                continue;
            }
            self::run($file);
            $names[] = $name;
            echo "Migration $name completed.\n";
        }

        $store->add($names);

    }

    static function undo($file = null, $force = false) {

        global $_MIGRATION_MODE;
        $_MIGRATION_MODE = 'undo';
    
        $path = Path::root() . '/' . Definitions::get('migrations');
        if (!is_dir($path) && !file_exists($path)) return;

        $store = self::getStore();

        $files = $file == null ? Folder::instance($path)->subfiles('php') : ["$path/$file.php"]; 
        $executed = $force ? $files : $store->filterExecuted($files, $file != null);

        $names = [];

        foreach($executed as $file) {
            $name = File::instance($file)->name(false);
            if (!file_exists($file)) {
                if ($file != null && defined('USING_CLI')) {
                    echo "The migration '$name' does not exist.\n";
                }
                continue;
            }

            self::run($file);
            $names[] = $name;
        }

        $store->remove($names);
    }

}

class MigrationDBStore {

    private function checkTable() {
        if (!DB::tableExists('migrations')) {
            DB::createTable('migrations', [
                [
                    'name' => 'name',
                    'type' => 'VARCHAR(100)'
                ],
                [
                    'name' => 'migratedAt',
                    'type' => 'DATETIME'
                ]
            ]);
            return false;
        }
        return true;
    }

    public function reset() {
        if ($this->checkTable()) {
            DB::run('DELETE FROM migrations');
        }
    }

    public function filterNotExecuted($files, $specific = false) {

        $notExecuted = [];
        $this->checkTable();
        $res = DB::run('SELECT * FROM migrations')->result;

        foreach($files as $file) {
            $name = File::instance($file)->name(false);

            $found = false;
            foreach($res as $row) {
                if ($row->name == $name) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $notExecuted[] = $file;
            } else if ($specific) {
                if (defined('USING_CLI')) {
                    echo "The migration '$name' wants to be executed but it was already migrated. Run with --force to execute anyway.\n";
                }
            }
        }

        return $notExecuted;
    }
    
    public function add($names) {
        $this->checkTable();
        foreach($names as $name) {
            
            $count = DB::run('SELECT COUNT(*) as count FROM migrations WHERE name = :name', [
                'name' => $name
            ])->first->count;

            if (intval($count) > 0) {
                DB::run('UPDATE migrations SET migratedAt = :now WHERE name = :name', [
                    'name' => $name,
                    'now' => datenow()
                ]);
            } else {
                DB::run('INSERT INTO migrations (name, migratedAt) VALUES (:name, :now)', [
                    'name' => $name,
                    'now' => datenow()
                ]);
            }

        }
    }

    public function filterExecuted($files, $specific = false) {

        $executed = [];
        $this->checkTable();
        $rows = DB::run("SELECT * FROM migrations ORDER BY migratedAt DESC")->result;

        foreach($files as $f) {
            $n = File::instance($f)->name(false);
            if (!file_exists($f)) {
                if ($specific && defined('USING_CLI')) {
                    echo "The migration '$n' does not exist.\n";
                }
                continue;
            }

            $found = false;
            foreach($rows as $m) {
                if ($m->name == $n) {
                    $executed[$f] = $m->migratedAt . "_$m->ID";
                    $found = true;
                    break;
                }
            }
            if ($specific && !$found && defined('USING_CLI')) {
                echo "The migration '$n' wants to be undone but it's not migrated. Run with --force to undo anyway.\n";
            }
        }

        arsort($executed);

        $aux = [];
        foreach($executed as $name => $date) {
            $aux[] = $name;
        }

        return $aux;

    }

    public function remove($names) {
        if (sizeof($names) == 0) return;
        $this->checkTable();
        DB::run("DELETE FROM migrations WHERE name IN :names", [
            'names' => $names
        ]);
    }

}

class MigrationJSONStore {

    private function filepath() {
        $path = Path::root() . '/' . Definitions::get('migrations');

        if (!is_dir($path) || !file_exists($path)) {
            mkdir($path);
        }

        $historyFile = "$path/history.json";

        if (!file_exists($historyFile)) {
            file_put_contents($historyFile, '{}');
        }

        return $historyFile;
    }

    public function reset() {
        file_put_contents($this->filepath(), '{}');
    }

    public function filterNotExecuted($files, $specific = false) {

        $historyFile = $this->filepath();
        $history = [];
        if (file_exists($historyFile)) {
            $history = JSON::fromFile($historyFile)->toArray();
        }
    
        $notExecuted = [];

        foreach($files as $file) {
            if (!file_exists($file)) continue;
            $name = File::instance($file)->name(false);
    
            if (isset($history[$name])) {

                if ($specific && defined('USING_CLI')) {
                    echo "The migration '$name' wants to be executed but it was already migrated. Run with --force to execute anyway.\n";
                }

                continue;
            } else {
                $notExecuted[] = $file;
            }
    
        }

        return $notExecuted;

    }
    
    public function add($names) {
        $historyFile = $this->filepath();
        $history = [];
        if (file_exists($historyFile)) {
            $history = JSON::fromFile($historyFile)->toArray();
        }

        foreach($names as $name) {
            $history[$name] = datenow();
        }

        JSON::instance($history)->saveTo($historyFile);
    }

    public function filterExecuted($files, $specific = false) {

        $historyFile = $this->filepath();
        $history = [];
        if (file_exists($historyFile)) {
            $history = JSON::fromFile($historyFile)->toArray();
        }
    
        $executed = [];
        arsort($history);

        $found = false;
        foreach($history as $name => $date) {

            foreach($files as $f) {
                $n = File::instance($f)->name(false);
                if ($name == $n) {
                    $executed[] = $f;
                    $found = true;
                    break;
                }
            }

        }
        if ($specific && !$found && defined('USING_CLI')) {
            $n = File::instance($files[0])->name(false);
            echo "The migration '$n' wants to be undone but it's not migrated. Run with --force to undo anyway.\n";
        }

        return $executed;

    }

    public function remove($names) {
        $historyFile = $this->filepath();
        $history = [];
        if (file_exists($historyFile)) {
            $history = JSON::fromFile($historyFile)->toArray();
        }

        foreach($names as $name) {
            unset($history[$name]);
        }

        JSON::instance($history)->saveTo($historyFile);
    }

}