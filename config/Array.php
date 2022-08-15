<?php

/**
 * Generate Arr object from array.
 * 
 * @param array $array
 * 
 * @return Arr
 */
function arr($array) {
    if (is_array($array)) {
        return new Arr($array);
    }
    return $array;
}

class Arr extends ArrayObject {

    private $position;

    /**
     * Create instance of Arr class
     * 
     * @param array $array
     * 
     * @return Arr
     */
    public static function instance(array $array) : Arr {
        return new Arr($array);
    }

    /**
     * @param array $initialData [Default empty]
     */
    function __construct(array $initialData = []) {
        parent::__construct($initialData);
    }

    /**
     * Generate a copy of this Arr object.
     * 
     * @return Arr
     */
    public function copy() : Arr {
        $aux = [];
        foreach($this as $k => $v) {
            $aux[$k] = $v;
        }
        return new Arr($aux);
    }

    // Properties

    /**
     * Obtain array value using dot notation.
     * 
     * @param string $key path to value using dot notation.
     * @param mixed $def (default null) Default value if not exists.
     * 
     * @return mixed value
     */
    public function get(string $key, $def = null) {

        $parts = explode('.', $key);
        $last = $parts[sizeof($parts) - 1];

        $container = $this;
        for($i = 0; $i < sizeof($parts) - 1; ++$i) {
            $ind = $parts[$i];
            if (!isset($container[$ind])) {
                return $def;
            }

            $container = $container[$ind];
        }
        if ($container == null) return $def;
        if (!isset($container[$last])) return $def;

        return $container[$last];
    }

    /**
     * Set array value using dot notation.
     * 
     * @param string $key path to value using dot notation.
     * @param mixed $value
     */
    public function set(string $key, $value) {
        $parts = explode('.', $key);
        $last = $parts[sizeof($parts) - 1];

        $container = $this;
        for($i = 0; $i < sizeof($parts) - 1; ++$i) {
            if (!isset($container[$parts[$i]]))
                $container[$parts[$i]] = [];
            $container = &$container[$parts[$i]];
        }
        $container[$last] = $value;
    }

    /**
     * Check if value exists in array
     * 
     * @param string $key
     * @param bool $notEmpty Also check that value is not empty.
     * 
     * @return bool has value
     */
    public function has(string $key, bool $notEmpty = false) : bool {
        return $notEmpty ? !empty($this[$key]) : array_key_exists($key, $this->getArray());
    }

    /**
     * Checks if array is empty
     * 
     * @return bool empty
     */
    public function isEmpty() : bool {
        return sizeof($this) == 0;
    }

    /**
     * Size of array
     * 
     * @return int size
     */
    public function size() : int {
        return sizeof($this);
    }

    /**
     * First element of the array
     * 
     * @return mixed
     */
    public function first() {
        if (sizeof($this) < 1) return null;
        $keys = array_keys($this);
        return $this[$keys[0]];
    }

    /**
     * Last element of the array
     * 
     * @param int $n Position from end
     * 
     * @return mixed
     */
    public function last(int $n = 0) {
        $keys = array_keys($this);
        if (sizeof($keys) <= $n) return null;
        return $this[$keys[sizeof($keys) - 1 - $n]];
    }

    /**
     * Get keys of the array.
     * 
     * @return string[]
     */
    public function keys() : array {
        return array_keys($this->getArrayCopy());
    }

    /**
     * Convert Arr object to array.
     * 
     * @return array
     */
    public function getArray() : array {
        return $this->getArrayCopy();
    }

    /**
     * Get a random item of the array.
     * 
     * @return mixed
     */
    public function random() {
        return $this[array_rand($this)];
    }

    /**
     * Iterator using a callable.
     * Normal foreach iterator can also be used.
     * 
     * @param callable $func($key, $value)
     */
    public function foreach(callable $func) {
        foreach($this as $k => $v) {
            $func($k, $v);
        }
    }

    /**
     * Is this key the last element of the array?
     * 
     * @param mixed $key
     * 
     * @return bool
     */
    public function isLast($key) : bool {
        $keys = array_keys($this);
        if (sizeof($keys) == 0) return false;
        if ($keys[sizeof($keys) - 1] == $key) return true;
        return false;
    }

    /**
     * Is array associative?
     * 
     * @return bool associative
     */
    public function isAssoc() : bool {
        return sizeof($this) > 0 && ($this->keys() !== range(0, count($this) - 1));
    }

    /**
     * Does array contain this value?
     * 
     * @param mixed $value
     * 
     * @return bool
     */
    public function contains($value) : bool {
        return in_array($value, $this);
    }

    // Methods

    /**
     * Force array to adapt a structure with default values.
     * 
     * @param array $defaults Example array with default values
     * 
     * @return Arr self
     */
    public function force($defaults) : Arr {

        $arr = $defaults;
        if (gettype($arr) == 'Arr') {
            $arr = $defaults->getArray();
        }

        $keys = array_keys($arr);
        foreach($this as $k => $v) {
            if (!in_array($k, $keys)) {
                unset($this[$k]);
            }
        }

        foreach($arr as $k => $v)
        {
            if (!isset($this[$k])) {
                $this[$k] = $arr[$k];
            }
        }
        return $this;
    }

    /**
     * Merge with another array. Accepts both array and Arr objects.
     * 
     * @param mixed $other Arr or array
     * @param bool $mergeNoAssoc (Default false) No associative arrays are overwritten (false) or merged (true)?
     * 
     * @return Arr self
     */
    public function merge($other, bool $mergeNoAssoc = false) : Arr {

        if (!is_array($other)) {
            return $this->merge($other->getArray(), $mergeNoAssoc);
        }

        $arr2 = $other;

        $merged = $this;
        $container = &$merged;
        foreach($arr2 as $k => $v) {
            if (!isset($container[$k])) {
                $container[$k] = $v;
                continue;
            }
            if (!is_array($container[$k]) || !is_array($v)) {
                $container[$k] = $v;
                continue;
            }
    
            $assocA = arr($container[$k])->isAssoc();
            $assocB = arr($v)->isAssoc();
    
            if ($assocA && $assocB)
                $container[$k] = arr($container[$k])->merge($v, $mergeNoAssoc);
            else if (!$assocA && !$assocB) {
                if ($mergeNoAssoc) {
                    $container[$k] = array_merge($container[$k], $v);
                }
                else
                    $container[$k] = $v;
            } else {
                $container[$k] = $v;
            }
    
        }
    
        return $this;

    }

    /**
     * Delete key from array.
     * 
     * @param mixed $key
     * 
     * @return Arr self
     */
    public function delete($key) : Arr {
        if (isset($this[$key]))
            unset($this[$key]);

        return $this;
    }

    /**
     * Compare two arrays to determine if they are equal.
     * Accepts both array and Arr objects.
     * 
     * @param mixed $other, Arr or array.
     * 
     * @return bool
     */
    public function equalTo($other) : bool {
        if (!is_array($other)) {
            return $this->equalTo($other->getArray());
        }

        $a = $this;
        $b = $other;

        if (sizeof($a) != sizeof($b)) return false;

        foreach($a as $k => $v) {
            if (!isset($b[$k])) return false;
        
            if (is_array($v)) {
                if (!is_array($b[$k])) return false;

                if (! arr($v)->equalTo($b[$k]))
                    return false;
            }
            else if ($v != $b[$k]) return false;
        }

        return true;
    }

    /**
     * Reverse the order of the items in the array.
     * 
     * @return Arr self
     */
    public function reverse() : Arr {
        $keys = [];
        $values = [];
        foreach($this as $k => $v) {
            $keys[] = $k;
            $values[] = $v;
            unset($this[$k]);
        }

        for($i = sizeof($keys) - 1; $i >= 0; --$i) {
            $this[$keys[$i]] = $values[$i];
        }

        return $this;
    }

    /**
     * Print array in a human readable format.
     * 
     * @param bool $html (Default true) Use HTML format?
     * @param int $level=0 Used for recursivity. Ignore.
     * 
     * @return Arr self
     */
    public function print(bool $html = true, int $level = 0) : Arr {
        
        $arr = $this;

        $br = $html ? '<br>' : "\n";
        $sp = $html ? '&nbsp;&nbsp;&nbsp;' : '   ';

        foreach($arr as $k => $v) {
            for($i = 0; $i<$level; ++$i) {
                echo $sp;
            }
            echo "$k = ";

            if (is_array($v)) {

                $arrv = arr($v);

                if (!$arrv->isAssoc()) {

                    if (!$arrv->isEmpty()) {

                        echo '[';

                        $arrv->foreach(function($key, $value) use($arrv, $html, $level, $sp, $br) {

                            if (is_string($value)) {
                                echo "'$value'";
                            }

                            else if (is_bool($value)) {
                                echo $value ? 'true' : 'false';
                            }

                            else if (is_array($value)) {

                                $arr = arr($value);
                                $assoc = $arr->isAssoc();
                                $b = $assoc ? '{' : '[';

                                echo $br;
                                forn($level+1, function($index) use($sp) {
                                    echo $sp;
                                });

                                echo "$b $br";

                                arr($value)->print($html, $level + 2);

                                forn($level + 1, function($index) use($sp) {
                                    echo $sp;
                                });

                                echo "$b $br";

                            } else echo $value;

                        });

                        forn($level, function() use($sp) {
                            echo $sp;
                        });

                        echo "]$sp";
                    } else {
                        echo "{ }$sp";
                    }

                } else {

                    if (!$arrv->isEmpty()) {
                        echo "{ $br";
                        $arrv->print($html, $level + 1);
                        forn($level, function() use($sp) {
                            echo $sp;
                        });
                        echo "}$br";

                    } else {
                        echo "{ }$br";
                    }

                }

            } else if (is_bool($v)) {

                echo $v ? 'true' : 'false';
                echo $br;

            } else if (is_string($v)) {
                echo "'$v'$br";

            } else {
                echo "$v $br";
            }
        }

        return $this;
    }

    /**
     * Map an array to another array by converting each item
     * 
     * @param callable
     * 
     * @return array
     */
    public function map(callable $forItem) : array {
        $list = [];
        foreach($this as $i) {
            $list[] = $forItem($i);
        }
        return $list;
    }

}