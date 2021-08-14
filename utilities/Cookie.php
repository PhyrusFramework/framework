<?php

class Cookie
{
    /**
     * Cookie expiration time in days [Default 365].
     * 
     * @var float $expiration
     */
    public static float $expiration = 365;

    /**
     * Set the value of a cookie.
     * 
     * @param string $key
     * @param string $value
     * @param float $time [Default static $expiration]
     */
    public static function set(string $key, string $value, float $time = null)
    {
        $e = $time == null ? self::$expiration : $time;

        $_COOKIE[$key] = $value;
        setcookie($key, $value, floor(time() + (86400 * $e)), '/');
    }

    /**
     * Checks if cookie exists.
     * 
     * @param string $key
     * 
     * @return bool
     */
    public static function has(string $key) : bool {

        if (isset($_COOKIE[$key])) {
            return true;
        }

        return false;
    }

    /**
     * Get the value of a cookie
     * 
     * @param string $key
     * @param mixed $default
     * 
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        if (isset($_COOKIE[$key])) {
            return $_COOKIE[$key];
        }

        return $default;
    }

    /**
     * Destroy a cookie.
     * 
     * @param string $key
     */
    public static function destroy($key)
    {
        if (isset($_COOKIE[$key])) {
            setcookie($key, null, -1, '/');
        }
    }
}

class SESSION
{
    /**
     * Get the current session ID.
     * 
     * @return string
     */
    public static function ID() : string {
        return session_id();
    }

    /**
     * Is the session started?
     * 
     * @return bool
     */
    public static function isStarted() : bool {
        return session_status() == PHP_SESSION_ACTIVE;
    }

    /**
     * Start the session if is not started.
     */
    public static function start() {
        if (!self::isStarted())
            session_start();
    }

    /**
     * Set a session value.
     * 
     * @param string $key
     * @param mixed $value
     */
    public static function set(string $key, $value)
    {
        self::start();

        $_SESSION[$key] = $value;
    }

    /**
     * Does this value exist in the session?
     * 
     * @return bool
     */
    public static function has(string $key) : bool {

        self::start();

        if (isset($_SESSION[$key]))
            return true;

        return false;
    }

    /**
     * Get session value
     * 
     * @param string $key
     * @param mixed $default
     */
    public static function get(string $key, $default = null) {

        self::start();

        if (isset($_SESSION[$key]))
            return $_SESSION[$key];

        return $default;
    }

    /**
     * Destroy a session value.
     * 
     * @param string $key
     */
    public static function destroy(string $key = null) {

        if ($key == null) {
            session_destroy();
            return;
        }

        self::start();
        unset($_SESSION[self::$prefix . $key]);
    }

    /**
     * Status of the session
     * 
     * @return string active|disabled|none
     */
    public static function status() : string {

        $status = session_status();

        if ($status == PHP_SESSION_ACTIVE)
            return "active";
        if ($status == PHP_SESSION_DISABLED)
            return "disabled";

        return "none";

    }
}
