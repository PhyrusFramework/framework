<?php

class Promise {

    /**
     * @var mixed Promise response
     */
    private $response = null;

    /**
     * @var mixed Promise error
     */
    private $error = null;

    /**
     * @var string Promise status: none, success or error
     */
    private $result = 'none';

    /**
     * @var array Methods map
     */
    private array $__methods = [];

    function __call($func, $params) {

        foreach($this->__methods as $k => $v) {
            if ($func == $k) {
                return $v(...$params);
            }
        }
        return null;
    }

    /**
     * Create a new Promise
     * 
     * @param callable Action to run
     * 
     * @return Promise
     */
    public static function instance(callable $action) : Promise {
        return new Promise($action);
    }

    function __construct(callable $action) {

        $resolve = function($parameter = null) {

            $this->result = 'success';
            $this->response = $parameter;

            if (isset($this->__methods['resolve'])) {
                $this->resolve($parameter);
                unset($this->__methods['resolve']);
            }

            if (isset($this->__methods['anyway'])) {
                $this->anyway($parameter);
                unset($this->__methods['anyway']);
            }

        };

        $reject = function($error = null) {
            $this->result = 'error';
            $this->error = $error;

            if (isset($this->__methods['error'])) {
                $this->error($error);
                unset($this->__methods['error']);
            }

            if (isset($this->__methods['anyway'])) {
                $this->anyway($error);
                unset($this->__methods['anyway']);
            }
        };

        try {
            $action($resolve, $reject);
        } catch(Throwable $e) {
            $reject($e);
        }

    }

    /**
     * Was this promise rejected?
     * 
     * @return bool
     */
    public function isError() : bool {
        return $this->result == 'error';
    }

    /**
     * Was this promise resolved successfully?
     * 
     * @return bool
     */
    public function isSuccess() : bool {
        return $this->result == 'success';
    }

    /**
     * Get the response after the promise execution.
     * 
     * @return mixed
     */
    public function getResponse() {
        return $this->response;
    }

    /**
     * If the Promise failed, get the error.
     * 
     * @return mixed
     */
    public function getError() {
        return $this->error;
    }

    /**
     * Run some action when the promise is finished
     * 
     * @param callable Action
     * 
     * @return Promise self
     */
    public function then(callable $func) : Promise {

        if ($this->result == 'success') {
            $func($this->response);
        } else {
            $this->__methods['resolve'] = $func;
        }

        return $this;
    }

    /**
     * Catch the error if the Promise fails
     * 
     * @param callable Action
     * 
     * @return Promise self
     */
    public function catch(callable $func) : Promise {
        if ($this->result == 'error') {
            $func($this->error);
        } else {
            $this->__methods['error'] = $func;
        }

        return $this;
    }

    /**
     * Do something either if the Promise works or fails.
     * 
     * @param callable Action
     * 
     * @return Promise self
     */
    public function finally(callable $func) : Promise {
        if ($this->result == 'success') {
            $func($this->response);
        } else if ($this->result == 'error') {
            $func($this->error);
        } else {
            $this->__methods['anyway'] = $func;
        }

        return $this;
    }

}