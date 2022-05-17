<?php

if (function_exists('getallheaders'))
    getallheaders();
if (function_exists('apache_request_headers'))
    apache_request_headers();

// Solve CORS problem
if (!defined('USING_CLI')) {

    // Headers
    $headers = Config::get('web.headers', []);
    foreach($headers as $header => $value) {
        if ($header != 'content-security-policy')
            header("$header: $value");
    }

    if (isset($headers['content-security-policy'])) {
        $csp = $headers['content-security-policy'];

        $str = 'default-src ' . $csp['default'] . ';';
        $str .= 'img ' . $csp['img'] . ';';
        $str .= 'font ' . $csp['fonts'] . ';';
        $str .= 'frame-src ' . $csp['frames'] . ';';
        $str .= 'style-src-elem ' . $csp['styles'] . ';';
        $str .= 'script-src-elem ' . $csp['js'] . ';';

        header("content-security-policy: $str");
    }

    // CORS
    $origin = Config::get('web.CORS.origin', '*');

    header("Access-Control-Allow-Origin: $origin");

    if (isset($_SERVER['HTTP_ORIGIN'])) {
        if ($origin == '*') {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        }
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: ' . Config::get('web.CORS.age', 86400));    // cache for 1 day
    }

    // Access-Control headers are received during OPTIONS requests
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            header('Access-Control-Allow-Methods: ' . Config::get('web.CORS.methods', 'GET, POST, OPTIONS, PUT, DELETE, PATCH'));         

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
            if (Config::get('web.CORS.headers') == '*') {
                header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
            } else {
                header('Access-Control-Allow-Headers: ' . Config::get('web.CORS.headers'));
            }
        }

        exit(0);
    }
}

require_once(__DIR__ . '/RequestData.php');

function _get_http_responses() {

    return [
        'ok' => [
            200,
            'HTTP/1.0 200 OK',
            'Success'
        ],
        'created' => [
            201,
            'HTTP/1.0 201 Created',
            'New element created'
        ],
        'accepted' => [
            202,
            'HTTP/1.0 202 Accepted',
            'Request accepted but action not made yet.'
        ],
        'development' => [
            203,
            'HTTP/1.0 203 Non-Authoritative Information',
            'Request successfull but returned data is not the official yet, but a development or hardcoded version.'
        ],
        'no-content' => [
            204,
            'HTTP/1.0 204 No Content',
            'Successfull request without response body.'
        ],
        'reset-view' => [
            205,
            'HTTP/1.0 205 Reset Content',
            'Client has to reload the view after this request.'
        ],
        'partial-content' => [
            206,
            'HTTP/1.0 206 Partial Content',
            'The response is only a part of the whole object, for example in downloads transferences.'
        ],
        'multiple-choice' => [
            300,
            'HTTP/1.0 300 Multiple Choice',
            'This request has multiple possible responses, so the client cannot know which will receive.'
        ],
        'moved-permanently' => [
            301,
            'HTTP/1.0 301 Moved Permanently',
            'Redirection.'
        ],
        'found' => [
            302,
            'HTTP/1.0 302 Found',
            'URL is valid but it will change in the future.'
        ],
        'see-other' => [
            303,
            'HTTP/1.0 303 See Other',
            'The server requests the client to visit another URL instead of this one.'
        ],
        'not-modified' => [
            304,
            'HTTP/1.0 304 Not Modified',
            'Used for cache purposes. Indicates the client that the response does not change with evey request, so it can use its cached version.'
        ],
        'bad' => [
            400,
            'HTTP/1.0 400 Bad Request',
            'The request is not properly made.'
        ],
        'unauthorized' => [
            401,
            'HTTP/1.0 401 Unauthorized',
            'Authentication failed.'
        ],
        'forbidden' => [
            403,
            'HTTP/1.0 403 Forbidden',
            'Client has not access to this path.'
        ],
        'not-found' => [
            404,
            'HTTP/1.0 404 Not Found',
            'This path does not exist.'
        ],
        'method-not-allowed' => [
            405,
            'HTTP/1.0 405 Method Not Allowed',
            'The method used for this request is not allowed.'
        ],
        'not-acceptable' => [
            406,
            'HTTP/1.0 406 Not Acceptable',
            'After analyzing the request body, data does not comply with the expected format.'
        ],
        'proxy-unauthorized' => [
            407,
            'HTTP/1.0 407 Proxy Authentication Required',
            'Authorization must be made throgh a proxy.'
        ],
        'timeout' => [
            408,
            'HTTP/1.0 408 Request Timeout',
            'Request timed out.'
        ],
        'conflict' => [
            409,
            'HTTP/1.0 409 Conflict',
            'There is a conflict between the state of the server and what the client is requesting.'
        ],
        'gone' => [
            410,
            'HTTP/1.0 410 Gone',
            'The accessed resource was deleted.'
        ],
        'condition-failed' => [
            412,
            'HTTP/1.0 412 Precondition failed',
            'The server requires conditions (headers, data, etc) that the request does not fullfill.'
        ],
        'unsupported-media-type' => [
            415,
            'HTTP/1.0 415 Unsupported Media Type',
            'The file format is not accepted by the server.'
        ],
        'unprocessable' => [
            422,
            'HTTP/1.0 422 Unprocessable entity',
            'Request can be processed. This usually happens when Content-Type headers is not correct.'
        ],
        'error' => [
            500,
            'HTTP/1.0 500 Internal Server Error',
            'Internal Server Error'
        ],
        'not-implemented' => [
            501,
            'HTTP/1.0 501 Not implemented',
            'This functionality is not implemented yet.'
        ],
        'bad-gateway' => [
            502,
            'HTTP/1.0 502 Bad Gateway',
            'The request cannot be completed because the server obtained invalid data from another service.'
        ],
        'unavailable' => [
            503,
            'HTTP/1.0 503 Service Unavailable',
            'The requested path is not available right now, but should be fixed soon.'
        ],
        'gateway-timeout' => [
            504,
            'HTTP/1.0 504 Not implemented',
            'The requested cannot be completed because the server made another request that timed out.'
        ],
    ];

}

/**
 * Send a response header.
 * 
 * @param string|int $name
 */
function response($name, $message = null)
{
    if (defined('USING_CLI')) {
        return;
    }

    if (!empty($message)) {
        if (is_array($message)) {
            echo JSON::stringify($message);
        } else {
            echo $message;
        }
    }

    $responses = _get_http_responses();

    if (isset($responses[$name])) {
        header($responses[$name][1]);
        http_response_code($responses[$name][0]);
    }
    else if (is_string($name)) {

        header($name);

    } else if (is_numeric($name)) {

        http_response_code($name);

    } else {
        foreach($name as $code => $header) {
            header($header);
            http_response_code($code);
            return;
        }
    }
}

/**
 * Send a response header and end execution.
 * 
 * @param string $name
 * @param mixed $message
 */
function response_die(string $name, $message = null) {
    response($name, $message);
    die();
}