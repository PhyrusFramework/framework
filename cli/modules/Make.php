<?php

class MakeCommand extends Command {

    protected $command = 'make';

    public function command_script() {
        if (sizeof($this->params) == 0) {
            echo "\nScript name not specified.\n";
            return;
        }

        $name = $this->params[0];

        $folder = Path::root() . '/' . Definitions::get('scripts');
        create_folder($folder);

        $file =  "$folder/$name.php";

        if (file_exists($file)) {
            echo "\nScript $name.php already exists.\n";
            return;
        }

        file_put_contents($file, "<?php\n\n//Run \"php phyrus script $name\"\necho \"Script works!\\n\";\n");

        echo "\nScript created at $file\n";
    }

    public function command_migration() {
        if (!$this->first) {
            echo "\nMigration name not specified.\n";
            return;
        }

        $path = Path::root() . '/' . Definitions::get('migrations');
        if (!file_exists($path)) {
            create_folder($path);
        }
        $t = new Time();

        $filename = $t->format('YmdHis') . "_$this->first.php";

        ob_start();?>
<<?= '?' ?>php  

new Migration(

    function() {
        // DO
    },

    function() {
        // UNDO
    }
    
);<?php
        $content = ob_get_clean();
        file_put_contents("$path/$filename", $content);

        echo "\n\nMigration created at $path/$filename.\n";
    }

    public function command_command() {

        if (!$this->first) {
            echo "\n\nCommand name not specified.\n";
        }

        $dir = Path::root() . '/' . Definition('commands');
        if (!file_exists($dir)) {
            create_folder($dir);
        }

        $file = "$dir/$this->first.php";

        $cmd = strtolower($this->first);

        ob_start();
?><<?= '?' ?>php  

class <?= $this->first ?> extends Command {

    protected $command = '<?= $cmd; ?>';

    public function run() {
        // php phyrus <?= $cmd ?> 
    }

    public function command_action() {
        // php phyrus <?= $cmd ?>:action
    }

}<?php
        $content = ob_get_clean();
        file_put_contents($file, $content);

        echo "\n\nCommand created at $file.\n";

    }

    public function command_test() {
        if (!$this->first) {
            echo "\nTest name not specified\n";
            return;
        }

        $name = $this->first;
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

}
new <?= $ucfirst ?>();<?php
        $content = ob_get_clean();
        file_put_contents($file, $content);

        echo "\n\nTest created at $file\n";
    }

    public function help() { ?>

    Use the "make" command to create project content automatically:

    - make:script : Creates a script in /scripts.
    - make:migration : Creates a migration in /migrations.
    - make:command : Creates a CLI Command in /commands.
    - make:test : Creates a test file in /tests.

<?php }

}