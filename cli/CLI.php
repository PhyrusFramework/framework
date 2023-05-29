<?php
require_once(__DIR__ . '/Command.php');
php_in(__DIR__ . '/modules');

class CLI {

    private array $_modules = [];

    /**
     * Add new module to the CLI
     * 
     * @param string $command
     * @param string $class
     */
    public function register(string $command, Command $commandObject) {
        $this->_modules[$command] = $commandObject;
    }

    public function autoregisterCommands() {

        $classes = getSubclassesOf('Command');
        foreach($classes as $cl) {
            $obj = new $cl();
            $name = $obj->getCommand();
            if (!empty($name)) {
                $this->register($name, $obj);
            }
        }
    }

    /**
     * Params of the current CLI request.
     * 
     * @var array $params
     */
    public array $params;

    /**
     * Flags set in the command.
     * 
     * @var array $flags
     */
    public array $flags;

    /**
     * Command keyword.
     * 
     * @var string $command
     */
    private string $command;

    /**
     * Current used Command
     * 
     * @var Command
     */
    private Command $module;

    public function __construct($args) {
        if (sizeof($args) < 2) return;

        /////////////////// GET ARGUMENTS
        $params = [];
        $flags = [];
        for($i = 1; $i<sizeof($args); ++$i) {
            $p = $args[$i];

            if (strlen($p) > 2 && substr($p, 0, 2) == '--') {
                if (strpos($p, '=') !== FALSE) {
                    $parts = explode('=', substr($p, 2));
                    $flags[$parts[0]] = $parts[1];
                }
                else {
                    $flags[substr($p, 2)] = true;
                }
            }
            else {
                if (empty($this->command))
                    $this->command = $p;
                else
                    $params[] = $p;
            }
        }
        $this->params = &$params;
        $this->flags = &$flags;
        /////////////////////////

        if (isset($this->flags['database']) || 
        in_array($this->command, ['test', 'migrate', 'script', 'install'])) {
            define('CLI_DATABASE', true);
        }

        $this->autoregisterCommands();
        $this->init();
    }

    /**
     * Check if command is registered
     * 
     * @param string Command name
     * 
     * @return Command|null
     */
    private function hasCommand(string $command) : Command|null {

        $c = strpos($command, ':') === FALSE ?
            $command : explode(':', $command)[0];

        $action = '';
        if ($c != $command) {
            $action = explode(':', $command)[1];
        }

        foreach(array_keys($this->_modules) as $cmd) {
            if ($cmd == $c) {
                $this->_modules[$cmd]->action = $action;
                return $this->_modules[$cmd];
            }
        }
        
        return null;
    }

    /**
     * Initialize CLI before running.
     */
    public function init() {

        $this->module = $this->hasCommand($this->command);
        if ($this->module) {
            $this->module->init($this);
        }

        // Unset to free memory
        unset($this->_modules);
        $this->_modules = [];
    }

    /**
     * Run CLI command.
     */
    public function run() {

        if (defined('CLI_DATABASE')) {
            DB::connect();
        }

        if (empty($this->command) || $this->command == 'help') {
            $this->help();
            return;
        }

        if (!empty($this->module))
            $this->module->execute();
        else {
            echo "Command '$this->command' not recognized.\n";
            return;
        }
    }

    /**
     * CLI Help message.
     */
    private function help() {
        ?>

        Phyrus CLI helps you manage the project via terminal.
        You can use any of the following commands:

        - make: create project files.
        - config: manage project configuration from CLI.

        To get help for a specific command use:
        php phyrus <command> help
<?php
    }

}