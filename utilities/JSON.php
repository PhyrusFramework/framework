<?php

class JSON {

    /**
     * Array object.
     * 
     * @var array $_object
     */
    private $_object;

    /**
     * Can use array or JSON string
     * 
     * @param mixed $body
     */
    function __construct($body = null) {
        $this->_object = [];

        if ($body != null) {

            if (is_string($body))
                $obj = json_decode($body, true);
            else
                $obj = $body;

            if (!is_array($obj)) return;
            $this->_object = $obj;

            foreach($obj as $k => $v) {
                $this->{$k} = $v;
            }
        }
    }

    /**
     * Get JSON Object from array or JSON String
     * 
     * @param mixed $body
     * 
     * @return JSON
     */
    public static function instance($body = null) : JSON {
        return new JSON($body);
    }

    /**
     * Property exists.
     * 
     * @param string $key
     * 
     * @return mixed
     */
    public function has(string $key) {
        return isset($this->{$key});
    }

    /**
     * Set property.
     * 
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, $value) {
        $this->{$key} = $value;
        $this->_object[$key] = $value;
    }

    /**
     * Remove object property
     * 
     * @param string $key
     */
    public function remove($key) {
        unset($this->{$key});
        unset($this->_object[$key]);
    }

    /** 
     * Get JSON string.
     * 
     * @param bool $pretty [Default false]
     * 
     * @return string
    */
    public function string(bool $pretty = false) : string {
        return self::stringify($this->_object, $pretty);
    }

    /**
     * Echo the JSON string.
     * 
     * @param array $array
     * @param bool $pretty [Default false]
     */
    public static function print(array $array, bool $pretty = false) {
        echo self::stringify($array, $pretty);
    }

    /**
     * Convert object or array to JSON string.
     * 
     * @param $obj
     * @param bool $pretty [Default false]
     * 
     * @return string
     */
    public static function stringify($obj, bool $pretty = false) : string {
        $flags = JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_NUMERIC_CHECK|JSON_HEX_QUOT;
        $json = json_encode($obj, $pretty ? $flags|JSON_PRETTY_PRINT : $flags);
        return str_replace("\u0022", "\\\\\"", $json);
    }

    /**
     * Convert json string to array
     * 
     * @param string $json string
     * 
     * @return array
     */
    public static function parse(string $json) : array {
        $json = new JSON($json);
        return $json->asArray();
    }

    /**
     * Convert to Array
     * 
     * @return array
     */
    public function asArray() : array {
        return $this->_object;
    }

    /**
     * Convert to Array. Same as ->asArray()
     * 
     * @return array
     */
    public function toArray() : array {
        return $this->asArray();
    }

    /**
     * Get as Object.
     * 
     * @return Generic
     */
    public function asObject() : Generic {
        return new Generic($this->_object);
    }

    /**
     * Get JSON object from file.
     * 
     * @param string $file
     * 
     * @return JSON
     */
    public static function fromFile(string $file) : JSON {
        if (!file_exists($file)) return new JSON();
        $content = file_get_contents($file);
        return new JSON($content);
    }

    /**
     * Save JSON file.
     * 
     * @param string $filename
     */
    public function saveTo(string $filename) {
        file_put_contents($filename, $this->string(true));
    }

    /**
     * Check if string is json
     * 
     * @param string $string
     * 
     * @return mixed false if is not JSON, decoded array if it is.
     */
    public static function isJSON(string $string) {
        $decoded = json_decode($string, true);
        if (json_last_error() != JSON_ERROR_NONE) return false;
        return $decoded;
    }

}