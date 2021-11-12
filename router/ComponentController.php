<?php

class ComponentController extends Controller {

    /**
     * Array $class -> ComponentController
     * 
     * @var array $_instances
     */
    private static $_instances = [];

    /**
     * Array $name -> ComponentController
     * 
     * @var array $_components
     */
    private static $_components = [];

    /**
     * Get instance of this component.
     * 
     * @return ComponentController
     */
    public static function instance() {
        return self::$_instances[get_called_class()];
    }

    /**
     * Get instance of a specific component by name.
     * 
     * @param string $name
     * 
     * @return ComponentController
     */
    public static function get(string $name) {
        return isset(self::$_components[$name]) ? self::$_components[$name] : null;
    }

    /**
     * Component name.
     * 
     * @var string $name
     */
    private string $name = '';

    /**
     * Find and import a ComponentController in a folder.
     * 
     * @param string $folder
     */
    public static function findController($folder) {
        if (!is_dir($folder)) return;
        $res = Folder::instance($folder)->subfiles('controller.php');
        if (!empty($res)) {
            require_once($res[0]);

            $classes = get_declared_classes();
            $last = $classes[sizeof($classes) - 1];
            
            if (is_subclass_of($last, "ComponentController")) {
                $obj = new $last();
                $obj->initialize();
            }
            else {
                throw new FrameworkException(
                    "Can't find ComponentController for directory $folder.",
                    "Make sure that your component (<b>$last</b>?) extends the ComponentController class.<br><br>Are you maybe overriding the Controller constructor? If so, please, don't override the constructor, use the method init() instead."
                );
            }
        }
    }

    /**
     * Initialize component.
     */
    protected function initialize() {
        
        $this->meta = new Generic();
        $this->findFile();

        self::$_instances[get_called_class()] = $this;
        
        $this->name = str_replace(Path::components() . '/', '', (Path::of($this->file)) );
        self::$_components[$this->name] = $this;
        $this->init();
        $this->load();
        $this->declareAjax();
    }

    /**
     * Find the file where this class is declared.
     */
    protected function findFile() {
        $reflector = new \ReflectionClass(get_called_class());
        $this->file = $reflector->getFileName();
        $this->found = true;
    }

    /**
     * Display component view.
     * 
     * @param array $parameters
     */
    public function display(array $parameters = []) {
        // Override
        $this->view($parameters);
    }

}