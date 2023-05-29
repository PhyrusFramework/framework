<?php

class WatcherAction {

    private array $timmings = [];
    private ?stdClass $currentTimming;
    private ?Generic $actionContainer;

    public function __construct()
    {
        $this->currentTimming = null;
        $this->actionContainer = null;
    }

    public function setAction(Closure $action) {
        $a = new Generic();
        $a->set('action', $action);
        $this->actionContainer = $a;
    }

    public function every(int $value) {
        if ($this->currentTimming) {
            $timmings[] = $this->currentTimming;
        }

        $this->currentTimming = new stdClass();
        $this->currentTimming->every = $value;
    }

    public function on(int $value) {
        if ($this->currentTimming) {
            $timmings[] = $this->currentTimming;
        }

        $this->currentTimming = new stdClass();
        $this->currentTimming->on = $value;
    }

    public function setMeasure(string $measure) {
        if (!$this->currentTimming) return;
        $this->currentTimming->measure = $measure;
        $this->timmings[] = $this->currentTimming;
        $this->currentTimming = null;
    }

    public function check(array &$dateParts) : bool {

        foreach($this->timmings as $t) {

            if (!empty($t->every)) {

                $n = $t->every;
                $d = $dateParts[$t->measure];

                if ($d % $n == 0) {
                    continue;
                }

                return false;

            }
            else if (!empty($t->on)) {

                $n = $t->on;
                $d = $dateParts[$t->measure];

                if ($n != $d) return false;

            }
        }

        return true;
    }

    public function run() {
        if (!empty($this->actionContainer)) {
            $this->actionContainer->action();
        }
    }

}