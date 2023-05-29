<?php

class URL {

    /**
     * Current URL
     * 
     * @return string
     */
    public static function current() : string {
        return URL::protocol() . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    /**
     * Protocol + host
     * 
     * @param string $url [Default current]
     * 
     * @return string
     */
    public static function host(string $url = null) : string {
        if ($url == null)
            return URL::protocol() . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost');

        $parts = parse_url($url);
        if (empty($parts['host']))
            return '';

        return URL::protocol($url) . $parts['host'];
    }

    /**
     * Get URL parameters (?a=x&b=x)
     * 
     * @param string $url [Default current]
     * 
     * @return array
     */
    public static function parameters(string $url = null) : array {
        if ($url == null)
            $u = $_SERVER['REQUEST_URI'];
        else
            $u = $url;
        $parts = parse_url($u);
        if (empty($parts['query']))
            return [];

        $parts = explode('&', $parts['query']);
        $query = [];
        foreach($parts as $p) {
            $kv = explode('=', $p);
            if (sizeof($kv) != 2) continue;
            $query[$kv[0]] = urldecode($kv[1]);
        }

        return $query;
    }

    /**
     * Get protocol http:// or https://
     * 
     * @param string $url [Default current]
     * 
     * @return string
     */
    public static function protocol(string $url = null) : string {

        if ($url != null) {
            $is = URL::isHttps($url);
            if ($is) return 'https://';
            return 'http://';
        }

        if (isset($_SERVER['HTTPS']) &&
            ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
            isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
            $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            return 'https://';
        }
        return 'http://';
    }

    /**
     * Check if URL uses SSL certificate (https)
     * 
     * @param string $url [Default current]
     * 
     * @return bool
     */
    public static function isHttps(string $url = null) : bool {
        if ($url == null)
            return URL::protocol() == 'https://';

        if (strlen($url) < 5) return false;
        $s = substr($url, 0, 5);
        return $s == 'https';
    }

    /**
     * Get host (without protocol)
     * 
     * @param string $url [Default current]
     * 
     * @return string
     */
    public static function hostname(string $url = null) : string {
        if ($url == null) return isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $parts = parse_url($url);
        return $parts['host'];
    }

    /**
     * Get the last URI part.
     * 
     * @param string $url [Default current]
     * 
     * @return string
     */
    public static function page(string $url = null) : string {
        if ($url == null)
            $u = $_SERVER['REQUEST_URI'];
        else
            $u = $url;
        $parts = parse_url($u);
        parse_str($parts['path'], $query);
    
        foreach($query as $k => $v)
            return substr($k, 1);
    }

    /**
     * Get the URI
     * 
     * @param string $url [Default current]
     * 
     * @return string
     */
    public static function uri(string $url = null) : string {
        if ($url == null)
            return $_SERVER['REQUEST_URI'];

        $u = $url;
        $parts = parse_url($u);
        $uri = $parts['path'];
        if (!empty($parts['query']))
            $uri = $uri . '?' . $parts['query'];

        return $uri;
    }

    /**
     * Get the URI without query.
     * 
     * @param string $url [Default current]
     * 
     * @return string
     */
    public static function route(string $url = null) : string {

        $u = $url;
        if ($u == null){
            $u = URL::current();
            if (strpos($u, '?')) {
                $u = explode('?', $u)[0];
            }
        }

        $parts = parse_url($u);
        $route = $parts['path'];

        if ($route[strlen($route) - 1] != '/') {
            $route .= '/';
        }
        return $route;
    }

    /**
     * Get the URL route as array
     * 
     * @param string $url [Default current]
     * 
     * @return array
     */
    public static function path(string $url = null) : array {
        if ($url == null)
            $page = URL::page();
        else
            $page = $url;
        $values = Text::instance($page)->split(['/', "\\"], false);

        $newv = [];
        foreach($values as $v)
        {
            if (!empty($v) || $v === '0')
                $newv[] = $v;
        }
        return $newv;
    }

}