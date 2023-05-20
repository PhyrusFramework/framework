<?php

class CLI_Script extends CLI_Module {

    public function run() {

        if ($this->command == 'create') {
            $this->command_create();
            return;
        }

        if (empty($this->command)) {
            echo "\nNo script specified\n";
            return;
        }

        $__path__ = Path::root() . '/' . Definitions::get('scripts') . "/$this->command";

        if (!file_exists($__path__)) {
            $__path__ .= '.php';
        }

        if (!file_exists($__path__)) {
            echo "\nScript '$this->command.php' does not exist.\n";
            return;
        }

        include($__path__);
    }

    public function command_create() {

        if (sizeof($this->params) == 0) {
            echo "\nScript name not specified.\n";
            return;
        }

        $name = $this->params[0];

        $folder = Path::root() . '/' . Definitions::get('scripts');

        create_folder($folder);

        $file =  "$folder/$name.php";

        if (file_exists($file)) {
            echo "\nScript $name.php already exists.\n";
            return;
        }

        file_put_contents($file, "<?php\n\n//Run php phyrus script $name\necho 'Script works!';");

        echo "\nScript created at $file\n";
    }

    public function help() { ?>

        The Script command lets you run php scripts under the /scripts directory:

        - script create <name>: create a script file.

        - script <name>: run script

    <?php }

}