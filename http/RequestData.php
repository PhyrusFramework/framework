<?php

class RequestData {

    /**
     * HTTP Headers
     * 
     * @var HTTPHeaders
     */
    public HTTPHeaders $headers;

    /**
     * Data as array
     * 
     * @var array Data
     */
    private array $_arr = [];

    /**
     * Get data as array
     * 
     * @return array
     */
    public function getData() : array {
        return $this->_arr;
    }

    /**
     * Get an instance object of RequestData.
     * 
     * @return RequestData
     */
    public static function instance(bool $urlParams = false) : RequestData {
        return new RequestData($urlParams);
    }

    /**
     * @param bool $urlParams Accept also parameters in the URL?
     */
    function __construct(bool $urlParams = false) {

        // Headers
        $this->headers = new HTTPHeaders();

        // type
        $this->{'_type'} = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // data
        $postdata = file_get_contents('php://input');
        $arr = json_decode($postdata, true);
        if (!is_array($arr)) $arr = [];

        foreach($arr as $k => $v) {
            if ($v == 'null' || $v == 'undefined') continue;
            $this->{$k} = $v;
        }

        foreach($_POST as $k => $v) {
            if ($v == 'null' || $v == 'undefined') continue;
            $this->{$k} = $v;
            $arr[$k] = $v;
        }

        if ($urlParams) {
            $q = URL::parameters();
            foreach($q as $k => $v) {
                if ($v == 'null' || $v == 'undefined') continue;
                $this->{$k} = $v;
                $arr[$k] = $v;
            }
        }

        $this->_arr = $arr;
    }

    /**
     * Has this value been received?
     * 
     * @param string $name
     * 
     * @return bool
     */
    function has(string $name) : bool {
        return isset($this->{$name});
    }

    /**
     * Secure a value against XSS attacks
     * 
     * @param string $name
     * 
     * @return mixed
     */
    function secure(string $name) {
        if (!$this->has($name)) return null;

        $v = $this->$name;

        if (is_string($v)) {
            return str_replace('\&#039;', "'", htmlspecialchars($v, ENT_QUOTES, 'UTF-8'));
        }

        return $v;
    }

    /**
     * If a value was not received, return a 400 error.
     * 
     * @param array ...$args
     */
    function require(...$args) {

        $dev = Config::get('project.development_mode');

        foreach($args as $v) {
            if (!$this->has($v)) {
                if ($dev) {
                    ApiResponse::badRequest("Field '$v' is missing.");
                } else {
                    response_die('bad');
                }
            }
        }
    }

    /**
     * Get the request method.
     * 
     * @return string
     */
    function method() : string {
        return isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
    }

    /**
     * Check if the method is the one you want.
     * 
     * @param string $method GET, POST, DELETE, PUT, PATCH
     * 
     * @return bool
     */
    function isMethod(string $method) : bool {
        return $_SERVER['REQUEST_METHOD'] == $method;
    }

    /**
     * If the method is not one of these, return a 401 error.
     * 
     * @param array ...$args
     */
    function requireMethod(...$args) {
        $m = $_SERVER['REQUEST_METHOD'];
        foreach($args as $v) {
            if ($m == $v) {
                return;
            }
        }
        response('method-not-allowed');
        die();
    }

    /**
     * Require having received certain values and convert them to a specific format.
     * Formats: number, string or boolean.
     * 
     * If the value was not received, error 400 is sent.
     * 
     * @param array[string, string] $formats
     */
    function requireFormats(array $formats) {
        if (!is_array($formats)) return;
        foreach($formats as $k => $f) {
            $this->requireFormat($k, $f);
        }
    }

    /**
     * Require a single value and if received,
     * convert it to a specific format.
     * Formats: number, string or boolean.
     * 
     * @param string $val
     * @param string $format
     */
    function requireFormat(string $val, string $format) {

        if (!$this->has($val)) {
            response_die('bad');
        }
        $v = $this->{$val};

        if ($format == 'number') {

            if (is_int($v)) return;
            if (is_float($v)) return;
            if (is_numeric($v)) return;
            $this->{$val} = floatval($v);

        } else if ($format == 'string') {

            if (is_string($v)) return;
            $this->{$val} = "$val";

        } else if ($format == 'boolean') {
            if (is_bool($v)) return;

            if ($v == 1 || $v == '1' || $v == 'true') {
                $this->{$val} = true;
                return;
            }
            if ($v == 0 || $v == '0' || $v == 'false') {
                $this->{$val} = false;
                return;
            }

            response_die('bad');
        }

    }

    /**
     * Has received at least one file.
     * 
     * @return bool
     */
    function hasFiles() : bool {
        return sizeof($_FILES) > 0;
    }

    /**
     * Has this file been received.
     * 
     * @param string $name
     * 
     * @return bool
     */
    function hasFile(string $name) : bool {
        $b = sizeof($_FILES) == 0 || empty($_FILES[$name]);
        return !$b;
    }

    /**
     * Require file.
     * 
     * @param string $name
     * @param string Accepted mime
     * 
     * @return Generic
     */
    function requireFile(string $name, $mime = null) : Generic {
        if (!$this->hasFile($name))
            response_die('bad');
        
        if ($mime != null && strpos($mime, '/') !== FALSE) {

            $file = $this->getFile($name);
            $fileMime = $file->type ? $file->type : mime_content_type($file->name);

            $parts = explode('/', $mime);
            if ($parts[0] != '*') {

                if ($parts[1] != '*') {
                    if ($mime != $fileMime) {
                        response_die('unsupported-media-type');
                    }
                } else {
                    $fileParts = explode('/', $fileMime);
                    if ($parts[0] != $fileParts[0]) {
                        response_die('unsupported-media-type');
                    }
                }
            }
        }

        return $this->getFile($name);
    }

    /**
     * Get a file.
     * 
     * @param string $name
     * 
     * @return Generic
     */
    function getFile(string $name) : ?Generic {
        if (!isset($_FILES[$name])) return null;

        $file = $_FILES[$name];

        $parts = explode('.', $file['name']);
        $ext = '';
        if (sizeof($parts) > 1)
            $ext = $parts[1];

        return new Generic(array(
            'tmp' => $file['tmp_name'],
            'name' => $file['name'],
            'type' => $file['type'],
            'extension' => strtolower($ext)
        ));
    }

    /**
     * Get User-Agent header.
     * 
     * @return string
     */
    function userAgent() : string {
        return $_SERVER['HTTP_USER_AGENT']??'';
    }
}

class HTTPHeaders {

    public function __get($name) {
        if (!empty($_SERVER[$name])) return $_SERVER[$name];
        $n = strtoupper($name);
        $n = str_replace('-', '_', $n);
        $n = "HTTP_$n";
        if (!empty($_SERVER[$n])) return $_SERVER[$n];
        return '';
    }

    /**
     * Check if Request has header
     * 
     * @param string $name
     * 
     * @return bool
     */
    public function has(string $name) : bool {
        $h = $this->{$name};
        return !empty($h);
    }

    /**
     * Get Authorization header
     * 
     * @return string
     */
    public function Auth() : string {
        $attempts = array('HTTP_AUTHORIZATION', 'Authorization', 'REDIRECT_HTTP_AUTHORIZATION');
        foreach($attempts as $h) {
            if (!empty($_SERVER[$h]))
                return $_SERVER[$h];
        }
        return '';
    }

    /**
     * Require headers for request
     * 
     * @param array
     */
    public function require(...$args) {

        $dev = Config::get('project.development_mode');

        foreach($args as $v) {
            if (!$this->has($v)) {
                if ($dev) {
                    ApiResponse::badRequest("Header '$v' is missing.");
                } else {
                    response_die('bad');
                }
            }
        }
    }

}