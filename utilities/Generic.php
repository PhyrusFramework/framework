<?php

class Generic {

    /**
     * [Managed by Framework] Array containing the actual values.
     * 
     * @var array $__definition
     */
    private array $__definition = [];

    /**
     * @param array $array Initial values.
     */
    public function __construct($array = []) {
        $this->__definition = $array;
        foreach($array as $k => $v) {
            $this->{$k} = $v;
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
     */
    public function set($key, $value) {
        $this->__definition[$key] = $value;
        $this->{$key} = $value;
    }

    /**
     * Get a value or get a default value if not exists.
     * 
     * @param mixed $key
     * @param mixed $default [Default null
     * 
     * @return mixed
     */
    public function get($key, $default = null) { 
        return property_exists($this, $key) ? $this->$key : $default;
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
}