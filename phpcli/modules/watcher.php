<?php

class CLI_Watcher extends CLI_Module {

    public function command_create() {

        if (sizeof($this->params) == 0) {
            echo "\nWatcher name not specified.\n";
            return;
        }

        $name = $this->params[0];

        $folder = Path::root() . '/' . Definitions::get('watcher');

        create_folder($folder);

        $file =  "$folder/$name.php";

        $content = "<?php\n\nreturn [\n\t'interval' => 60,\n\t'run' => function() {\n\t\t// Do something.\n\t}\n];";

        file_put_contents($file, $content);

        echo "\nWatcher created.\n";

    }

    public function command_status() {

        $file = Path::root() . '/watcher';

        if (!file_exists($file)) {
            echo "\nWatcher directory does not exist.\n";
            return;
        }

        $file .= '/pid.lock';
        if (!file_exists($file)) {
            echo "\nWatcher not running.\n";
            return;
        }

        echo "Watcher is running on process " . file_get_contents($file);

    }

    public function command_start() {

        $file = Path::root() . '/watcher';

        if (!file_exists($file)) {
            mkdir($file);
        }

        $file .= '/pid.lock';
        if (file_exists($file)) {
            echo "\nWatcher already running.\n";
            return;
        }

        $pid = getmypid();
        file_put_contents($file, $pid);

        echo "\nWatcher running on process $pid\n";

        $this->startLoop();
        unlink($file);
    }

    private function gcd($nums) {

        if (sizeof($nums) < 2) {
            if (sizeof($nums) == 1) return $nums[0];
            return 0;
        }

        if (sizeof($nums) == 2) {
            $a = $nums[0];
            $b = $nums[1];

            if ($b == 0)
                return $a;
            return $this->gcd([$b, $a % $b]);
        }

        return $this->gcd($nums[0], $this->gcd(array_slice($nums, 1, sizeof($nums) - 1)));
    }

    private function startLoop() {

        $interval = 1;
        $count = 0;
        $prev_last_modified = '0000-00-00 00:00:00';
        $last_modified = '0000-00-00 00:00:00';

        while(true) {
            
            $files = Folder::instance(Path::root() . '/watcher')->subfiles('php');

            if (sizeof($files) == 0) {
                echo "\nNo watchers active.\n";
                return;
            }

            $watchers = [];
            $intervals = [];

            foreach($files as $f) {
                $w = include($f);

                if (!is_array($w)) {
                    continue;
                }
                if (!isset($w['interval'])) {
                    continue;
                }
                if (!is_numeric($w['interval'])) {
                    continue;
                }
                if (!isset($w['run'])) {
                    continue;
                }

                $mod = File::instance($f)->modification_date();
                if ($mod > $last_modified) {
                    $last_modified = $mod;
                } 

                $watchers[] = $w;
                $intervals[] = $w['interval'];
            }

            if ($last_modified != $prev_last_modified) {
                $interval = $this->gcd($intervals);
                $prev_last_modified = $last_modified;
            }

            if ($count > 0) {
                foreach($watchers as $watcher) {

                    $i = $watcher['interval'];

                    if (floor($count / $i) == $count / $i) {
                        $watcher['run']();
                    }
                }
            }

            if ($interval < 1) {
                echo "\nWatcher interval ($interval) too small, stopping process.\n";
                break;
            }

            sleep($interval);
            $count += $interval;
        }

    }

    public function command_stop() {

        $file = Path::root() . '/watcher';

        if (!file_exists($file)) {
            echo "\nWatcher not running.\n";
            return;
        }

        $file .= '/pid.lock';
        if (!file_exists($file)) {
            echo "\nWatcher not running.\n";
            return;
        }

        $pid = file_get_contents($file);
        $pid = intval($pid);
        if ($pid == 0) {
            echo "\nInvalid Watcher process.\n";
            return;
        }

        if (detectOS() == 'windows') {
            shell_exec("taskkill /F /PID $pid");
        } else {
            shell_exec("kill $pid");
        }
        unlink($file);
        echo "\nWatcher stopped\n";
        return;

    }

    public function command_restart() {
        $this->command_stop();
        $this->command_start();
    }

    public function help() {?>
    
        The Watcher command is used to run or stop the watcher process.

        - create <name>
        Create a new watcher script.

        - status
        Check whether the process is running or not.

        - start
        Start the process.

        - restart
        Stop the process if running and start it again.

        - stop <key>
        Stop the process if running.
    
    <?php }

}