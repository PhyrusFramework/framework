<?php

class CLI_Config extends CLI_Module {

    public function command_add() {

        if (sizeof($this->params) < 1) {
            echo 'File name not specified.';
            return;
        }

        $name = $this->params[0];

        if (Config::hasFile($name)) {
            echo "File $name.yaml already exists.";
            return;
        }

        Config::save($name, []);
        echo "File $name.yaml created.";

    }

    public function command_set() {

        if (sizeof($this->params) < 1) {
            echo 'Key not specified';
            return;
        }

        if (sizeof($this->params) < 2) {
            echo 'Value not specified';
            return;
        }

        Config::save($this->params[0], $this->params[1]);
    }

    public function command_show() {
        if (sizeof($this->params) == 0) {
            print_r(Config::get());
            return;
        }
        
        $key = $this->params[0];
        $v = Config::get($key);

        if (empty($v)) {
            echo "Key $key does not exist.";
            return;
        }

        print_r($v);
    }

    public function help() {?>
    
        The Config command is used to read or
        modify the config.json file of the project.

        - set <key> <value>
        Same as php Config::save(...)

        - show
        Print all configurations.

        - show <key>
        Display configuration.
    
    <?php }

}