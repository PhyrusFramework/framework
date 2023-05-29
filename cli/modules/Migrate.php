<?php

class MigrateCommand extends Command {

    protected $command = 'migrate';

    public function run() {
        $this->do_migrate($this->first);
    }

    public function command_undo() {
        $this->do_undo($this->first);
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

    public function help() {?>

    - Use migrate to run all pending migrations.

        - Use migrate --fresh to clear database and run all migrations again from the beginning.

    - Use migrate <file> to run a specific migration. <file> is the name of the migration file, without .php.

        - Use migrate <file> --force to execute the migration even if it was already executed.

    - Use migrate:undo to undo all migrations.

    - Use migrate:undo <file> to undo a specific migration.

<?php }

}