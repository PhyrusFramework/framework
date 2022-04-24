<?php

class Router {

    private static $routes = [];
    private static $finders = [];

    private static $middlewareCount = [];

    /**
     * Add a route manually
     * 
     * @param string $route
     * @param string|array $options Array or path to php file.
     */
    public static function add(string $route, mixed $options) {
        self::$routers[$route] = $options;
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
            'options' => $index,
            'params' => $params
        ];
    }

    static function launch($url = null) {

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
            // Load Front-End
            $html = Path::public() . '/index.html';

            if (file_exists($html)) {
                echo file_get_contents($html);
            }
            return;

        }

        // Load Back-End
        $ops = $res['options'];
        if (is_string($ops)) {
            $ops = include($ops);
        }

        if (!is_array($ops)) {
            return new Exception('Route PHP file does not return options array.');
        }

        self::run($ops, $res['params']);

    }

    private static function run($options, $params = []) {

        $req = new RequestData();
        $method = $req->method();

        if (!isset($options[$method])) {
            response_die('method-not-allowed');
            return;
        }

        $func = self::getMiddleware($options['middleware'] ?? 'default');
        $ret = null;

        if (!$func) {
            $ret = $options[$method]($req, $params);

        } else {
            $next = $func($req, $params);
            
            if ($next !== false) {
                $ret = $options[$method]($req, $params);
            } else return;
        }

        if (is_array($ret)) {
            response_die('ok', JSON::stringify($ret));
        } else if (is_string($ret) || is_numeric($ret)) {
            response_die('ok', $ret);
        }
    }

    private static function getMiddleware(string $name) {
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

    public static function reboot($newpath) {
        self::launch($newpath);
        return false;
    }

}