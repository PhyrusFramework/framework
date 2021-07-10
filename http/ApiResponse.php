<?php

class ApiResponse {

    /**
     * Request succeeded
     * 
     * @param mixed $data
     * @param array $extra Extra fields for the response
     */
    public static function success($data, $extra = []) {

        $response = [
            'code' => 200,
            'message' => 'Success',
            'data' => $data
        ];

        foreach($extra as $k => $v) {
            $response[$k] = $v;
        }

        response_die('ok', JSON::stringify($response));
    }

    /**
     * The request failed.
     * 
     * @param string $type
     * @param string $message Error information
     * @param mixed $data
     * @param array $extra fields for response
     */
    private static function failRequest(string $type, $message, $data = null, array $extra = []) {

        $responses = _get_http_responses();

        $name = isset($responses[$type]) ? $type : 'bad';

        $msg = $message == null ? $responses[$name][2] : $message;
        $code = $responses[$name][0];

        $response = [
            'code' => $code,
            'message' => $msg
        ];

        foreach($extra as $k => $v) {
            $response[$k] = $v;
        }

        if ($data != null) {
            $response['data'] = $data;
        }

        response_die($name, JSON::stringify($response));
    }

    /**
     * 400 Bad request
     * 
     * @param string $message
     * @param mixed $data
     * @param array $extra
     */
    public static function badRequest($message = null, $data = null, array $extra = []) {
        self::failRequest('bad', $message, $data, $extra);
    }

    /**
     * 403 Forbidden
     * 
     * @param string $message
     * @param mixed $data
     * @param array $extra
     */
    public static function forbidden($message = null, $data = null, array $extra = []) {
        self::failRequest('forbidden', $message, $data, $extra);
    }

    /**
     * 401 Unauthorized
     * 
     * @param string $message
     * @param mixed $data
     * @param array $extra
     */
    public static function notAuthorized($message = null, $data = null, array $extra = []) {
        self::failRequest('unauthorized', $message, $data, $extra);
    }

    /**
     * 404 Not found
     * 
     * @param string $message
     * @param mixed $data
     * @param array $extra
     */
    public static function notFound($message = null, $data = null, array $extra = []) {
        self::failRequest('not-found', $message, $data, $extra);
    }

    /**
     * 500 Error
     * 
     * @param string $message
     * @param mixed $data
     * @param array $extra
     */
    public static function error($message = null, $data = null, array $extra = []) {
        self::failRequest('error', $message, $data, $extra);
    }

    /**
     * 400 Bad request
     * 
     * @param string $message
     * @param mixed $data
     * @param array $extra
     */
    public static function fail($responseName, $message = null, $data = null, array $extra = []) {
        self::failRequest($responseName, $message, $data, $extra);
    }

    /**
     * Token expired response.
     * 
     * @param string $message?
     */
    public static function tokenExpired($message = null) {
        self::failRequest('unauthorized', $message ?? 'Token expired');
    }

    /**
     * Logged in response.
     * 
     * @param mixed $token
     * @param mixed $refreshToken?
     * @param array $extra?
     */
    public static function logged($token, $refreshToken = null, array $extra = []) {

        $data = ['token' => $token];
        if (!empty($refreshToken)) {
            $data['refreshToken'] = $refreshToken;
        }
        if (!empty($extra)) {
            foreach($extra as $k => $v) {
                $data[$k] = $v;
            }
        }

        self::success($data);

    }

}