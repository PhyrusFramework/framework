<?php

class Command {

    protected $params;
    protected $flags;
    protected $first;
    protected $second;
    protected $third;

    /**
     * @var string command
     */
    protected $command = '';

    /**
     * @var string action
     */
    public string $action = '';

    /**
     * @return string
     */
    public function getCommand() : string {
        return $this->command;
    }

    public function init(CLI $cli) {
        $this->flags = $cli->flags;
        $this->params = $cli->params;
        $this->first = count($this->params) > 0 ? $this->params[0] : null;
        $this->second = count($this->params) > 1 ? $this->params[1] : null;
        $this->third = count($this->params) > 2 ? $this->params[2] : null;
    }

    public function run() {
        if (!$this->first) {
            $this->help();
            return;
        }

        echo "Command '$this->command $this->first' not recognized.\n";
    }

    public function execute() {

        if (empty($this->action)) {

            if ($this->first == 'help') {
                $this->help();
                return;
            }

            $this->run();
            return;
        }

        $func = "command_$this->action";

        if (method_exists($this, $func)) {
            $this->$func();
            return;
        }

        echo "Command '$this->command:$this->action' not recognized.\n";
    }

    public function help() {?>

        The command '<?= $this->command; ?>' does not offer any help information.

    <?php }
}