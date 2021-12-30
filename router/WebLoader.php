<?php

/**
 * Display a component.
 * 
 * @param string $name Component name
 * @param array $parameters
 */
function component(string $name, array $parameters = []) {

    if (!class_exists($name)) {
        if (Config::get('development_mode')) {
            $suggestion = "You are trying to use a Component ($name) that is not imported for this page. Add the component to the init() function of the controller.";
            try {
            throw new FrameworkException('Component not imported', $suggestion);
            } catch(Exception $e) {
                echo $e->getMessage() . ": $suggestion";
            }
        }
        return;
    }

    $name::instance()->display($parameters);
}

class WebLoader {

    /**
     * Use the router to find the Middleware and Controller.
     * 
     * @param string $path URL [Default current]
     */
    public static function router($path = null) {
        // Load global assets

        // Moved to index.php, after loading framework modules.
        //Assets::importFrameworkAssets();

        Assets::assets_in(Path::assets());
        self::php_in(Path::code());

        // Find and load controller
        Router::init($path);
        $controller = Controller::current();
        $middleware = self::middleware($controller);
        $controller->load();

        if ($controller->middleware != null) {
            $controller->middleware->setController($controller);
            // setController does prepare
        }
        $controller->doPrepare();

        // $route
        Footer::add(function() use($controller) {
            Javascript::define([
                '$route' => [
                    'route' => URL::route(),
                    'path' => URL::path(),
                    'query' => URL::parameters(),
                    'parameters' => $controller->parameters->toArray()
                ]
            ]);
        });
    }

    /**
     * Launch the website
     */
    public static function launch() {

        if (WebLoader::isAjaxRequest()) {
            WebLoader::launchAjax();
            return;
        }

        if (defined('PERFORMANCE_ANALYZER')) {
            ob_start();
        }

        self::router();
        $controller = Controller::current();
        $middleware = $controller->middleware;

        if (defined('PERFORMANCE_ANALYZER')) {
            CLI_Performance::record('Start printing page content');
        }

        if (!$controller->raw)
        {
?>
<!DOCTYPE html>
<html>
<head>
    <?php 
    self::headlines();
    ?>
</head>
<body><?php if (Config::get('assets.js.vue')) { ?>
<div id="app-body"><?php }
}

if (!$controller->found)
    response('not_found');

$middleware->displayHierarchy();
if (Config::get('assets.js.vue')) { ?></div><?php }

if (!$controller->raw){
    self::footlines();
?>
</body>
</html>
<?php }

        if (defined('PERFORMANCE_ANALYZER')) {
            ob_clean();
        }

    }

    /**
     * [Managed by framework] Display middleware content.
     */
    private static function middleware($controller) {
        $middleware = $controller->middleware;

        if ($middleware == null) {
            $controller->middleware = Middleware::get('default');
        }

        return $middleware;
    }

    /**
     * Import any php file in this directory or its subfolders.
     * 
     * @param string $directory
     */
    public static function php_in(string $directory) {

        if (!is_dir($directory)) return;


        $dirsqueue = [$directory];
        while (sizeof($dirsqueue) > 0)
        {
            $dir = array_shift($dirsqueue);

            $files = glob($dir.'/*.php');
            foreach($files as $file)
            {
                $filename = file_name($file);

                if (strpos($filename, '.view.php')) {
                    view($file);
                } else {
                    include($file);
                }
            }
                
            $dirs = array_filter(glob($dir . '/*'), 'is_dir');
            foreach($dirs as $dir)
            {
                $dirsqueue[] = $dir;
            }
        }
    }

    /**
     * Display the <head> content.
     */
    public static function headlines() {
        
        $controller = Controller::current();
        ?>
        <title><?php echo $controller->meta->title; ?></title>
        <meta charset="<?php echo $controller->meta->charset; ?>">
        <meta name="description" content="<?php echo $controller->meta->description; ?>">
        <meta name="keywords" content="<?php echo $controller->meta->keywords; ?>">
        <meta name="author" content="<?php echo $controller->meta->author; ?>">
        <meta name="viewport" content="<?php echo $controller->meta->viewport; ?>">
        <?php
        if (Cache::assets_cached())
        {
            ?>
            <link rel="stylesheet" href="<?php echo Path::src(true); ?>/cache/assets/web.css">
            <script type="text/javascript" src="<?php echo Path::src(true); ?>/cache/assets/web.js"></script>
            <?php
        }
        else{
            Assets::loadQueuedAssets();
            Cache::minify();
        }
    
        Head::print();
    
    }
    
    /**
     * Display the footer content.
     */
    public static function footlines() {

        if (Config::get('development_mode'))
        DebugConsole::print();

        echo "<div id='" . Definition('foot') . "' style='display:none'>";

        Footer::print();

        if (Config::get('assets.js.vue')) { ?><script>VueLoader.pageLoaded();</script><?php }
    
        ?>
<script>
let footer = document.getElementById('<?= Definition('foot') ?>');
let scripts = footer.querySelectorAll('script');
for(let script of scripts) {
    script.remove();
}
if (footer.childNodes.length == 0) {
    footer.remove();
}
</script>
        <?php
        echo '</div>';

    }

    /**
     * Check if the current request is an ajax request.
     */
    public static function isAjaxRequest() {
        $req = new RequestData(true);
        return $req->has('ajaxActionName');
    }

    /**
     * Load accessed path as an Ajax request.
     */
    public static function launchAjax() {
        require(realpath(__DIR__ . '/../ajax/ajax.php'));
    }

}