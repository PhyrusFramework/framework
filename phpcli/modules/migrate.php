<?php

class CLI_Migrate extends CLI_Module {

    public function run() {

        require_once(__DIR__ . '/../Migration.php');

        if ($this->command == 'create') {
            return $this->command_create();
        }

        if ($this->command == null) {
            $this->do_migrate();
        } else if ($this->command == 'undo') {
            if (!empty($this->params)) {
                $this->do_undo($this->params[0]);
            } else {
                $this->do_undo();
            }
        } else {
            $this->do_migrate($this->command);
        }
    }

    public function do_migrate($file = null) {
        if (isset($this->flags['fresh'])) {
            Migration::reset();
        }
        Migration::migrate($file, isset($this->flags['force']));
    }

    public function do_undo($file = null) {
        Migration::undo($file, isset($this->flags['force']));
    }

    public function command_create() {

        if (sizeof($this->params) < 1) {
            echo "\nMigration name not specified.\n";
            return;
        }

        $path = Path::root() . '/' . Definitions::get('migrations');
        if (!file_exists($path)) {
            mkdir($path);
        }
        $t = new Time();
        $name = $this->params[0];

        $filename = $t->format('YmdHis') . "_$name.php";

        ob_start();?>
<<?= '?' ?>php  

new Migration(
    function() {
        // DO
    },
    function() {
        // UNDO
    }
);<?php
        $content = ob_get_clean();
        file_put_contents("$path/$filename", $content);

        echo "\nMigration created at $path/$filename.\n";
    }

    public function help() { ?>

        The Migrate command lets you run migrations:

        - migrate create <name>: create a migration

        - migrate: run all migrations

        - migrate <file name>: run a specific migration.

        - migrate undo: undo all migrations

        - migrate undo <file name>: undo a specific migration.

    <?php }

}