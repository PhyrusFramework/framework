<?php

class TestCommand extends Command {

    protected $command = 'test';

    public function run() {
        
        $this->replace_database();

        if (!$this->first) {
            $path = Path::framework() . "/tests/loader.php";
            if (!file_exists($path)) {
                echo "\nThe framework tests component is not found. Are you sure you are using the right version of the framework?\n";
                return;
            }
            include($path);
            return;
        }

        $test = $this->first;
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