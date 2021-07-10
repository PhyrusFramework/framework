<?php

/**
 * Displays a PHP file as a view.
 * 
 * @param string $file
 * @param mixed $parameters [Default empty] array or Arr object
 */
function view(string $file, $parameters = []) {
    if (!file_exists($file) || !is_file($file)) return;

    foreach($parameters as $k => $v) {
        ${$k} = $v;
    }
    return include(Template::process($file));    
}

class Template {

    /**
     * Custom filters.
     * 
     * @var array $filters
     */
    static array $filters = [];

    /**
     * Cached files extension.
     * 
     * @var string $extension
     */
    private static string $extension = 'cch';

    /**
     * Get the path to the processed version of the file.
     * 
     * @param string $file
     * 
     * @return string
     */
    public static function process(string $file) : string {

        $prc = self::has($file);
        if (!$prc) {
            $prc = self::generate($file);
        }

        return $prc;
    }

    /**
     * Analyzes the original file and generates the cached version.
     * 
     * @param string $original_file
     * 
     * @return string
     */
    public static function generate($original_file) : string {

        $code = self::analyze( File::instance($original_file)->content() );

        if (!file_exists($original_file)) return '';

        $file = str_replace('.php', '.'.self::$extension, $original_file);
        $f = File::instance($file);

        if (!$f->exists() || self::fileExpired($file)) {
            $f->write($code);
        }

        return $file;

    }

    /**
     * Checks if a cached file needs to be overwritten by a newer version.
     * 
     * @param string $filename
     * 
     * @return bool
     */
    private static function fileExpired(string $filename) : bool {
        if (!file_exists($filename)) return true;
        $php = str_replace('.' . self::$extension, '.php', $filename);

        $t1 = new Time(last_modification_date($php));
        $t2 = new Time(last_modification_date($filename));

        $f = File::instance($filename);

        if ($t2->isBefore($t1)) {
            $f->delete();
            return true;
        }

        return false;
    }

    /**
     * Checks if exists a cached version of this file.
     * 
     * @param string $filename
     * 
     * @return mixed
     */
    public static function has(string $filename) {
        $tmp = str_replace('.php', '.' . self::$extension, $filename);

        $expired = self::fileExpired($tmp);
        if (!$expired) return $tmp;
        return false;
    }

    /**
     * Checks if a file is a cached file.
     * 
     * @param string $filename
     * 
     * @return bool
     */
    public static function isTmp(string $filename) : bool {
        return strpos($filename, '.' . self::$extension) ? true : false;
    }

    /**
     * Analyzes a string to transform the filters into php.
     * 
     * @param string $text
     * 
     * @return string
     */
    public static function analyze(string $text) : string {
        $content = '';
        $in = false;
        $first = false;

        $intag = true;
        $current_tag = null;
        $current_content = '';
        for($i = 0; $i < strlen($text); ++$i) {

            $c = $text[$i];

            // If not in tag
            if (!$in) {

                // If not found first {
                if (!$first) {

                    // If finds { first = true, else add character to string
                    if ($c == '{')
                        $first = true;
                    else
                        $content .= $c;

                } 
                // If found { before
                else {

                    // If now is different to {, add the previous one + the current character
                    if ($c != '{') {
                        $content .= '{' . $c;
                    } 
                    // If found second { we are inside a tag
                    else {
                        $in = true;
                        $intag = true;
                    } 
                    $first = false;

                }

            } else { // If in a tag

                // If not found first }
                if (!$first) {

                    // If still in tag  {{HERE content}}
                    if ($intag){

                        if ($c == '}') {
                            $first = true;
                        }
                        // If found space, tag finished. Else add character to string
                        else if ($c != ' ') {
                            $current_content .= $c;
                        } else {
                            $current_tag = $current_content;
                            $current_content = '';
                            $intag = false;
                        }

                    } 
                    // If already in content  {{tag HERE}}
                    else {

                        // If not }, add character to string
                        if ($c != '}') {
                            $current_content .= $c;
                        } else {
                            $first = true;
                        }

                    }

                } else { // If found first }
                    
                    // If not }, add the previous one + current character
                    if ($c != '}') {
                        $first = false;
                        $current_content .= '}' . $c;
                    } 
                    // If second }, process the template tag, add that to text and get out of tag ($in = false)
                    else {
                        $in = false;
                        $first = false;
                        $content .= self::filter($current_tag, $current_content);
                        $current_tag = null;
                        $current_content = '';
                    }

                }

            }

        }

        return $content;
    }

    /**
     * Process a template filter.
     * 
     * @param ?string $t
     * @param string $c
     * 
     * @return string
     */
    private static function filter(?string $t, string $c) : string {

        $tag = $t;
        $content = $c;
        if ($tag == '' && !empty($content)) {
            $tag = $content;
            $content = '';
        }

        if (strlen($tag) > 0 && $tag[0] == '$') {
            return "<?php echo $tag $content; ?>";
        }

        $filters = [
            '' => "echo e($content);",
            'run' => "$content;",
            'img' => "echo Assets::image($content);",
            'size' => "echo sizeof($content);",
            'cmp' => "component($content);",
            'if' => "if ($content) { ",
            'foreach' => "foreach($content) { ",
            'for' => "for($content) { ",
            'forn' => 'for($index = 0; $index ' . $content . '; ++$index) { ',
            'else' => "} else { ",
            'else/' => "} else { $content; }",
            'while' => "while ($content) {",
            '/' => '}',
            'elseif' => "} else if ($content) { ",
            'func' => "function " . (strpos($content, "(") ? $content : "$content()") . " {",
            'js' => "?><script>document.write($content)</script><?php ",
            'content' => 'echo Controller::current->display()',
            'param' => 'echo Controller::current()->parameters->{'.$content.'}',
            't' => "echo Translate::use('user')->get($content);",
            'view' => "view($content)",
            'empty' => "if (empty($content)) { ",
            'notempty' => "if (!empty($content)) { ",
            '?' => "echo (!empty($content) ? $content : '');",
            'if?' => "if (!empty($content)) { "
        ];
    
        foreach($filters as $k => $v) {
            if ($tag == $k) {
                return "<?php $v ?>";
            }
        }
    
        // Check custom filters
        foreach(self::$filters as $k => $filter) {
            if ($k != $tag) continue;

            $ret = $filter($content, $tag);
    
            if ($ret == null || $ret == false) continue;
            return "<?php $ret ?>";
        }

        return "<?php echo $tag $content; ?>";
    }

    /**
     * Adds a custom filter.
     * 
     * @param string $key
     * @param callable $func
     */
    public static function addFilter(string $key, callable $func) {
        self::$filters[$key] = $func;
    }

}