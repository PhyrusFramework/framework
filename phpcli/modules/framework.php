<?php

class CLI_Framework extends CLI_Module {

    private static $env = "framework";

    public function command_version() {
        $json = Path::project() . '/composer.json';
        if (file_exists($json)) {
            $content = json_decode(file_get_contents($json), true);

            if (isset($content['require']) && isset($content['require']['phyrus/framework']))
                echo $content['require']['phyrus/framework'] . "\n";
            else
                echo "Phyrus not present in composer.json";
        } else {
            echo "composer.json not found.\n";
        }
    }

    public function command_package() {
        echo shell_exec('composer show phyrus/framework');
    }

    public function command_update() {
        echo shell_exec('composer update phyrus/framework');
    }

    public function help() {?>

        The Check command is used to view information about the
        framework.

        - version
        Your current version.

        - package
        See composer package info.

        - upgrade
        Download and install last version of the framework.

    <?php }

}