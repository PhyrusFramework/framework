<?php

class CLI_Module {

    private $name;
    protected $command;
    protected $params;
    protected $flags;

    public function __construct($name) {
        $this->name = $name;
    }

    public function init($cli) {

        $this->flags = $cli->flags;

        if (sizeof($cli->params) == 0) return;

        $this->command = $cli->params[0];
        $params = [];
        for($i = 1; $i < sizeof($cli->params); ++$i) {
            $params[] = $cli->params[$i];
        }
        $this->params = $params;
        
    }

    public function run() {
        
        if (empty($this->command) || $this->command == 'help') {
            $this->help();
            return;
        }

        $func = "command_$this->command";

        if (!method_exists($this, $func)) {
            echo "Command '$this->name $this->command' not recognized.\n";
        }
        else {
            $this->$func();
        }

    }

    public function help() {?>

        The command '<?php echo $this->name; ?>' does not offer any help information.

    <?php }

}