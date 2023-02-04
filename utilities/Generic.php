<?php

class Generic extends stdClass {

    /**
     * [Managed by Framework] Array containing the actual values.
     * 
     * @var array $__definition
     */
    private array $__definition = [];

    private array $__methods = [];

    function __call($func, $params) {

        foreach($this->__methods as $k => $v) {
            if ($func == $k) {
                return $v(...$params);
            }
        }
        return null;
    }

    /**
     * Create a Generic instance.
     * 
     * @param array $array Initial values.
     * @param bool $recursive Turn sub-arrays into Generic objects.
     * 
     * @return Generic
     */
    public static function instance(array $array = [], $recursive = false) : Generic {
        return new Generic($array, $recursive);
    }

    /**
     * @param array $array Initial values.
     * @param bool $recursive Turn sub-arrays into Generic objects.
     */
    public function __construct(array $array = [], $recursive = false) {
        $this->__definition = $array;
        foreach($array as $k => $v) {

            if (is_callable($v)) {
                $this->__methods[$k] = $v;
                continue;
            }

            if (!is_array($v) || !$recursive)
                $this->{$k} = $v;
            else {

                if (!arr($v)->isAssoc()) {

                    function arrToGeneric($arr) {

                        $aux = [];

                        foreach($arr as $v) {

                            if (!is_array($v)) {
                                $aux[] = $v;
                            } else {
                                if (!arr($v)->isAssoc()) {
                                    $aux[] = arrToGeneric($v);
                                } else {
                                    $aux[] = new Generic($v, true);
                                }
                            }
    
                        }

                        return $aux;

                    }

                    $this->{$k} = arrToGeneric($v);

                } else {
                    $this->{$k} = new Generic($v, $recursive);
                }
 
            }

        }
    }

    public function __get($name) {
        if (isset($this->{$name})) {
            return $this->{$name};
        }
        return null;
    }

    /**
     * Set a value.
     * 
     * @param mixed $key
     * @param mixed $value
     * 
     * @return Generic
     */
    public function set($key, $value) : Generic {
        if (is_callable($value)) {
            $this->__methods[$key] = $value;
            return $this;
        }

        $this->__definition[$key] = $value;
        $this->{$key} = $value;
        return $this;
    }

    /**
     * Remove a value from the object.
     * 
     * @param mixed $key
     */
    public function remove($key) {
        if (!isset($this->__definition[$key])) return;

        $this->__definition[$key] = null;
        unset($this->__definition[$key]);
        $this->{$key} = null;
        unset($this->{$key});
    }

    /**
     * Check if property exists.
     * 
     * @param mixed $key
     * 
     * @return mixed
     */
    public function has($key) {
        return property_exists($this, $key);
    }

    /**
     * Check if property exists and the value is the desired one.
     * 
     * @param mixed $key
     * @param mixed $value
     * 
     * @return bool
     */
    public function hasAndIs($key, $value) : bool {
        if (!$this->has($key)) return false;
        return $this->$key === $value;
    }

    /**
     * Check if property exists and the value is not the passed one.
     * 
     * @param mixed $key
     * @param mixed $value
     * 
     * @return bool
     */
    public function hasAndIsNot($name, $value) : bool {
        if (!$this->has($name)) return false;
        return $this->$name !== $value;
    }

    /**
     * Convert this Generic object into an array.
     * 
     * @return array
     */
    public function toArray() : array {
        return $this->__definition;
    }

    /**
     * Determine whether the generic object is empty.
     * 
     * @return bool
     */
    public function isEmpty() : bool {
        return empty($this->__definition);
    }
}