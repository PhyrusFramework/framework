<?php

class Middleware extends Controller {

    /**
     * [Managed by Framework] Middlewares already loaded
     * 
     * @var array
     */
    private static array $_loadedMiddlewares = [];

    /**
     * [Managed by Framework] Instance of the current middleware
     * 
     * @var Middleware
     */
    private static Middleware $_instance;

    /**
     * Get the instance of the current Middleware.
     * 
     * @return Middleware
     */
    public static function instance() {
        return self::$_instance;
    }

    /**
     * Create and initialize a middleware from a previously imported class.
     * 
     * @return Middleware
     */
    public static function instantiate() {
        $cl = get_called_class();
        $mid = new $cl();
        $mid->initialize();
        return $mid;
    }

    /**
     * [Managed by framework] Page controller
     */
    protected Controller $controller;

    public function setController(Controller $controller) {
        $this->controller = $controller;

        if ($this->middleware != null) {
            $this->middleware->setController($this);
        }

        $this->doPrepare();
    }

    /**
     * Get the Middleware by the name of the folder.
     * 
     * @param string $name
     * 
     * @return Middleware
     */
    public static function get(string $name) : ?Middleware {

        if (isset(self::$_loadedMiddlewares[$name])) {
            return self::$_loadedMiddlewares[$name];
        }

        $mid = null;
        $folder = Path::middlewares() . "/$name";
        if (is_dir($folder)) {
            $mid = self::findController($folder);
        } else if ($name != 'default') {
            $name = 'default';
            $folder = Path::middlewares() . "/$name";
            self::findController($folder);
        }

        if ($mid == null) return null;
        self::$_loadedMiddlewares[$name] = $mid;
        return $mid;
    }

    /**
     * Finds a Middleware controller in a directory.
     */
    public static function findController($folder) : ?Middleware {
        if (!is_dir($folder)) return null;

        $res = Folder::instance($folder)->subfiles('controller.php');
        if (sizeof($res) > 0) {
            require_once($res[0]);
            $classes = get_declared_classes();
            $lastMiddleware = $classes[sizeof($classes) - 1];
            
            if (is_subclass_of($lastMiddleware, 'Middleware')) {
                $obj = new $lastMiddleware();
                $obj->initialize();
                return $obj;
            }
            else {
                throw new FrameworkException(
                    "Can't find Middleware in directory $folder.",
                    "Make sure that your middleware (<b>$lastMiddleware</b>?) extends the Middleware class.<br><br>Are you maybe overriding the Middleware constructor? If so, please, don't override the constructor, use the method init() instead."
                );
            }
        }

        return null;
    }

    /**
     * Initializes the middleware.
     */
    protected function initialize() {
        
        $this->meta = new Generic();
        $this->findFile();

        self::$_instance = $this;
        $this->init();
        $this->load();
        $this->declareAjax();
    }

    /**
     * Finds the file where the Middleware is declared.
     */
    protected function findFile() {
        $reflector = new \ReflectionClass(get_called_class());
        $this->file = $reflector->getFileName();
        $this->found = true;
    }

    /**
     * Display middleware or parent middleware if exists.
     */
    public function displayHierarchy() {

        if ($this->middleware != null) {
            $this->middleware->displayHierarchy();
        } else {
            $this->display();
        }
    }

    /**
     * Displays the middleware content.
     */
    public function display() {
        // Override
        $this->view();
    }

    /**
     * If exists, displays the View file in the same folder.
     * 
     * @param mixed $parameters array or Arr object.
     */
    public function view($parameters = []) {
        $file = str_replace('controller.php', 'view.php', $this->file);
        $parameters['controller'] = $this->controller;

        if (file_exists($file)) {
            view($file, $parameters);
        } else if ($this->controller != null) {
            $this->controller->display();
        }
    }

}