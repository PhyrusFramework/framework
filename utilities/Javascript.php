<?php

class Javascript {

    /**
     * Define variables in Javascript.
     * 
     * @param array $array
     */
    public static function define(array $array) {
    ?>
        <script>
<?php
        foreach($array as $k => $v) {
        
            if ($v === null) {
                echo "var $k = null;";
            }
            else if (is_string($v)) {
                $val = str_replace("'", "\\'", $v);
                $val = str_replace("\n", "\\n", $v);
                echo "var $k = '$val';\n";
            }
            else if (is_bool($v)) {
                if ($v)
                    echo "var $k = true;\n";
                else
                    echo "var $k = false;\n";
            }
            else if (is_array($v) || (is_object($v) && get_class($v) == 'Arr') ) {

                if (!is_array($v) && get_class($v) == 'Arr') {
                    $v = $v->getArray();
                }
        
                if (arr($v)->isAssoc()) {
                    echo "var $k = {\n";
                    self::define_subobject($v);
                    echo "};\n";
                } else {
                    echo "var $k = [\n";
                    for($index = 0; $index < sizeof($v); ++$index) {
                        self::define_value($v[$index]);
                        if ($index < sizeof($v) - 1)
                            echo ", ";
                    }
                    echo "];\n";
                }
            }
            else if ($v == null) {
                echo "var $k = null;\n";
            }
            else {
                echo "var $k = $v;\n";
            }
        }
        ?>
        </script>
    <?php
    }

    public static function define_value($value) {
        if (is_string($value)) {
            $val = str_replace("'", "\\'", $value);
            $val = str_replace("\n", "\\n", $val);
            echo "'$val'";
        } else if (is_bool($value)) {
            if ($value) echo "true";
            else echo "false";
        } else if (is_array($value)) {
    
            if (arr($value)->isAssoc()) {
    
                echo "{";
                    self::define_subobject($value);
                echo "}";
    
            } else {
    
                echo "[";
                for($index = 0; $index < sizeof($value); ++$index) {
                    self::define_value($value[$index]);
                    if ($index < sizeof($value) - 1)
                        echo ", ";
                }
                echo "]";
    
            }
    
        } else {
            echo $value;
        }
    }

    public static function define_subobject($array, $level = 1) {
        foreach($array as $k => $v) {

            for($i = 0; $i<$level; ++$i)
            echo "\t";
    
            if ($v === null) {
                echo "$k: null,\n";
            }
            else if (is_string($v)) {
                $val = str_replace("'", "\\'", $v);
                $val = str_replace("\n", "\\n", $val);
                echo "$k: '$val',\n";
            }
            else if (is_bool($v)) {
                if ($v)
                    echo "$k: true,\n";
                else
                    echo "$k: false,\n";
            }
            else if (is_array($v)) {

                echo "$k: ";

                if (arr($v)->isAssoc()) {
                    echo "{\n";
                        self::define_subobject($v, $level + 1);
        
                    for($i = 0; $i<$level; ++$i)
                    echo "\t";
                    echo "},\n";

                } else {
                    echo "[";
                    for($index = 0; $index < sizeof($v); ++$index) {
                        self::define_value($v[$index]);
                        if ($index < sizeof($v) - 1)
                            echo ", ";
                    }
                    echo "],\n";
                }

            }
            else {
                echo "$k: $v,\n";
            }
        }
    }

    /**
     * Log something in the browser console.
     */
    public static function log($message) {
        echo "<script>console.log('$message');</script>";
    }

    /**
     * Redirect the user to another URL.
     * 
     * @param string $url
     * @param bool $end Die after redirect
     * 
     */
    public static function redirect_to(string $url, bool $end = false) {
        ?>
        <script>
        location.href = "<?php echo $url; ?>";
        </script>
        <?php
        if ($end) {
            die();
        }
    }

}