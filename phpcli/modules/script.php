<?php

class CLI_Script extends CLI_Module {

    public function run() {

        if (empty($this->command)) {
            echo "No script specified\n";
            return;
        }

        $__path__ = Path::root() . '/' . Definitions::get('scripts') . "/$this->command.php";
        if (!file_exists($__path__)) {
            echo "Script '$this->command.php' does not exist";
            return;
        }

        include($__path__);
    }

    public function help() { ?>

        The Script command lets you run php scripts under the /scripts directory:

        - script <name>: run script

    <?php }

}