<?php

class Controller {

    /**
     * Base URL. Auto-set by framework.
     * 
     * @var string
     */
    public string $base = '';

    private array $_actions = [];

    private array $_middlewares = [];

    /**
     * Make a new controller.
     * 
     * @return Controller New controller
     */
    public static function make(string $baseUrl = '') : Controller {
        $ctrl = new Controller();
        $ctrl->base = $baseUrl;
        return $ctrl;
    }

    private function setRoute(string $method, $route, callable $action) {
        if (!isset($this->_actions[$method])) {
            $this->_actions[$method] = [];
        }

        $this->_actions[$method][$route] = $action;
    }

    public function GET(callable $action) : Controller {
        $this->setRoute('GET', '/', $action);
        return $this;
    }

    public function POST(callable $action) : Controller {
        $this->setRoute('POST', '/', $action);
        return $this;
    }

    public function PUT(callable $action) : Controller {
        $this->setRoute('PUT', '/', $action);
        return $this;
    }

    public function DELETE(callable $action) : Controller {
        $this->setRoute('DELETE', '/', $action);
        return $this;
    }

    public function PATCH(callable $action) : Controller {
        $this->setRoute('PATCH', '/', $action);
        return $this;
    }

    public function GETon(string $route, callable $action) : Controller {
        $this->setRoute('GET', $route, $action);
        return $this;
    }

    public function POSTon(string $route, callable $action) : Controller {
        $this->setRoute('POST', $route, $action);
        return $this;
    }

    public function PUTon(string $route, callable $action) : Controller {
        $this->setRoute('PUT', $route, $action);
        return $this;
    }

    public function DELETEon(string $route, callable $action) : Controller {
        $this->setRoute('DELETE', $route, $action);
        return $this;
    }

    public function PATCHon(string $route, callable $action) : Controller {
        $this->setRoute('PATCH', $route, $action);
        return $this;
    }

    public function middleware(callable $middleware) : Controller {
        $this->_middlewares[] = $middleware;
        return $this;
    }

    /**
     * Executes the match route.
     * 
     * @param stdClass URL parameters
     */
    public function resolve(stdClass $params) {

        // Get the difference between the base URI of this controller, and the URL URI.
        $diff = $this->getDifference();

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if (!isset($this->_actions[$method])) {
            return false;
        }

        $actions = $this->_actions[$method];
        
        $action = $this->matches($diff, $actions, $params);
        if (!$action) {
            return false;
        }

        $request = new Request();
        
        // Run middlewares
        foreach($this->_middlewares as $mid) {
            $mid($request, $params);
        }

        return $action($request, $params);
    }

    private function getDifference() {

        $current = Text::instance(URL::route())->split('/', false);
        $base = Text::instance($this->base)->split('/', false);

        $diff = '';
        for($i = count($base); $i < count($current); ++$i) {
            $diff .= "/" . $current[$i];
        }

        return $diff;
    }

    private function matches($diff, &$actions, $params) {
        if (isset($actions[$diff])) {
            return $actions[$diff];
        }

        // Split route into parts
        $diffParts = Text::instance($diff)->split('/', false);

        // Foreach defined method action
        foreach($actions as $route => $action) {

            // Also split its route into parts
            $parts = Text::instance($route)->split('/', false);

            // If number of parts are not equal, can't be. Stop here.
            if (count($diffParts) != count($parts)) {
                continue;
            }

            // Now check part by part and see if they match.
            $found = true;
            $tmpParams = [];
            for($i = 0; $i < count($diffParts); ++$i) {

                $dp = $diffParts[$i];
                $rp = $parts[$i];

                // If route parts starts with ':', it's a parameter
                if ($rp[0] == ':') {
                    $tmpParams[substr($rp, 1)] = $dp;
                } 
                
                // However, if they don't match, can't be.
                else {
                    if ($rp != $dp) {
                        $found = false;
                        break;
                    }
                }

            }

            // If match wasn't found in the previous for, keep looking.
            if (!$found) {
                continue;
            } 
            
            // If found, update the request URL params, and resolve.
            else {
                foreach($tmpParams as $param => $value) {
                    $params->$param = $value;
                }
                return $action;
            }

        }
    }

}