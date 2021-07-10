<?php

class CLI_Cron extends CLI_Module {

    public function command_list() {
        $list = Cron::list();
        if (sizeof($list) == 0) {
            echo "\nThere are no active cronjobs.\n";
            return;
        }
        echo "\nCronjobs:\n";
        $index = 0;
        foreach($list as $i) {
            echo "\t[$index] $i->command\n";
            ++$index;
        }
        echo "\n";
    }

    public function command_delete() {

        if (isset($this->flags['all'])) {
            Cron::deleteAll();
            return;
        }

        $cmd = null;
        if (isset($this->flags['index'])) {
            $list = Cron::list();
            $ind = intval($this->flags['index']);
            if ($ind + 1 > sizeof($list)) {
                echo "\nIndex does not exist. There are only " . sizeof($list) . " cronjobs.\n";
                return;
            }

            $cmd = $list[$ind]->command;
        }
        else if (isset($this->flags['command'])){
            $cmd = $this->flags['command'];
        }

        if ($cmd == null) {?>

            To delete a specific cronjob use
            the command or the index:

            - cron delete --index=2
            - cron delete --command="0 2 * * * /bin/sh backup.sh"

            To find the index use 'cron list'

        <?php } else {
            $cron = Cron::select($cmd);
            if ($cron == null) {
                echo "\nThis cron does not exist.\n";
            }
            else $cron->delete();
        }

    }

    public function command_create() {

        if (sizeof($this->params) == 0) {
            echo "\nCron command not specified.\n";
            return;
        }
        $command = $this->params[0];
        $interval = isset($this->flags['interval']) ? $this->flags['interval'] : null;

        $cron = new Cron($command);
        if ($interval != null)
            $cron->setInterval($interval);

        $cron->create();

    }

    public function command_curl() {

        if (sizeof($this->params) < 1) {
            echo "\nYou must specify the URL.\n";
            return;
        }

        if (!isset($this->flags['interval'])) {
            echo "\nYou must specify the interval.\n";
            return;
        }

        $url = $this->params[0];
        $interval = $this->flags['interval'];

        $cron = new Cron();
        $cron->action($url, "curl");
        $cron->setInterval($interval);

        $cron->create();

    }

    public function help() { ?>

        The Cron command lets you easily manage and view
        active cronjobs.

        - list
        Print a list of all active cronjobs

        - delete --index=X
        Delete a cronjob by using its index (consult in list)

        - delete --command=...
        Delete a cronjob by using its command (consult in list)

        - delete --all
        Delete all cronjobs

        - create <command>
        Create using a full command

        Example:
        cron create "0 2 * * * /bin/sh backup.sh"

        - create --interval="* * * * *"
        Create and specify the interval aside
        cron create "/bin/sh backup.sh" --interval="0 2 * * *"

        - curl --interval="* * * * *"
        Create a CURL cronjob passing only the URL and
        the interval:

        Example:
        cron curl "https://mysite.com/cron.php" --interval="0 2 * * *"

        To find the interval you need, please refer to:
        https://crontab.guru/

    <?php }

}