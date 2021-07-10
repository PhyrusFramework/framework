<?php

/**
 * Helper class to debug during development.
 * 
 */
class DebugConsole {

    /**
     * List of logs
     *
     * @var array logs
     */
    private static array $logs = [];

    /**
     * Log something into the debug console.
     * 
     * @param array $args List of values to log
     */
    public static function log(...$args) {

        $str = '';
        foreach($args as $arg) {
            if (is_array($arg)) {

                $str .= self::array2string($arg);

            } else if (is_object($arg)) {

                ob_flush();
                ob_clean();
                ob_start();
                var_dump($arg);
                $str .= ob_get_clean();

            } else if (is_bool($arg)) {
                $str .= $arg ? 'true ' : 'false ';

            } else if (!is_scalar($arg)) {
                $str .= 'Object('. get_class($arg) .')';
            } else {
                $str .= e($arg) . ' ';
            }

        }
        self::$logs[] = [
            'message' => $str,
            'backtrace' => debug_backtrace()
        ];

    }

    /**
     * Convert array into string.
     * 
     * @param array $arr
     * 
     * @return string
     */
    private static function array2string(array $arr) : string {
        $str = '[';
        $count = 0;
        foreach($arr as $k => $v) {
            $str .= "[$k] = ";

            if (is_array($v)) {
                $str .= self::array2string($v);
            } else if (is_string($v)) {
                $str .= "'$v'";
            } else if (!is_scalar($v)) {
                $str .= 'Object(' . get_class($v) . ')';
            } else {
                $str .= $v;
            }

            ++ $count;

            if ($count < sizeof($arr)) {
                $str .= ', ';
            }
        }
        $str .= ']';

        return $str;
    }

    /**
    * Clears the console logs.
    */
    public static function clear() {
        self::$logs = [];
    }

    /**
     * Prints the console HTML (managed by framework)
     */
    public static function print() {
        ?>
            <div class='debug-console' style='display: none'>
            <style>
            .debug-console {
                position: fixed;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                z-index: 9999;
                background: rgba(0,0,0, .7);
                padding: 30px;
                color: white;
                overflow-y: scroll;
            }
            .debug-console .debug-console-line {
                color: white;
                margin-bottom: 10px;
            }
            .debug-console .debug-console-message:hover {
                cursor: pointer;
                font-weight: bold;
            }
            </style>
                <div class='debug-console-line' style='margin-bottom: 20px; font-weight: bold'>Debug Console</div>
            <?php
            if (!empty(DebugConsole::$logs)) {
                foreach(DebugConsole::$logs as $log) {
                    echo '<div class="debug-console-line">';
                        echo '<div class="debug-console-message">&gt; ' . $log['message'] . '</div>';
                        echo '<div class="debug-console-backtrace" style="display:none"><ul>';
                        foreach($log['backtrace'] as $trace) {
                            echo '<li>' . Path::toRelative($trace['file']) . '(' . $trace['line'] . ')</li>';
                        }
                        echo '</ul></div>';
                    echo '</div>';
                }
            }
            else {
                echo '<div class="debug-console-line">&gt; Nothing logged.</div>';
            }
            ?>

            <script>
            $(document).ready(function() {
                $('body').keyup(function(e) {
                    if (e.keyCode == <?php echo (detectOS() == 'windows' ? '220' : '192') ?>) { // º is 220 in Windows, 192 in Mac
                        $('.debug-console').fadeToggle(100);
                    }
                });
                $(".debug-console-message").click(function() {
                    $(this).parent().find('.debug-console-backtrace').slideToggle();
                });
            });
            </script>
            </div>
        <?php
    }

    /**
     * Display errors in the website.
     */
    public static function showErrors() {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);

        /* Error Level Constants:
        ; E_ALL             - All errors and warnings (includes E_STRICT as of PHP 5.4.0)
        ; E_ERROR           - fatal run-time errors
        ; E_RECOVERABLE_ERROR  - almost fatal run-time errors
        ; E_WARNING         - run-time warnings (non-fatal errors)
        ; E_PARSE           - compile-time parse errors
        ; E_NOTICE          - run-time notices (these are warnings which often result
        ;                     from a bug in your code, but it's possible that it was
        ;                     intentional (e.g., using an uninitialized variable and
        ;                     relying on the fact it is automatically initialized to an
        ;                     empty string)
        ; E_STRICT          - run-time notices, enable to have PHP suggest changes
        ;                     to your code which will ensure the best interoperability
        ;                     and forward compatibility of your code
        ; E_CORE_ERROR      - fatal errors that occur during PHP's initial startup
        ; E_CORE_WARNING    - warnings (non-fatal errors) that occur during PHP's
        ;                     initial startup
        ; E_COMPILE_ERROR   - fatal compile-time errors
        ; E_COMPILE_WARNING - compile-time warnings (non-fatal errors)
        ; E_USER_ERROR      - user-generated error message
        ; E_USER_WARNING    - user-generated warning message
        ; E_USER_NOTICE     - user-generated notice message
        ; E_DEPRECATED      - warn about code that will not work in future versions
        ;                     of PHP
        ; E_USER_DEPRECATED - user-generated deprecation warnings */

        error_reporting(E_ALL);
        //error_reporting(E_ERROR|E_RECOVERABLE_ERROR|E_PARSE|E_STRICT|E_CORE_ERROR|E_USER_ERROR);
    }

}