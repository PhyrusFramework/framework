<?php

class CLI_Test extends CLI_Module {

    function command_run() {

        if ($this->command == 'create') {
            $this->command_create();
            return;
        }

        $this->replace_database();

        if (sizeof($this->params) == 0) {
            $path = Path::framework() . "/tests/loader.php";
            if (!file_exists($path)) {
                echo "\nThe framework tests component is not found. Are you sure you are using the right version of the framework?\n";
                return;
            }
            
            $useFrameworkTests = isset($this->flags['framework']);
            include($path);
        }
        else {
            $test = $this->params[0];
            $test_path = Path::root() . "/tests/$test.php";
            if (!file_exists($test_path)) {

                echo "\nTest $test not found. Make sure the file $test.php exists in the path /tests.\n";
                return;
            }

            $path = Path::framework() . "/tests/index.php";
            if (!file_exists($path)) {
                echo "\nThe framework tests component is not found. Are you sure you are using the right version of the framework?\n";
                return;
            }
            
            include($path);
            include($test_path);

        }

    }

    public function command_create() {
        if (sizeof($this->params) < 1) {
            echo "\nTest name not specified\n";
            return;
        }

        $name = $this->params[0];
        $route = Path::tests();
        create_folder($route);

        $file = "$route/$name.php";
        $ucfirst = ucfirst($name);

        ob_start();?>
<<?= '?' ?>php      

class <?= $ucfirst ?>Test extends Test {

    function run() {
        if (!true) {
            $this->addError('Something went wrong!');
        }
    }

}<?php
        $content = ob_get_clean();
        file_put_contents($file, $content);

        echo "\nTest created at $file\n";
    }

    private function replace_database() {

        $db = Config::get('tests.alternativeDatabase');
        if (is_array($db)
        && !empty($db['database'])
        && !empty($db['username'])
        && isset($db['password'])) {

            global $DATABASE;
            try{
                $DATABASE = new DATABASE($db);
            } catch(Exception $e) {}

        }

    }

    public function help() { ?>
    
        The test command lets you run your Tests directly from CLI.
        To know more about tests read the framework documentation.

        - php cli test create <name>
        Create a test file.

        - php cli test run
        Run all of your tests at once.

        - php cli test run <name>
        Run a specific test.

    <?php }

}