<?php

class Cron {

    /**
     * Cron interval.
     * 
     * @var array $__interval
     */
    private array $__interval = [
        'minute' => '*',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayOfWeek' => '*'
    ];

    /**
     * Command action.
     * 
     * @var string $__action
     */
    private string $__action = '';

    /**
     * Cron full command.
     * 
     * @var string $__command
     */
    private string $__command = '';

    public function __get($name) {
        if ($name == 'command') {
            return $this->__command;
        }
        if ($name == 'interval') {
            return $this->__interval['minute'] .' '. $this->__interval['hour'] .' '. $this->__interval['day'] .' '. $this->__interval['month'] .' '. $this->__interval['dayOfWeek'];
        }
        if (isset($this->__interval[$name])) {
            return $this->__interval[$name];
        }
    }

    public function __construct(string $command = '') {
        $this->__command = $command;
    }

    /**
     * Create a new Cron job.
     * 
     * @param ?string Cron command.
     * 
     * @return Cron
     */
    public static function instance(string $command = '') : Cron {
        return new Cron($command);
    }

    /**
     * Combines interval and action to generate the command.
     */
    private function __generate() {

        $this->__command = "$this->interval $this->__action";
    }

    /**
     * Creates the cronjob in the system.
     * 
     * @return bool
     */
    public function create() : bool {

        /*if(is_string($this->__command) && !empty($this->__command) && Cron::exists($this->__command)===FALSE) {
            exec('echo -e "$(crontab -l)\n' . $this->__command . '" | crontab -', $output);
        }*/

        // Create a temporary file to hold the current crontab entries
        $tempFile = tempnam(sys_get_temp_dir(), 'cron');

        // Get the current crontab entries
        exec('crontab -l > ' . $tempFile);

        // Append the new cron job to the temporary file
        file_put_contents($tempFile, $this->__command . PHP_EOL, FILE_APPEND);

        // Install the modified crontab file
        exec('crontab ' . $tempFile);

        // Remove the temporary file
        unlink($tempFile);

        return true;
    }

    /**
     * Deletes this Cronjob from the system.
     */
    public function delete() {
        $crons = Cron::list();

        Cron::deleteAll();
        foreach($crons as $cron) {
            if ($cron->command == $this->command) continue;
            $cron->create();
        }
    }

    /**
     * Get a list of active cronjobs.
     * 
     * @return array
     */
    public static function list() : array {
        exec('crontab -l', $output);
        $list = [];
        foreach($output as $k => $cmd) {
            if (!empty($cmd)) {
                $list[] = new Cron($cmd);
            }
        }
        return $list;
    }

    /**
     * Print the list of cronjobs.
     * 
     * @param string $empty [Default 'No cronjobs created']
     */
    public static function print(string $empty = 'No cronjobs created.') {
        exec('crontab -l', $output);
        if (sizeof($output) == 0) {
            echo $empty;
            return;
        }
        foreach($output as $k => $cmd) {
            if (!empty($cmd))
                echo "$cmd<br>";
        }
    }

    /**
     * Check if cronjob already exists.
     * 
     * @param string $command
     * 
     * @return bool
     */
    public static function exists(string $command) : bool {
    
        exec('crontab -l', $crontab);
    
        if(isset($crontab) && is_array($crontab)){
    
            $crontab = array_flip($crontab);
            if(isset($crontab[$command])){
    
                return true;
    
            }
    
        }
        return false;
    }

    /**
     * Delete all created cronjobs.
     */
    public static function deleteAll() {
        exec('crontab -r', $output);
        return $output;
    }

    /**
     * Get an active cronjob by command.
     * 
     * @param string Command
     * 
     * @return Cron
     */
    public static function select(string $command) : ?Cron {
        $list = Cron::list();
        foreach($list as $cron) {
            if ($command == $cron->command)
                return $cron;
        }
        return null;
    }

    /**
     * Get an active cronjob by its index
     * 
     * @param int Index
     * 
     * @return Cron
     */
    public static function byIndex(int $index) : ?Cron {
        $list = Cron::list();
        return count($list) <= $index ? null : $list[$index];
    }

    //// Edit Cron

    /**
     * Resets the interval of the Cron.
     * 
     * @param string measure [Default all] minute, hour, day...
     * 
     * @return Cron self
     */
    public function reset(string $measure = null) : Cron {
        if ($measure == null) {
            $this->__interval = [
                'minute' => '*',
                'hour' => '*',
                'day' => '*',
                'month' => '*',
                'dayOfWeek' => '*'
            ];
        }
        else {
            if (isset($this->__interval[$measure])) {
                $this->__interval[$measure] = '*';
            }
        }
        $this->__generate();
        return $this;
    }

    /**
     * Set the interval using cronstyle.
     * 
     * @param string $cronstyle
     */
    function setInterval(string $cronstyle) : Cron {
        $parts = explode(' ', $cronstyle);
        if (sizeof($parts) < 5) return $this;
        $this->__interval['minute'] = $parts[0];
        $this->__interval['hour'] = $parts[1];
        $this->__interval['day'] = $parts[2];
        $this->__interval['month'] = $parts[3];
        $this->__interval['dayOfWeek'] = $parts[4];

        return $this;
    }

    private function __setDayOfWeek($day) {
        if ($this->minute == '*') 
            $this->__interval['minute'] = '0';
        if ($this->hour == '*') 
            $this->__interval['hour'] = '0';

        $this->__interval['dayOfWeek'] = strtoupper(substr($day, 0, 3));

        return $this;
    }

    /**
     * Change the interval setting a specific measure:
     * every(3, 'hour')
     * every(7, 'day')
     * every('monday')
     * 
     * @param mixed $x
     * @param string $measure minute, hour, day...
     * 
     * @return Cron
     */
    public function every($x, string $measure = 'minute') : Cron {

        if (is_string($x) && in_array($x, [
            'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'
        ])) {
            $this->__setDayOfWeek($x);
        }

        else {
            $n = intval($x);
            if (!$n) return $this;

            if (!isset($this->__interval[$measure]))
                return $this;

            $this->__interval[$measure] = "*/$n";

            if ($measure == 'hour') {
                if ($this->minute == '*')
                    $this->__interval['minute'] = '1';
            }
            else if ($measure == 'day') {
                if ($this->minute == '*')
                    $this->__interval['minute'] = '0';

                if ($this->hour == '*') {
                    $this->__interval['hour'] = '1';
                }
            }
            else if ($measure == 'month') {
                if ($this->minute == '*')
                    $this->__interval['minute'] = '0';

                if ($this->hour == '*') {
                    $this->__interval['hour'] = '0';
                }

                if ($this->day == '*') {
                    $this->__interval['day'] = '1';
                }
            }
        }

        $this->__generate();
        return $this;
    }

    /**
     * Change the interval setting a specific measure:
     * at(3, 'hour') = at 3rd hour of the day.
     * at(7, 'day') = at 7th day of the month.
     * at('monday');
     * 
     * @param mixed $x
     * @param string $measure minute, hour, day...
     * 
     * @return Cron
     */
    public function at($x, string $measure = 'minute') : Cron {

        if (is_string($x) && in_array($x, [
            'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'
        ])) {
            $this->__setDayOfWeek($x);
        }

        else {
            $n = intval($x);
            if (!$n) return $this;

            if (!isset($this->__interval[$measure]))
                return $this;

            $this->__interval[$measure] = "$n";

            if (in_array($measure, ['hour', 'day', 'month'])) {
                if ($this->minute == '*')
                    $this->__interval['minute'] = '0';

                    if (in_array($measure, ['day', 'month'])) {
                        if ($this->hour == '*') {
                            $this->__interval['hour'] = '0';
                        }

                        if ($measure == 'month') {
                            if ($this->day == '*') {
                                $this->__interval['day'] = '0';
                            }
                        }
                    }
            }
        }

        $this->__generate();
        return $this;
    }

    /**
     * Set the Cron action.
     * 
     * @param string $command
     * @param string type [Default null] 'curl' or 'php'
     * 
     * @return Cron self
     */
    public function do(string $command, string $type = null) : Cron {

        if ($type == 'curl') {
            $this->__action = "curl -m 120 -s $command";
        }
        else if ($type == 'php') {
            $this->__action = "php -q $command";
        }
        else {
            $this->__action = $command;
        }

        $this->__generate();

        return $this;

    }

}