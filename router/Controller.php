<?php

class Controller {

    /**
     * [Managed by Framework] Current page controller.
     * 
     * @var Controller $currentController
     */
    protected static $currentController;

    /**
     * Get the last loaded controller.
     * 
     * @return Controller
     */
    public static function current() {
        return self::$currentController;
    }

    /**
     * [Managed by framework] Loaded controllers.
     * 
     * @var Controller[] $_loaded
     */
    private static $_loaded = [
        'modules' => [],
        'components' => []
    ];

    /**
     * Page meta.
     * 
     * @var Generic $meta
     */
    public Generic $meta;

    /**
     * Controller middleware.
     * 
     * @var Middleware $middleware
     */
    public $middleware;

    /**
     * Is raw output
     * 
     * @var bool $raw
     */
    public bool $raw = false;

    /**
     * Use automatic routing with this controller?
     * 
     * @var bool $automatic
     */
    public bool $automatic = true;

    /**
     * List of components used by this controller.
     * 
     * @var array $components
     */
    public array $components = [];

    /** 
     * Dynamic parameters in the route.
     * 
     * @var mixed $parameters
    */
    public $parameters = [];

    /**
     * Define ajax functions: [ name => class_method ]
     * 
     * @var array ajax
     */
    public $ajax = [];

    /**
     * Controller file path
     * 
     * @var string $file
     */
    protected string $file;

    /**
     * Was this path found or redirected to 404?
     * 
     * @var bool $found
     */
    public bool $found = true;

    public function __get($name) {
        if ($name == 'name') return get_called_class();
    }

    /**
     * Find and load a controller in a directory
     * 
     * @param string $folder
     */
    public static function findController(string $folder) {
        if (!is_dir($folder)) return;
        $res = Folder::instance($folder)->subfiles('controller.php');
        if (sizeof($res) > 0) {
            $classcount = sizeof(get_declared_classes());
            require_once($res[0]);

            $classes = get_declared_classes();

            if (sizeof($classes) == $classcount) {
                throw new FrameworkException(
                    "Can't find Controller for directory $folder.",
                    "Make sure that your controller (<b>$last</b>?) extends the Controller class.<br><br>Are you maybe overriding the Controller constructor? If so, please, don't override the constructor, use the method init() instead."
                );
                return;
            }

            $last = $classes[$classcount];
            
            if (is_subclass_of($last, "Controller")) {
                $obj = new $last();
                $obj->initialize();
            }
            else {
                throw new FrameworkException(
                    "Can't find Controller for directory $folder.",
                    "Make sure that your controller (<b>$last</b>?) extends the Controller class.<br><br>Are you maybe overriding the Controller constructor? If so, please, don't override the constructor, use the method init() instead."
                );
            }
        }
    } 

    public function __construct() { }

    /**
     * [Managed by framework] Initializes the controller.
     * 
     */
    protected function initialize() {
        
        $this->loadDefaultMeta();
        $this->findFile();

        if (self::$currentController != null) {
            $this->_merge(self::$currentController);
        }
        self::$currentController = $this;

        $this->init();
        $this->declareAjax();
    }

    protected function declareAjax() {
        $ctrl = $this;
        foreach($this->ajax as $k => $v) {
            $name = is_string($k) ? $k : $v;
            Ajax::add($name, function($req) use($ctrl, $v) {
                $ret = $ctrl->{$v}($req);
                if ($ret !== null) {
                    if (is_array($ret) && sizeof($ret) == 0) {
                        echo '[]';
                    } else 
                    echo JSON::stringify($ret);
                }
            });
        }
    }

    /**
     * Controller directory.
     * 
     * @return string
     */
    public function directory() : string {
        return str_replace('\\', '/', dirname($this->file));
    }

    /**
     * [Managed by framework] Finds the file where the controller is declared.
     */
    protected function findFile() {
        $reflector = new \ReflectionClass(get_called_class());
        $this->file = str_replace('\\', '/', $reflector->getFileName());

        $directory = dirname($this->file);
        $this->meta->title = basename($directory);

        $this->found = ($directory != Path::pages() . Definition('404'));
    }

    /**
     * Initializes the default page meta.
     */
    private function loadDefaultMeta() {
        $this->meta = new Generic([
            'title' => '',
            'description' => '',
            'keywords' => '',
            'author' => '',
            'charset' => 'UTF-8',
            'viewport' => 'width=device-width, initial-scale=1'
        ]);
    }

    /**
     * This controller inherits properties from another controller.
     * 
     * @param Controller $controller
     */
    private function _merge(Controller $controller) {
        $this->middleware = $controller->middleware;
        $this->raw = $controller->raw;
        arr($controller->parameters)->merge($this->parameters);
    }

    /**
     * Loads the Controller assets (PHP, components, css, js)
     */
    public function load() {

        // Load resources
        $directory = dirname($this->file);

        // Components
        foreach($this->components as $cmp) {

            if (is_string($cmp)) {
                if (in_array($cmp, self::$_loaded['components'])) {
                    continue;
                }
                self::$_loaded['components'][] = $cmp;
                ComponentController::findController(Path::components() . "/$cmp");
            } else {
                $cmp->initialize();
            }
        }
        
        // Assets
        Assets::assets_in("$directory/assets");

        // Vue controller
        $res = Folder::instance($directory)->subfiles('vue');
        if (sizeof($res) > 0) {
            $result = VueParser::process($res[0]);
            if (isset($result['js'])) {
                Assets::include_js(Path::toRelative($result['js']), true);
            }
            if (isset($result['css'])) {
                Assets::scss_in(dirname($result['css']), false);
            }
        }        

        // Code
        $code = $directory . '/' . Definition('code');
        if (is_dir($code)) {
            WebLoader::php_in($code);
        }
    }

    /**
     * Init function to be overrided in each Controller.
     */
    protected function init() {
        // Override
    }

    /**
     * Execute preparation methods
     */
    public function doPrepare() {
        $this->prepare();

        $js = $this->javascript();
        if ($js != null) {
            Head::add(function() use ($js) { 
                Javascript::define($js); 
            });
        }

        // Components
        foreach($this->components as $cmp) {

            if (is_string($cmp)) {

                $comp = ComponentController::get($cmp);

                if ($comp != null) {
                    $comp->doPrepare();
                }

            } else {
                $cmp->doPrepare();
            }
        }
    }

    /**
     * Declare javascript values to use in this page.
     */
    public function javascript() {
        // Override
    }

    /**
     * Action after Controller is loaded but before page is displayed.
     */
    public function prepare() {
        // Override
    }

    /**
     * Displays the Controller content.
     * 
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
        $parameters['controller'] = $this;

        if (file_exists($file)) {
            view($file, $parameters);
        }
    }

}