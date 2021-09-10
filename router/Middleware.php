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
     * [Managed by framework] Page controller
     */
    private Controller $controller;

    public function setController(Controller $controller) {
        $this->controller = $controller;
    }

    /**
     * Get the Middleware by the name of the folder.
     * 
     * @param string $name
     * 
     * @return Middleware
     */
    public static function get(string $name) : Middleware {

        if (isset(self::$_loadedMiddlewares[$name])) {
            return self::$_loadedMiddlewares[$name];
        }

        $folder = Path::middlewares() . "/$name";
        if (is_dir($folder)) Middleware::findController($folder);
        else if ($name != 'default') {
            $name = 'default';
            $folder = Path::middlewares() . "/$name";
            Middleware::findController($folder);
        }

        $instance = Middleware::instance();
        self::$_loadedMiddlewares[$name] = $instance;
        return $instance;
    }

    /**
     * Finds a Middleware controller in a directory.
     */
    public static function findController($folder) {
        if (!is_dir($folder)) return;
        $res = Folder::instance($folder)->subfiles('controller.php');
        if (sizeof($res) > 0) {
            require_once($res[0]);
            $classes = get_declared_classes();
            $lastMiddleware = $classes[sizeof($classes) - 1];
            
            if (is_subclass_of($lastMiddleware, 'Middleware')) {
                $obj = new $lastMiddleware();
                $obj->initialize();
            }
            else {
                throw new FrameworkException(
                    "Can't find Middleware in directory $folder.",
                    "Make sure that your middleware (<b>$lastMiddleware</b>?) extends the Middleware class.<br><br>Are you maybe overriding the Middleware constructor? If so, please, don't override the constructor, use the method init() instead."
                );
            }
        }
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
            $this->controller->view();
        }
    }

}