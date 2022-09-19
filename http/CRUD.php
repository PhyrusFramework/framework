<?php

class CRUD {

    private $route;

    private $_middleware = null;

    private $_list = null;
    private $_get = null;
    private $_create = null;
    private $_edit = null;
    private $_delete = null;

    private $_customs = [];

    public static function instance(string $route) : CRUD {
        return new CRUD($route);
    }

    function __construct(string $route) {
        $this->route = $route;
    }

    /**
     * Define the middleware for these routes.
     * 
     * @param $middleware
     * 
     * @return CRUD
     */
    public function middleware($middleware) : CRUD {
        $this->_middleware = $middleware;
        return $this;
    }

    /**
     * Create the GET /model route.
     * 
     * @param callable Action
     * @param callable|null Endpoint info
     * 
     * @return CRUD
     */
    public function list(callable $action, ?callable $info) : CRUD {
        $obj = [$action];
        if (!empty($info)) {
            $obj['info'] = $info;
        }
        $this->_list = $obj;
        return $this;
    }

    /**
     * Create the GET /model/:id route
     * 
     * @param callable Action
     * @param callable|null Endpoint info
     * 
     * @return CRUD
     */
    public function get(callable $action, ?callable $info) : CRUD {
        $obj = [$action];
        if (!empty($info)) {
            $obj['info'] = $info;
        }
        $this->_get = $obj;
        return $this;
    }

    /**
     * Create the PUT /model/:id route
     * 
     * @param callable Action
     * @param callable|null Endpoint info
     * 
     * @return CRUD
     */
    public function edit(callable $action, ?callable $info) : CRUD {
        $obj = [$action];
        if (!empty($info)) {
            $obj['info'] = $info;
        }
        $this->_edit = $obj;
        return $this;
    }

    /**
     * Create the POST /model route
     * 
     * @param callable Action
     * @param callable|null Endpoint info
     * 
     * @return CRUD
     */
    public function create(callable $action, ?callable $info) : CRUD {
        $obj = [$action];
        if (!empty($info)) {
            $obj['info'] = $info;
        }
        $this->_create = $obj;
        return $this;
    }

    /**
     * Create the DELETE /model/:id route
     * 
     * @param callable Action
     * @param callable|null Endpoint info
     * 
     * @return CRUD
     */
    public function delete(callable $action, ?callable $info = null) : CRUD {
        $obj = [$action];
        if (!empty($info)) {
            $obj['info'] = $info;
        }
        $this->_delete = $obj;
        return $this;
    }

    /**
     * Create a custom route.
     * 
     * @param string Method
     * @param string Route
     * @param callable Action
     * @param callable|null Endpoint info
     * 
     * @return CRUD
     */
    public function custom(string $method, string $route, callable $action, ?callable $info = null) : CRUD {

        $r = [];
        if (isset($this->_customs[$route])) {
            $r = $this->_customs[$route];
        }

        $obj = [$action];
        if (!empty($info)) {
            $obj['info'] = $info;
        }

        $r[$method] = $obj;;
        $this->_customs[$route] = $r;
        return $this;
    }

    /**
     * Add the routes to the Router
     * 
     * @return CRUD
     */
    public function generate() : CRUD {

        if (sizeof($this->_customs) > 0) {

            foreach($this->_customs as $route => $endpoint) {

                if ($this->_middleware) {
                    $endpoint['middleware'] = $this->_middleware;
                }

                Router::add($this->route . $route, $endpoint);
            }

        }

        if ($this->_list || $this->_create) {

            $route = [];

            if ($this->_list) {
                if ($this->_middleware) {
                    $this->_list['middleware'] = $this->_middleware;
                }
                $route['GET'] = $this->_list;
            }
            if ($this->_create) {
                if ($this->_middleware) {
                    $this->_create['middleware'] = $this->_middleware;
                }
                $route['POST'] = $this->_create;
            }

            Router::add($this->route, $route);
        }

        if (
            $this->_get ||
            $this->_edit ||
            $this->_delete
        ) {
            $route = [];

            if ($this->_get) {
                if ($this->_middleware) {
                    $this->_get['middleware'] = $this->_middleware;
                }
                $route['GET'] = $this->_get;
            }
            if ($this->_edit) {
                if ($this->_middleware) {
                    $this->_edit['middleware'] = $this->_middleware;
                }
                $route['PUT'] = $this->_edit;
            }
            if ($this->_delete) {
                if ($this->_middleware) {
                    $this->_delete['middleware'] = $this->_middleware;
                }
                $route['DELETE'] = $this->_delete;
            }

            Router::add($this->route . '/:id', $route);
        }

        return $this;

    }

}