<?php

class CRUD {

    private $route;

    private $_middleware = null;

    private $_list = null;
    private $_get = null;
    private $_create = null;
    private $_edit = null;
    private $_delete = null;

    public static function instance(string $route) : CRUD {
        return new CRUD($route);
    }

    function __construct(string $route) {
        $this->route = $route;
    }

    public function middleware($middleware) : CRUD {
        $this->_middleware = $middleware;
        return $this;
    }

    public function list(callable $action) : CRUD {
        $this->_list = [$action];
        return $this;
    }

    public function get(callable $action) : CRUD {
        $this->_get = [$action];
        return $this;
    }

    public function edit(callable $action) : CRUD {
        $this->_edit = [$action];
        return $this;
    }

    public function create(callable $action) : CRUD {
        $this->_create = [$action];
        return $this;
    }

    public function delete(callable $action) : CRUD {
        $this->_delete = [$action];
        return $this;
    }

    public function generate() : CRUD {

        if ($this->_list || $this->_create) {

            $route = [];

            if ($this->_middleware) {
                $route['middleware'] = $this->_middleware;
            }

            if ($this->_list) {
                $route['GET'] = $this->_list[0];
            }
            if ($this->_create) {
                $route['POST'] = $this->_create[0];
            }

            Router::add($this->route, $route);
        }

        if (
            $this->_get ||
            $this->_edit ||
            $this->_delete
        ) {
            $route = [];

            if ($this->_middleware) {
                $route['middleware'] = $this->_middleware;
            }

            if ($this->_get) {
                $route['GET'] = $this->_get[0];
            }
            if ($this->_edit) {
                $route['PUT'] = $this->_edit[0];
            }
            if ($this->_delete) {
                $route['DELETE'] = $this->_delete[0];
            }

            Router::add($this->route . '/:id', $route);
        }

        return $this;

    }

}