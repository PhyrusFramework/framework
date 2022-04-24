<?php

class CRUD {

    private $route;

    private $_list = null;
    private $_get = null;
    private $_create = null;
    private $_edit = null;
    private $_delete = null;

    /**
     * Create a CRUD object instance
     * 
     * @param string $route
     * 
     * @return CRUD
     */
    public static function instance(string $route) : CRUD {
        return new CRUD($route);
    }

    function __construct(string $route) {
        $this->route = $route;
    }

    /**
     * Add the list endpoint GET /xxx
     * 
     * @param callable
     * 
     * @return CRUD
     */
    public function list(callable $action) : CRUD {
        $this->_list = [$action];
        return $this;
    }

    /**
     * Add the get item endpoint GET /xxx/:id
     * 
     * @param callable
     * 
     * @return CRUD
     */
    public function get(callable $action) : CRUD {
        $this->_get = [$action];
        return $this;
    }

    /**
     * Add the edit item endpoint PUT /xxx/:id
     * 
     * @param callable
     * 
     * @return CRUD
     */
    public function edit(callable $action) : CRUD {
        $this->_edit = [$action];
        return $this;
    }

    /**
     * Add the create endpoint POST /xxx
     * 
     * @param callable
     * 
     * @return CRUD
     */
    public function create(callable $action) : CRUD {
        $this->_create = [$action];
        return $this;
    }

    /**
     * Add the delete endpoint DELETE /xxx/:id
     * 
     * @param callable
     * 
     * @return CRUD
     */
    public function delete(callable $action) : CRUD {
        $this->_delete = [$action];
        return $this;
    }

    /**
     * Add the specified routes to the Router
     * 
     * @return CRUD
     */
    public function generate() : CRUD {

        if ($this->_list || $this->_create) {
            Router::add($this->route, function() {
                $req = new RequestData();
                $req->requireMethod(
                    $this->_list ? 'GET' : '',
                    $this->_create ? 'POST' : ''
                );

                if ($req->method() == 'GET') {
                    return $this->_list[0]();
                }
                return $this->_create[0]();
            });
        }

        if (
            $this->_get ||
            $this->_edit ||
            $this->_delete
        ) {
            Router::add($this->route . '/:id', function($parameters) {
                $req = new RequestData();
                $req->requireMethod(
                    $this->_get ? 'GET' : '',
                    $this->_edit ? 'PUT' : '',
                    $this->_delete ? 'DELETE' : ''
                );

                if ($req->method() == 'GET') {
                    return $this->_get[0](...$parameters);
                }
                if ($req->method() == 'PUT') {
                    return $this->_edit[0](...$parameters);
                }
                
                return $this->_delete[0](...$parameters);
            });
        }

        return $this;

    }

}