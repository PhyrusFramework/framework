<?php

class Router {

    private static $routes = [];
    private static $finders = [];

    private static $middlewareCount = [];
    private static $registeredMiddlewares = [];

    /**
     * Register a middleware function by name.
     * 
     * @param string $name
     * @param callable $func
     */
    public static function addMiddleware(string $name, callable $func) {
        self::$registeredMiddlewares[$name] = $func;
    }

    /**
     * Use a registered middleware to get its response.
     * 
     * @param string $name
     * @param array $params
     * 
     * @return mixed
     */
    public static function useMiddleware(string $name, array $params = []) {
        if (!isset(self::$registeredMiddlewares[$name])) return true;

        return !(self::$registeredMiddlewares[$name](new RequestData(true), $params) === FALSE);
    }

    /**
     * Add a route manually
     * 
     * @param string $route
     * @param string|array $options Array or path to php file.
     */
    public static function add(string $route, $options) {

        $r = [];
        if (isset(self::$routes[$route])) {
            $r = self::$routes[$route];
        }

        $r = self::parseRoute($options, $r);
        
        self::$routes[$route] = $r;
    }

    /**
     * Convert the route specifications to the required format
     * 
     * @param $options
     * @param array $r
     * 
     * @return array
     */
    private static function parseRoute($options, array $r = []) : array {

        /* EXAMPLE OF $options
        [
            'middleware' => '...',
            'GET' => function() {...},
            'POST' => [
                'middleware' => '...',
                'info' => '...',
                function() => {...}  // <- index is always 0
            ]
        ]
        */

        foreach($options as $method => $v) {

            // Ignore middleware, we only want methods (GET, POST, ...)
            if ($method == 'middleware') continue;

            // Build the object. Action = function
            $m = [
                'action' => is_array($v) ? $v[0] : $v
            ];

            // If set, add the middleware
            if (isset($options['middleware'])) {
                $m['middleware'] = $options['middleware'];
            } else if (is_array($v) && isset($v['middleware'])) {
                $m['middleware'] = $v['middleware'];
            }

            // Add info object
            if (isset($options['info'])) {
                $m['info'] = $options['info'];
            }

            // Add to the current route
            $r[$method] = $m;
        }

        return $r;

    }

    /**
     * Add a finder
     * 
     * @param callable $callback
     */
    public static function finder(callable $callback) {
        self::$finders[] = $callback;
    }

    /**
     * Compare two paths
     * 
     * @param string $a
     * @param string $b
     */
    private static function comparePaths(string $a, string $b) {

        if ($a == $b) return [
            'options' => null,
            'params' => []
        ];

        $params = [];

        $a = str_replace('\\', '/', $a);
        $b = str_replace('\\', '/', $b);

        $partsA = Text::instance($a)->split('/', false);
        $partsB = Text::instance($b)->split('/', false);

        if (sizeof($partsA) != sizeof($partsB)) {
            return null;
        }

        for($i = 0; $i < sizeof($partsA); ++$i) {

            $pa = $partsA[$i];
            $pb = $partsB[$i];

            if ($pa[0] == ':') {
                $params[substr($pa, 1)] = $pb;
            } else {
                if ($pa != $pb) {
                    return null;
                }
            }

        }

        return [
            'options' => null,
            'params' => $params
        ];


    }

    /**
     * Find the routing automatically.
     */
    private static function automaticRouting($path) {

        $dir = Path::routes();
        $params = [];
        $index = null;

        foreach($path as $p) {
            $d = "$dir/$p";

            if (file_exists($d)) {
                $dir = $d;
                continue;
            }

            $dirs = subfolders($dir);
            $found = false;

            foreach($dirs as $subdir) {
                $name = basename($subdir);
                if ($name[0] == '_') {
                    $dir = $subdir;
                    $params[substr($name, 1)] = $p;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $files = subfiles($dir, 'php');
                foreach($files as $f) {
                    $name = basename($f);
                    if ($name[0] == '_') {
                        $n = str_replace('.php', '', substr($name, 1));
                        $params[$n] = $p;
                        $index = $f;
                        break;
                    }
                }

                if ($index == null)
                    return null;
            }
        }

        if ($index == null) {
            $index = "$dir/index.php";
        }

        if (!file_exists($index)) {
            return null;
        }

        return [
            'options' => self::parseRoute(include($index)),
            'params' => $params
        ];
    }

    static function launch($url = null) {

        // Load php files automatically
        self::loadAutoloads();

        $u = $url ?? URL::route();
        $path = URL::path($u);

        /*
        1 - Hay finders?
        2 - Hay manual routes?
        3 - Automatic routing
        */

        $res = null;

        // Finders
        foreach(self::$finders as $finder) {
            $options = $finder($u);
            if ($options != null) {
                $res = [
                    'options' => $options,
                    'params' => []
                ];
            }
        }

        if (!$res) {

            // Manual routing
            foreach(self::$routes as $route => $ops) {
                $res = self::comparePaths($route, $u);

                if ($res != null) {
                    $res['options'] = $ops;
                    break;
                }
            }

            if (!$res) {

                // Automatic routing
                $res = self::automaticRouting($path);

            }

        }

        if (!$res) {

            if (Config::get('project.only_API')) {
                response_die('not-found', 'Route not found');
            }

            // Load Front-End
            $html = Path::public() . '/index.html';

            if (file_exists($html)) {
                echo file_get_contents($html);
            }
            return;

        }

        header('Content-Type: application/json');

        // Load Back-End
        $ops = $res['options'];
        if (is_string($ops)) {
            $ops = include($ops);
        }

        if (!is_array($ops)) {
            return new Exception('Route PHP file does not return options array.');
        }

        self::run($ops, new Generic($res['params']));

    }

    private static function run($options, $params = []) {

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $req = new RequestData(in_array($method, ['GET', 'DELETE']));

        if (!isset($options[$method])) {
            response_die('method-not-allowed');
            return;
        }

        $m = $options[$method];

        if (!isset($m['action'])) {
            response_die('not-found');
            return;
        }

        $md = $m['middleware'] ?? 'default';
        $action = $m['action'];

        $func = null;
        if (is_callable($md)) {
            $func = $md;
        } else if (is_string($md)) {
            $func = self::getMiddleware($md);
        }
        $ret = null;

        if (!$func) {
            $ret = $action($req, $params);

        } else {
            $next = $func($req, $params);
            
            if ($next !== false) {
                $ret = $action($req, $params);
            } else {
                response_die('unauthorized');
            }
        }

        if (!$ret) {
            response_die('ok');
        }

        if (is_array($ret) || method_exists($ret, 'jsonSerialize')) {
            response_die('ok', JSON::stringify($ret));
        } else if (is_string($ret) || is_numeric($ret)) {
            response_die('ok', $ret);
        }
    }

    private static function getMiddleware(string $name) {

        if (isset(self::$registeredMiddlewares[$name])) {
            $md = self::$registeredMiddlewares[$name];
            if (is_callable($md)) {
                return $md;
            }
        }

        $file = Path::middlewares() . "/$name.php";

        if (!file_exists($file)) return null;
        self::secureInfiniteLoop($name);

        return include($file);
    }

    /**
     * Make sure not to enter an infinite loop.
     * 
     * @param string $middleware
     */
    private static function secureInfiniteLoop(string $middleware) {
        $count = self::$middlewareCount[$middleware] ?? 0;
        $count += 1;
        self::$middlewareCount[$middleware] = $count;

        if ($count >= 5) {
            throw new Exception('Infinite loop using middlewares.');
        }
    }

    /**
     * Make a 301 redirection to another URL.
     * 
     * @param string path
     */
    public static function redirectTo(string $path) {
        // Permanent 301 redirection
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: $path");
        exit();
    }

    /**
     * Load PHP files in autoload paths.
     */
    public static function loadAutoloads() {

        $root = Path::back();
        $file = $root . '/autoload.php';
        if (!file_exists($file)) return;

        $json = include($file);

        // Files imported always
        if (isset($json['always'])) {
            foreach($json['always'] as $path) {
                $p = $path[0] == '/' ? $path : "/$path";
                $f = $root . $p;
                if (!file_exists($f)) return;

                php_in($f);
            }
        }

        // Files imported when class is used
        if (isset($json['classByFile'])) {
            foreach($json['classByFile'] as $path) {
                $p = $path[0] == '/' ? $path : "/$path";
                $f = $root . $p;
                if (!file_exists($f)) return;

                php_in($f, true);
            }
        }

        if (isset($json['classByName'])) {

            foreach($json['classByName'] as $name => $path) {
                $p = $path[0] == '/' ? $path : "/$path";
                $f = $root . $p;
                if (!file_exists($f)) return;

                autoload($name, $f);
            }

        }
    }

    /**
     * Get the routes.
     * 
     * @return array
     */
    public static function getRoutes() : array {
        return self::$routes;
    }

    /**
     * Get the info of an endpoint.
     * 
     * @param string URL path
     * @param string Method
     * 
     * @return mixed
     */
    public static function getInfo(string $route, string $method) {
        if (!isset(self::$routes[$route])) return null;
        $r = self::$routes[$route];
        if (!isset($r[$method])) return null;
        if (!isset($r[$method]['info'])) return null;
        $info = $r[$method]['info'];
        if (gettype($info) != 'callable') return $info;
        return $info();
    }

}