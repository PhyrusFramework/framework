<?php

class CLI_Serve extends CLI_Module {

    public function run() {

        $port = isset($this->flags['port']) ? $this->$flags['port'] : 8000;

        if (detectOS() == "windows") {
            shell_exec("start http://localhost:$port");
        } else {
            shell_exec("open http://localhost:$port");
        }
        echo shell_exec("php -S localhost:$port");

    }

}