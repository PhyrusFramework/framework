<?php

class ConfigCommand extends Command {

    protected $command = 'config';

    public function command_add() {

        if (!$this->first) {
            echo 'File name not specified.';
            return;
        }

        if (Config::hasFile($this->first)) {
            echo "File $this->first.yaml already exists.";
            return;
        }

        Config::save($this->first, []);
        echo "File $this->first.yaml created.";

    }

    public function command_set() {

        if (!$this->first) {
            echo 'Key not specified';
            return;
        }

        if (!$this->second) {
            echo 'Value not specified';
            return;
        }

        Config::save($this->first, $this->second);
    }

    public function command_show() {
        if (!$this->first) {
            print_r(Config::get());
            return;
        }
        
        $v = Config::get($this->first);

        if (empty($v)) {
            echo "Key $this->first does not exist.";
            return;
        }

        print_r($v);
    }

    public function command_clear() {
        Config::clear();
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

        - clear
        Delete the cached configuration.
    
    <?php }

}