<?php 

class Test {

    /**
     * Number of executed tests.
     * 
     * @var int $TEST_COUNT
     */
    public static int $TEST_COUNT = 0;

    /**
     * Did all tests succeed?
     * 
     * @param bool $SUCCESS
     */
    public static bool $SUCCESS = true;

    /**
     * Test logged messages.
     * 
     * @var array $log
     */
    private array $log = [];

    /**
     * Logs a message to be displayed in the terminal.
     * 
     * @param string $message
     */
    public function addLog(string $message, $tab = 1) {
        $str = "\n";
        for($i = 0; $i<$tab; ++$i) {
            $str .= "\t· ";
        }
        $str .= $message;
        $this->log[] = $str;
    }

    /**
     * List of found errors.
     * 
     * @var array
     */
    protected array $errors = [];

    /**
     * Logs an error.
     * 
     * @param string $message
     * @param string $detail
     * @param string $file
     * @param int $line
     */
    public function addError(string $message, string $detail = null, string $file = null, int $line = null) {

        $this->addLog("ERROR: $message");
        $this->errors[] = new Generic([
            'message' => $message,
            'detail' => $detail,
            'file' => $file,
            'line' => $line
        ]);
    }

    /**
     * Test arguments.
     * 
     * @var array $arguments
     */
    protected array $arguments = [];

    /**
     * Did this test succeed?
     * 
     * @var bool $success
     */
    private bool $success = true;

    public function __construct() {
        $this->getArguments();

        ?>
        ------ Running test <?php echo get_called_class(); ?> -------
        [<?= datenow(); ?>]
        <?php

        self::$TEST_COUNT += 1;
        $this->run();
        $this->success = sizeof($this->errors) == 0;
        self::$SUCCESS = self::$SUCCESS && $this->success;
        $this->print();

        if (sizeof($this->errors) > 0) {
            die();
        }
    }

    /**
     * Get and parse arguments for the test.
     * 
     */
    private function getArguments() {

        $args = [];

        // In case of CLI
        if (defined('USING_CLI')) {
            global $CLI;
            if ($CLI != null) {
                $args = $CLI->flags;
            }
        }
        else {
            $args = $_GET;
        }

        $config = [
            'log' => false,
            'alternativeDatabase' => Config::get('database.forTests')
        ];

        $args = arr($args)->force($config);

        $this->arguments = [];
        foreach($args as $k => $v) {
            if ($v == 'true')
                $this->arguments[$k] = true;
            else if ($v == 'false')
                $this->arguments[$k] = true;
            else
                $this->arguments[$k] = $v;
        }

    }

    /**
     * Get a specific argument.
     * 
     * @param string $name
     * 
     * @return mixed
     */
    public function arg(string $name) {
        return isset($this->arguments[$name]) ? $this->arguments[$name] : false;
    }

    /**
     * Run this test.
     */
    protected function run() {
        // Override this
    }

    /**
     * Print the result of this test.
     */
    protected function print() { 
        ob_start(); ?>

        <?php 
        if (sizeof($this->errors) > 0) {
            echo "\e[91mTEST FAILED\e[39m\n\n";
        }
        else {
            echo "\e[92mTEST SUCCESSFUL\e[39m\n\n";
        }

        if ($this->arg('verbose')) {

            echo "\tLog:\n";
            foreach($this->log as $msg) {
                echo $msg;
            }
        }
        else {

            if (sizeof($this->errors) > 0) {
                echo "\tErrors found:\n\n";
                foreach($this->errors as $err) {
                    echo "\t- $err->message";
                    if (!empty($err->file)) {
                        echo " ($err->file";
                        if (!empty($err->line)) {
                            echo "; Line $err->line";
                        }
                        echo ')';
                    }
                    echo "\n";
                }
            }

        }

        $log = ob_get_flush();
        if (!empty($this->arguments['log'])) {
            $logfile = Path::root() . '/tests';
            if (!is_dir($logfile)) {
                create_folder($logfile);
            }
            $logfile .= '/log';
            File::instance($logfile)->prepend($log);
        }
    }

    private function valueToString($value) {
        $vstr = '';
        if ($value === null) {
            $vstr = 'null';
        } else if (is_bool($value)) {
            $vstr = $value ? 'true' : 'false';
        } else if (is_array($value)) {
            $vstr = print_r($value, true);
        } else {
            $vstr = "'$value'";
        }
        return $vstr;
    }

    /**
     * Check if value is the expected one.
     * 
     * @param mixed $value
     * @param mixed $expectedValue
     * @param string $title for test
     */
    public function is($value, $expectedValue, string $title = '') {

        $vstr = $this->valueToString($value);
        $estr = $this->valueToString($expectedValue);

        $errorMsg = (!empty($title) ? "($title) " : '') . "Error: value $vstr was not the expected ($estr)";
        $successMsg = (!empty($title) ? "($title) " : '') . "Success: value $vstr was expected.";

        if (is_array($value)) {

            if (!is_array($expectedValue)) {
                $this->addError((empty($title) ? "($title)" : '') . "Error: array was not expected");
            } else {
                if (arr($value)->equalTo($expectedValue)) {
                    $this->addLog($successMsg);
                } else {
                    $this->addError($errorMsg);
                }
            }

        } else {
            if ($value !== $expectedValue) {
                $this->addError($errorMsg);
            } else {
                $this->addLog($successMsg);
            }
        }

    }

    /**
     * Generate a request Testcase.
     * 
     * @param string $path
     * @param array $options
     * 
     * @return TestCase
     */
    public function req(string $path, array $options = []) : TestCase {

        $method = $options['method'] ?? 'GET';

        $this->addLog("$method '$path'...");
        return new TestCase($path, $options);

    }

    /**
     * Automatically check that these routes return a success 200 code.
     * 
     * @param string $route
     * 
     * @return bool
     */
    public function check200(string $route, $options = []) : bool {

        $errors = false;
        $case = $this->req($route, $options);
            
        if (!$case->isCode(200)) {
            $this->addError("Path '$route' expected code 200 but found " . $case->getCode() . "\n", $case->getText());
            $errors = true;
        } else {
            $this->addLog("Path '$route' is correct (code 200).\n");
        }
        return !$errors;

    }


}