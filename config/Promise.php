<?php

class Promise {

    private $response = null;
    private $error = null;
    private $result = 'none';

    private $onResolve;
    private $onReject;
    private $onAnyway;

    function __construct(callable $action) {

        $resolve = function($parameter = null) {
            $this->result = 'success';
            $this->response = $parameter;
            if ($this->onResolve != null) {
                $this->onResolve($parameter);
                $this->onResolve = null;
            }
            if ($this->onAnyway != null) {
                $this->onAnyway($parameter);
                $this->onAnyway = null;
            }
        };

        $reject = function($error = null) {
            $this->result = 'error';
            $this->error = $error;
            if ($this->onReject != null) {
                $this->onReject($error);
                $this->onReject = null;
            }
            if ($this->onAnyway != null) {
                $this->onAnyway($error);
                $this->onAnyway = null;
            }
        };

        try {
            $action($resolve, $reject);
        } catch(Throwable $e) {
            $reject($e);
        }

    }

    public static function create(callable $action) {
        return new Promise($action);
    }

    public function then(callable $func) {

        if ($this->result == 'success') {
            $func($this->response);
        } else {
            $this->onResolve = $func;
        }

        return $this;
    }

    public function catch(callable $func) {
        if ($this->result == 'error') {
            $func($this->error);
        } else {
            $this->onReject = $func;
        }

        return $this;
    }

    public function finally(callable $func) {
        if ($this->result == 'success') {
            $func($this->response);
        } else if ($this->result == 'error') {
            $func($this->error);
        } else {
            $this->onAnyway = $func;
        }

        return $this;
    }

}