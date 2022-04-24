<?php

class Router {

    /**
     * Classic routing defined routes.
     */
    static $routes = [];

    /**
     * Route finders
     */
    static $finders = [];

    /**
     * Add a route manually.
     * 
     * @param string $route
     * @param mixed Path to controller relative to /src or Callable function
     */
    public static function add(string $route, mixed $path) {

        self::$routes[$route] = $path;

    }

    /**
     * Add a route finder.
     * 
     * @param callable $finder
     */
    public static function addFinder(callable $finder) {
        self::$finders[] = $finder;
    }

    /**
     * Run the router to find and load the current page.
     * 
     * @param string $path URL [Default current]
     */
    public static function init($path = null) {

        self::findController($path);
        EventListener::trigger('afterRouter');
        Assets::stopMinify();

        if (defined('PERFORMANCE_ANALYZER')) {
            CLI_Performance::record('Router loaded the page');
        }
    }

    /**
     * Find a page controller in a directory.
     * 
     * @param string $path [Default current URI]
     */
    public static function findController(string $path = null) {

        $route = $path == null ? URL::route() : $path;

        // Finders
        foreach(self::$finders as $finder) {
            $result = $finder($route);
            if (!empty($result) && is_string($result)) {
                $folder = $result;
                if (is_dir($folder)) {
                    return self::loadPage($folder, []);
                }
            }
        }

        // If route is "" or "/", -> home
        $parts = URL::path($route);
        if (empty($parts)) return self::loadHomepage();

        // Classic routing
        $routes = self::$routes;
        foreach($routes as $route => $folder) {
            $urlParts = URL::path($route);

            if (sizeof($urlParts) != sizeof($parts))
                continue;

            $match = true;
            $parameters = [];

            for($i = 0; $i < sizeof($urlParts); ++$i) {

                // Parameter detected
                if ($urlParts[$i][0] == ':') {
                    $param = substr($urlParts[$i], 1);
                    $parameters[$param] = $parts[$i];
                    continue;
                }

                if ($urlParts[$i] != $parts[$i]) {
                    $match = false;
                    break;
                }
            }

            if ($match) {
                $fullpath = $folder;

                if (is_callable($folder)) {
                    $resp = $folder($parameters);
                    if ($resp) {
                        response_die('ok', $resp);
                    }
                    die();
                }

                if (is_dir($fullpath)) {
                    self::findPageController($fullpath);
                    return self::loadPage($fullpath, $parameters, false);
                }
            }
        }

        // Auto-routing
        $parameters = [];
        $root = Path::src();

        for($i = 0; $i < sizeof($parts); ++$i) {

            $part = $parts[$i];

            $next = "$root/pages/$part";
            $controller = Controller::current();

            if (!is_dir($next)) {

                $parameter = null;
                $dirs = array_filter(glob("$root/pages/*"), 'is_dir');
                foreach($dirs as $dir) {
                    $basename = basename($dir);
                    if ($basename[0] == '_') {
                        $parameter = substr($basename, 1);
                        break;
                    }
                }

                if ($parameter == null) {
                    return self::load404();
                } else {
                    $parameters[$parameter] = $part;
                    $root = "$root/pages/_$parameter";
                    self::findPageController($root);
                }


            } else {

                // Continue routing
                $root = $next;

                // If there is a controller here that specifies automatic disabled, stop.
                self::findPageController($next);
                if ($controller != null && $controller->automatic === false) {
                    return self::load404();
                }

            }

        }

        self::loadPage($root, $parameters, false);

    }

    /**
     * Load the controller in a folder.
     * 
     * @param string $folder
     * @param array $parameters
     * @param bool $loadController or use the current one.
     */
    public static function loadPage(string $folder, array $parameters, bool $loadController = true) {

        $controller = $loadController ? self::findPageController($folder) : Controller::current();

        if ($controller == null || str_replace('\\', '/', $folder) != $controller->directory()) {

            if (Config::get('project.development_mode')) {
                $subpages = "$folder/pages";
                if (!is_dir($subpages)) {
                    throw new FrameworkException('404 Not found, Controller missing',
                'You are trying to access the route "'.$folder.'" that exists in your project but has no controller or subpages, so it\'s useless.'
                .'<br><br>Perhaps you forgot to create the controller?<br><br>'
                .'<b>This message is only displayed in debug mode, in production the 404 screen will be displayed.</b>'
                . '<h4>Possible solutions</h4>'
                . '<ul><li>If you want a page here, create the controller. Remember to extend the Controller class.</li>'
                . '<li>If this is not a page, delete the folder \''.Path::toRelative($folder).'\'.</li></ul>');
                    return;
                }

            }
            self::load404();
            return;

        }

        $controller->parameters = new Generic($parameters);

    }

    /**
     * Load the 404 Controller.
     */
    public static function load404() {

        response('not_found');
        $path = Path::pages() . '/' . Definition('404');
        if (!is_dir($path)) return null;
        
        $controller = self::findPageController($path);
        $controller->parameters = new Generic();
    }

    /**
     * Load the home page controller.
     */
    public static function loadHomepage($parameters = []) {

        // path to homepage folder (/web/pages/_homepage)
        $path = Path::pages() . '/' . Definition('homepage');
        // If doesn't exist -> 404
        if (!is_dir($path)) {
            return self::load404();
        }
        
        // Load homepage with parameters
        return self::loadPage($path, $parameters);

    }

    /**
     * Get and load the controller from the folder.
     * 
     * @param string $folder
     * 
     * @return Controller
     */
    private static function findPageController(string $folder) {
        Controller::findController($folder);
        return Controller::current();
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

}