<?php
require_once(__DIR__ . '/module.php');

class CLI {

    /**
     * CLI Modules
     */
    private static $_modules = [
        'front' => 'CLI_Front',
        'config' => 'CLI_Config',
        'cron' => 'CLI_Cron',
        'test' => 'CLI_Test',
        'migrate' => 'CLI_Migrate',
        'script' => 'CLI_Script',
        'watcher' => 'CLI_Watcher',
        'install' => 'CLI_Install'
    ];

    /**
     * Add new module to the CLI
     * 
     * @param string $command
     * @param string $class
     */
    public static function registerModule(string $command, string $class) {
        self::$_modules[$command] = $class;
    }

    /**
     * Params of the current CLI request.
     * 
     * @var array $params
     */
    public $params;

    /**
     * Flags set in the command.
     * 
     * @var array $flags
     */
    public $flags;

    /**
     * Command keyword.
     * 
     * @var CLI_Module $command
     */
    private $command;

    /**
     * Current used CLI Module
     * 
     * @var $module
     */
    private $module;

    public function __construct($args) {
        if (sizeof($args) < 2) return;

        $params = [];
        $flags = [];
        for($i = 1; $i<sizeof($args); ++$i) {
            $p = $args[$i];

            if (strlen($p) > 2 && substr($p, 0, 2) == '--') {
                if (strpos($p, '=')) {
                    $parts = explode( '=', substr($p, 2));
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
        $this->params = $params;
        $this->flags = $flags;

        if (isset($this->flags['database']) || 
        in_array($this->command, ['test', 'migrate', 'script', 'install'])) {
            define('CLI_DATABASE', true);
        }

        $this->init();
    }

    /**
     * Initialize CLI before running.
     */
    public function init() {

        if (in_array($this->command, array_keys(self::$_modules) )) {
            $cl = self::$_modules[$this->command];
            if (!class_exists($cl)) {
                $path = __DIR__ . "/modules/$this->command.php";

                if (file_exists($path)) {
                    require $path;

                    if (class_exists($cl)) {
                        $this->module = new $cl($this->command);
                        $this->module->init($this);
                    }
                }
            }
            else {
                $this->module = new $cl($this->command);
                $this->module->init($this);
            }
        }
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
            $this->module->run();
        else {
            if (!in_array($this->command, array_keys(self::$_modules) )) {
                echo "Command '$this->command' not recognized.\n";
                return;
            }

            $cl = self::$_modules[$this->command];
            if (!class_exists($cl)) {
                echo "CLI Module '$cl' does not exist.\n";
                return;
            }
            else {
                $this->module = new $cl($this->command);
                $this->module->init($this);
                $this->module->run();
            }
        }
    }

    /**
     * CLI Help message.
     */
    private function help() {
        ?>

        Phyrus CLI helps you manage the project via terminal.
        You can use any of the following commands:

        - front: manage the front-end project.
        - config: manage the project configuration.
        - test: create or run tests.
        - migrate: create or run migrations.
        - script: create or run scripts.
        - cron: manage cronjobs from cli.
        - watcher: manage the watcher process.

        To get help for a specific command use:
        php framework/cli <command> help
        <?php
    }

}