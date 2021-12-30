<?php

class CLI_Generate extends CLI_Module{

    public function command_page() {
        if (sizeof($this->params) < 1) {
            echo "\nPage route not specified\n";
            return;
        }

        $route = Path::src();
        $parts = explode('/', $this->params[0]);
        $last = '';
        foreach($parts as $p) {
            if ($p == '') continue;

            $route .= '/pages';
            if (!is_dir($route)) {
                create_folder($route);
            }

            $last = mb_strtolower($p);

            $route .= "/$last";
            if (!is_dir($route)) {
                create_folder($route);
            }
        }

        if ($last == '') return;

        $this->create_page($route, $last);
    }

    private function generateName($name) {
        
        $parts = explode('-', $name);
        $str = '';
        foreach($parts as $p) {
            $c = mb_strtolower($p);
            $str .= ucfirst($c);
        }

        return $str;

    }

    private function create_page($route, $name) {

        $lower = mb_strtolower($name);
        $ucfirst = $this->generateName($name);

        $file = $route . "/$lower.controller.php";
        if (!file_exists($file)) {
            $classname = $ucfirst.'PageController';

            ob_start();?>
<<?= '?' ?>php

class <?= $classname ?> extends Controller {

    function init() {

    }

    function display() {
        $this->view();
    }

}<?php
            $html = ob_get_clean();
            file_put_contents($file, $html);
        }

        $file = $route . "/$lower.view.php";
        if (!file_exists($file)) {
            file_put_contents($file, "<div>\n\tThis is the view\n</div>");
        }

        $file = $route . "/$lower.vue";
        if (!file_exists($file)) {
            file_put_contents($file, "<script>\nnew VueController({\n\t\n})\n</script>");
        }

        echo "\nPage created at $route\n";
    }

    public function command_component() {
        $this->sub_createComponent(false);
    }

    public function command_vueComponent() {
        $this->sub_createComponent(true);
    }

    private function sub_createComponent($vue = false) {
        if (sizeof($this->params) < 1) {
            echo "\nComponent name not specified\n";
            return;
        }

        $route = Path::src() . '/' . Definition('components');
        $parts = explode('/', $this->params[0]);
        $last = '';
        foreach($parts as $p) {
            if ($p == '') continue;

            $last = mb_strtolower($p);

            $route .= "/$last";
            if (!is_dir($route)) {
                create_folder($route);
            }
        }

        if ($last == '') return;

        $this->create_component($route, $last, $vue);
    }

    private function create_component($route, $name, $vue = false) {

        $lower = mb_strtolower($name);
        $ucfirst = $this->generateName($name);

        $file = $route . "/$lower.controller.php";
        if (!file_exists($file)) {
            $classname = $ucfirst.'Component';

            ob_start();?>
<<?= '?' ?>php
            
class <?= $classname ?> extends ComponentController {

    function init() {

    }
    <?php if (!$vue) { ?>
    function display($parameters = []) {
        $this->view($parameters);
    }<?php } ?>

}<?php
            $html = ob_get_clean();
            file_put_contents($file, $html);
        }

        $file = $route . "/$lower.view.php";
        if (!$vue && !file_exists($file)) {
            file_put_contents($file, "<div>\n\tThis is the view\n</div>");
        }

        $file = $route . "/$lower.vue";
        if (!file_exists($file)) {
            if (!$vue) {
                file_put_contents($file, "<script>\nnew VueController({\n\t\n})\n</script>");
            }
            else {
                file_put_contents($file, "<script>\nnew VueComponent('$lower', {\n\t\n})\n</script>");
            }
        }

        echo "\nComponent created at $route\n";
    }

    public function command_middleware() {

        if (sizeof($this->params) < 1) {
            echo "\Middleware name not specified\n";
            return;
        }

        $route = Path::src() . '/' . Definition('middlewares');
        $parts = explode('/', $this->params[0]);
        $last = '';
        foreach($parts as $p) {
            if ($p == '') continue;

            $last = mb_strtolower($p);

            $route .= "/$last";
            if (!is_dir($route)) {
                create_folder($route);
            }
        }

        if ($last == '') return;

        $lower = mb_strtolower($last);
        $ucfirst = $this->generateName($name);

        create_folder($route);
        $route .= "/$lower.controller.php";

        if (file_exists($route)) {
            echo "Middleware already exists\n";
            return;
        }

        ob_start();?>
<<?= '?' ?>php
            
class <?= $ucfirst ?>Middleware extends Middleware {

    function init() {

    }

    function display($controller = null) {
        $this->controller->display();
    }

}<?php
        $html = ob_get_clean();
        file_put_contents($route, $html);

        echo "\nMiddleware created at $route\n";
    }

    public function command_class() {

        if (sizeof($this->params) < 1) {
            echo "\nClass name not specified\n";
            return;
        }

        $cl = $this->params[0];
        $folder = sizeof($this->params) > 1 ? $this->params[1] : '/';

        $parts = explode('/', $folder);
        $route = Path::src() . '/code';
        foreach($parts as $p) {
            if (empty($p)) continue;

            $route .= "/$p";
            if (!is_dir($route))
                create_folder($route);
        }

        $route .= '/' . strtolower($cl) . '.php';

        $extend = '';
        if (isset($this->flags['extend'])) {
            $extend = 'extends ' . $this->flags['extend'] . ' ';
        }

        $namespace = '';
        if (isset($this->flags['namespace'])) {
            $namespace = $this->flags['namespace'];
        }
        
        ob_start();?>
<<?= '?' ?>php
<?= $namespace != '' ? "namespace $namespace;\n" : ''; ?>            
class <?= $cl ?> {

    function __construct() {

    }

}<?php
        $content = ob_get_clean();
        file_put_contents($route, $content);

        echo "\nClass created at $route\n";
    }

    public function command_test() {
        if (sizeof($this->params) < 1) {
            echo "\Test name not specified\n";
            return;
        }

        $route = Path::tests();
        create_folder($route);

        $parts = explode('/', $this->params[0]);
        $last = '';
        foreach($parts as $p) {
            if ($p == '') continue;

            if (!is_dir($route)) {
                create_folder($route);
            }

            $last = mb_strtolower($p);
            $route .= "/$last";
        }

        if ($last == '') return;

        $lower = mb_strtolower($last);
        $ucfirst = $this->generateName($last);

        $route .= '.php';

        ob_start();?>
<<?= '?' ?>php      
class <?= $ucfirst ?> extends Test {

    function run() {
        if (!true) {
            $this->addError('Something went wrong!');
        }
    }

}<?php
        $content = ob_get_clean();
        file_put_contents($route, $content);

        echo "\nTest created\n";
    }

    public function help() {?>

        The Generate command allows you to create project
        content in a moment: pages, components, classes, etc.

        - page <route>
        Create a page controller.

        - component <route>
        Create a component controller.

        - middleware
        Create a middleware in /src/middlewares

        - class <name> --extend=Parent --namespace=nmsp
        Create a class file in /src/code

        - test <name>
        Create a new test in /tests

    <?php }

}