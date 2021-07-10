<?php

class CLI_Config extends CLI_Module {

    public function command_get() {
        $v = Config::_get($this->params);

        if (empty($v)) {
            echo "\nIt's empty or does not exist.\n";
            return;
        }

        if (is_array($v))
            arr($v)->print(false);
        else
            echo "\n$v\n";
    }

    public function command_set() {
        Config::save($this->params);
    }

    public function command_show() {
        global $FRAMEWORK_CONFIG;
        $this->displayArray($FRAMEWORK_CONFIG);
    }

    public function help() {?>
    
        The Config command is used to modify
        the config.json file of the project.

        - set <param1> <param2> ... <value>
        Same as php Config::save(...);
        Route to the value and the last item is the value.

        - get <param1> <param2> ...
        Same as php Config::get(...)

        - show
        Print all configuration.
    
    <?php }

}