<?php

class CLI_Install extends CLI_Module {

    public function run() {
        if ($this->command == null) {
            echo "\nPackage name not specified.\n";
            return;
        }

        $path = Path::root() . "/vendor/$this->command";
        if (!file_exists($path)) {
            echo "\nPackage is not installed. Try composer require $this->command\n";
            return;
        }

        $path = "$path/install.php";
        if (!file_exists($path)) {
            echo "\nThis package does not contain an installation script.\n";
        }

        require_once($path);
        echo "\nPackage installation completed.\n";
    }

}