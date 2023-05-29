<?php

class Router {

    private static ?Router $_instance = null;

    public static function instance() : Router {
        if (self::$_instance == null) {
            self::$_instance = new Router();
        }

        return self::$_instance;
    }

    private array $routes = [];
    private array $middlewares = [];
    private string $_current_prefix = '';
    private array $_current_middlewares = [];

    public function registerMiddleware(string $name, callable $action) {
        $this->middlewares[$name] = $action;
    }

    /**
     * Add a route and link it to a controller.
     * 
     * @param string URL Route
     * @param string Path to controller, relative to /back-end folder.
     * 
     * @return Router
     */
    public function on(string $route, string $controller) : Router {
        $middlewares = $this->_current_middlewares;

        $this->routes[$this->_current_prefix . $route] = [
            'controller' => $controller,
            'middlewares' => $middlewares
        ];
        return $this;
    }

    /**
     * Group routes by a prefix.
     * 
     * @param string Prefix
     * @param callable Function to define routes inside
     * 
     * @return Router
     */
    public function prefix(string $prefix, callable $router) : Router {
        $oldPrefix = $this->_current_prefix;
        $this->_current_prefix .= $prefix;
        $router($this);
        $this->_current_prefix = $oldPrefix;
        return $this;
    }

    public function middleware(string|callable $middleware, callable $router) : Router {
        $midAction = is_string($middleware) ? ($this->middlewares[$middleware] ?? null) : $middleware;
        if ($midAction == null) {
            $router($this);
            return $this;
        }

        $this->_current_middlewares[] = $midAction;
        $router($this);
        array_pop($this->_current_middlewares);

        return $this;
    }

    private function loadProjectMiddlewares() {
        $path = Path::middlewares();
        $files = subfiles($path, 'php');

        foreach($files as $file) {
            $name = str_replace('.php', '', basename($file));
            $action = include($file);

            if (is_closure($action)) {
                $this->registerMiddleware($name, $action);
            }
        }
    }

    /**
     * Launch website
     */
    public static function launch() {

        if (Config::get('project.debug')) {
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            error_reporting(E_ALL);
        }

        $router = Router::instance();

        // Load project PHP files
        self::autoloadProjectPHPFiles();

        // Load middlewares from directory /back-end/middlewares
        $router->loadProjectMiddlewares();

        $routesFile = Path::back() . '/routes.php';
        if (!file_exists($routesFile)) {
            throw new FrameworkException('Routes file not found.', 'Check that file exists under ' . Path::back());
            return;
        }
        require_once($routesFile);

        // Find path matching route
        $match = null;
        $parts = URL::path();

        // For each defined route
        foreach($router->routes as $path => $options) {

            // Compare this route with the current URL:
            $response = self::matchesPath($path, $parts);
            if ($response->matches == 0) continue;

            if (!$match || $response->matches > $match->matches) {
                $match = $response;
                $match->controller = $options['controller'];
                $match->middlewares = &$options['middlewares'];
                $match->route = $path;
            }

        }

        // Release memory:
        $router->routes = [];
        gc_collect_cycles(); // <-- Force garbage collector to clean memory
        /////

        $controller = null;
        
        if ($match) {
            $controllerFile = Path::controllers() . $match->controller . '.php';

            if (file_exists($controllerFile)) {
                $controller = include($controllerFile);
                $controller->base = $match->route;

                foreach($match->middlewares as $mid) {
                    $controller->middleware($mid);
                }
            }
        }

        $whenRouteNotFound = function() {
            if (Config::get('project.only_API')) {
                header('Content-Type: application/json');
                response_die('not-found', 'Route not found');
            }

            // Load Front-End
            header('Content-Type: text/html');
            header('Cache-Control: ' . (Config::get('web.headers.cache-control-front', 'public, max-age=31536000')));

            $html = Path::public() . '/index.html';

            if (file_exists($html)) {
                echo file_get_contents($html);
            }
        };

        // If route not found:
        if (!$controller) {
            $whenRouteNotFound();
            return;
        }

        // If route found, run:
        header('Content-Type: application/json');
        header('Cache-Control: ' . (Config::get('web.headers.cache-control-api', 'no-cache')));
        $response = $controller->resolve($match->params);
        if ($response === FALSE) {
            $whenRouteNotFound();
        }

        // Print response
        if (!$response) {
            response_die('ok');
        }

        if (is_array($response) || method_exists($response, 'jsonSerialize')) {
            response_die('ok', JSON::stringify($response));
        } else if (is_string($response) || is_numeric($response)) {
            response_die('ok', $response);
        }
    }

    /**
     * Find files under /back-end folder and use them.
     */
    public static function autoloadProjectPHPFiles() {
        $dir = Path::back();

        $dirs = subfolders($dir);
        foreach($dirs as $d) {

            $basename = basename($d);
            if (in_array($basename, [
                Definition('middlewares'),
                Definition('controllers')
            ])) {
                continue;
            }

            php_in($dir, true);
        }   
    }

    /**
     * Compare two paths
     * 
     * @param string Route being compared to current URI
     * @param array Current URI parts (array)
     */
    private static function matchesPath(string $route, array $URIparts) {

        // Current URI
        $r = URL::route();

        // Prepare the response
        $response = new stdClass();
        // Parameters in URL
        $response->params = new stdClass();
        // Segments matching
        $response->matches = 0;

        // If routes are exactly the same.
        if ($route == URL::route()) {
            // Then matching all
            $response->matches = count($URIparts);
            return $response;
        }

        // Split route into array
        $routeParts = Text::instance($route)->split('/', false);

        if (count($routeParts) > count($URIparts)) {
            // if defined route is longer than current URI, can't be a match.
            return $response;
        }

        // Iterate route parts (defined route in router, not the URI)
        $matches = 0;
        for($i = 0; $i < sizeof($routeParts); ++$i) {

            $uri = $URIparts[$i];
            $r = $routeParts[$i];

            // If route has a parameter in this position, assign
            if ($r[0] == ':') {

                $response->params->{substr($r, 1)} = $uri;

            } else {

                // If they don't match, stop here.
                if ($r != $uri) {
                    break;
                }

            }

            $matches += 1;

        }
        $response->matches = $matches;

        return $response;

    }

}