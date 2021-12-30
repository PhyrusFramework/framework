<?php

class Migration {

    function __construct(callable $migrate, callable $undo) {

        global $_SQL_MIGRATION_MODE;

        if ($_SQL_MIGRATION_MODE == 'migrate') {
            $migrate();
        } else if ($_SQL_MIGRATION_MODE == 'undo') {
            $undo();
        }

    }

    private static function run($__file__) {
        include($__file__);
    }

    static function reset() {
        $path = Path::root() . '/' . Definitions::get('migrations');
        if (!is_dir($path) && !file_exists($path)) return;

        $historyFile = "$path/history.json";
        if (file_exists($historyFile)) {
            file_put_contents($historyFile, '{}');
        }
    }
    
    static function migrate($file = null, $force = false) {

        global $_SQL_MIGRATION_MODE;
        $_SQL_MIGRATION_MODE = 'migrate';
    
        $path = Path::root() . '/' . Definitions::get('migrations');
        if (!is_dir($path) && !file_exists($path)) return;
    
        if ($file == null)
            $files = Folder::instance($path)->subfiles('php');
        else
            $files = ["$path/$file.php"];
        
        $historyFile = "$path/history.json";
        $history = [];
        if (file_exists($historyFile)) {
            $history = JSON::fromFile($historyFile)->toArray();
        }
    
        foreach($files as $file) {
            if (!file_exists($file)) continue;
            $name = File::instance($file)->name(false);
    
            if (!$force && isset($history[$name])) {

                if (defined('USING_CLI')) {
                    echo "The migration '$name' wants to be executed but it was already migrated. Run with --force to execute anyway.\n";
                }

                continue;
            } else {
                $history[$name] = datenow();
                self::run($file);
            }
    
        }
    
        JSON::instance($history)->saveTo($historyFile);
    }

    static function undo($file = null, $force = false) {

        global $_SQL_MIGRATION_MODE;
        $_SQL_MIGRATION_MODE = 'undo';
    
        $path = Path::root() . '/' . Definitions::get('migrations');
        if (!is_dir($path) && !file_exists($path)) return;

        $historyFile = "$path/history.json";
        if (!file_exists($historyFile)) return;
        
        $history = JSON::fromFile($historyFile)->toArray();
    
        if ($file == null) {
            $aux = array_keys($history);
            $files = [];
            for($i = sizeof($aux) - 1; $i >= 0; --$i) {
                $files[] = "$path/" . $aux[$i] . ".php";
            }
        } else
            $files = ["$path/$file.php"];
    
        foreach($files as $file) {
            if (!file_exists($file)) continue;
            $name = File::instance($file)->name(false);
    
            if (!$force && !isset($history[$name])) {

                if (defined('USING_CLI')) {
                    echo "The migration '$name' wants to be undone but was never migrated. Run with --force to undo anyway.\n";
                }

                continue;
            } else {
                self::run($file);

                if (isset($history[$name]))
                    unset($history[$name]);
            }
    
        }
    
        JSON::instance($history)->saveTo($historyFile);
    }

}