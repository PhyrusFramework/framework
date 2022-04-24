<?php

class CLI_Migrate extends CLI_Module {

    public function run() {

        require_once(__DIR__ . '/../Migration.php');

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

    public function help() { ?>

        The Migrate command lets you run migrations:

        - migrate: run all migrations

        - migrate <file name>: run a specific migration.

        - migrate undo: undo all migrations

        - migrate undo <file name>: undo a specific migration.

    <?php }

}