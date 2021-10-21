<?php

/**
 * Class that handles any error or exception occurred in the website.
 * When the debug mode is ON, the error page will be displayed.
 */
class ErrorHandler {

    /**
     * Indicates if the ErrorHandler has already
     * been used once. (For multiple errors)
     * 
     * @var bool $_used
     */
    private static bool $_used = false;

    /**
     * Handle incoming error or exception.
     * 
     * @param mixed $error
     */
    public static function handle($error) {

        if (self::$_used) return;
        self::$_used = true;

        if (is_array($error)) {
            self::handleError(new Generic($error));
        }
        else {
            self::handleException($error);
        }
    }

    /**
     * Get the extract of code to display along with the error.
     * 
     * @param string $file
     * @param string $line
     * 
     * @return string
     */
    private static function getErrorCodeLines(string $file, int $line) : string {

        if (!file_exists($file)) {
            return '';
        }

        $lines = file($file);
        if (sizeof($lines) <= $line) {
            return '';
        }

        $min = $line - 3;
        $max = $line + 3;

        if ($min < 0) $min = 0;
        if ($max >= sizeof($lines)) {
            $max = sizeof($lines) - 1;
        }

        if ($min > $max) return '';

        $str = '';
        for($n = $min; $n <= $max; ++$n) {
            $l = htmlspecialchars($lines[$n]);

            $l = str_replace(' ', '&nbsp;&nbsp;', $l);


            if ($n == $line - 1) {
                $str .= "<div class='guilty'>$l</div>";
            } else {
                $str .= "<div>$l</div>";
            }
        }

        return $str;

    }

    /**
     * Handle error, not exception.
     * 
     * @param Generic $err
     */
    private static function handleError(Generic $err) {

        self::display([
            'title' => 'Error',
            'subtitle' => 'Fatal error',
            'message' => $err->message,
            'file' => Path::toRelative($err->file),
            'line' => $err->line,
            'suggestion' => self::findSuggestion($err->message, $err->file),
            'backtrace' => $err->backtrace,
            'code' => self::getErrorCodeLines($err->file, $err->line)
        ]);
    }

    /**
     * Handle incoming exception.
     * 
     * @param Exception $exc
     * 
     */
    private static function handleException($exc) {
        
        self::display([
            'title' => 'Exception',
            'subtitle' => get_class($exc),
            'message' => $exc->getMessage(),
            'file' => Path::toRelative($exc->getFile()),
            'line' => $exc->getLine(),
            'suggestion' => property_exists($exc, 'suggestion') ? $exc->suggestion 
            : self::findSuggestion($exc->getMessage(), $exc->getFile()),
            'backtrace' => $exc->getTrace(),
            'code' => self::getErrorCodeLines($exc->getFile(), $exc->getLine())
        ]);

    }

    /**
     * Display error view
     * 
     * @param array $parameters Error data
     */
    private static function display(array $parameters) {

        if (!empty(ob_get_status())) {
            ob_clean();
        }
        
        header('HTTP/1.0 500 Internal Server Error');
        http_response_code(500);

        if (!class_exists('RequestData')) {

            if (!defined('USING_CLI')) {
                self::view($parameters);
            } else {
                self::json($parameters);
            }
            return;
        }

        $req = new RequestData();
        if ($req->method() == 'POST' || $req->has('ajaxActionName') || defined('USING_CLI')) {
            self::json($parameters);
        }
        else {
            self::view($parameters);
        }
    }

    /**
     * Display error HTML View
     * 
     * @param array $parameters Error data
     */
    private static function view(array $parameters) {
        foreach($parameters as $k => $v) {
            ${$k} = $v;
        }
        include(__DIR__ . '/error_page/view.php');
        ?>
        <script src="/framework/assets/javascript/jquery.js"></script>
        <?php
        DebugConsole::print();
    }

    /**
     * Display error as a JSON string.
     * 
     * @param array $parameters Error data
     */
    private static function json(array $parameters) {
        echo JSON::stringify([
            'type' => $parameters['title'],
            'subtype' => $parameters['subtitle'],
            'message' => $parameters['message'],
            'file' => $parameters['file'],
            'line' => $parameters['line']
        ], defined('USING_CLI'));
    }

    /**
     * Try to generate a suggestion based on the error.
     * 
     * @param string $msg Error message
     * @param string $file File location.
     */
    private static function findSuggestion(string $msg, string $file) {

        if (strpos($msg, 'Undefined variable:') !== false) {

            $name = str_replace('Undefined variable: ', '', $msg);

            if (strpos($file, '.view.php')) {
                return "Variable $name is not defined. It seems you are using a .view.php file. So probably you should pass the value through the controller.";
            }

            return "Variable $name is not defined. Give it some value first.";
        }

        if (strpos($msg, 'Undefined index:') !== false) {
            $name = str_replace('Undefined index: ', '', $msg);

            return "You are trying to access the property '$name' of an array, but that array does not have any key named '$name'.";
        }

        if (strpos($msg, 'Class') !== false && strpos($msg, 'not found') !== false) {
            return "Possibly you are not including the PHP file that defines this class, or you are executing this script too soon and the class hasn't been declared yet.<br><br>Also make sure that the class name is spelled correctly. You can check if a class exists with <b>if (class_exists('class name'))</b> or get a list of declared classes with <b>get_declared_classes()</b>";
        }

        if (strpos($msg, 'Middleware::$_instance must not be accessed before initialization') !== false) {
            return "The current page hasn't a valid middleware. There must be at least a default middleware in the project.";
        }

        if (strpos($msg, "yped static property") !== false && strpos($msg, "must not be accessed before initialization") !== false) {
            return "You are accessing a static value that hasn't a value yet. You need to initialize the static value first, even if it's null.";
        }

        return null;
    }

}

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    ErrorHandler::handle([
        'number' => $errno,
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline,
        'backtrace' => debug_backtrace()
    ]);
});
set_exception_handler(function($exception) {
    ErrorHandler::handle($exception);
});