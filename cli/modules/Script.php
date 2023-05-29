<?php

class ScriptCommand extends Command {

    protected $command = 'script';

    public function run() {
        if (!$this->first) {
            echo "\n\nScript name not specified.\n";
            return;
        }

        $file = Path::root() . '/' . Definition('scripts') . "/$this->first.php";

        if (!file_exists($file)) {

            $file = Path::root() . '/' . Definition('scripts') . "/$this->first";

            if (!file_exists($file)) {
                echo "\n\nScript $this->first does not exist.\n";
                return;
            }

        }

        include($file);
    }

}