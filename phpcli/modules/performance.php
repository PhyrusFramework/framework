<?php

class CLI_Performance extends CLI_Module {

    private static $startTime;
    private static $records;

    public static function record($name) {
        self::$records[$name] = round(microtime(true) * 1000);
    }

    public function init($cli) {
        parent::init($cli);

        self::$records = [];
        define('PERFORMANCE_ANALYZER', true);
        self::record('start');
        self::$startTime = date('m-d-Y H:i:s').substr(fmod(microtime(true), 1), 1);
    }

    public function run() {

        /*** Check route */
        if (isset($this->flags['route'])) {
            ob_start();
            WebLoader::launch($this->flags['route']);
            self::record('Route '.$this->flags['route'].' completed');
            ob_clean();
        }
        /******/

        echo "\n--- PHYRUS FRAMEWORK ---------------------------------------\n\n";
        echo 'Performance analysis started at: '.self::$startTime."\n\n";
        $current = self::$records['start'];
        $first = $current;
        $mask = "%-40s | %-15s | %-15s\n";
        printf($mask, 'Name', 'Time to load', 'Total time');
        echo "-------------------------------------------------------------------\n";

        foreach(self::$records as $name => $mil) {
            if ($name == 'start') continue;

            $sinceFirst = ($mil - $first) / 1000;
            $sinceLast = ($mil - $current) / 1000;
            $current = $mil;

            printf($mask, $name, "+$sinceLast".'s', $sinceFirst.'s');

        }

        $now = round(microtime(true) * 1000);
        $total = ($now - $first) / 1000;

        echo "\nPerformance analysis finished! Total time: $total"." seconds to load.\n";
        echo "\n--- PHYRUS FRAMEWORK ---------------------------------------\n";
    }

}