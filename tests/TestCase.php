<?php

class TestCase {

    /**
     * Web route to load.
     * 
     * @var string $path
     */
    private string $path;

    /**
     * Request response
     * 
     * @var array $report
     */
    private array $report;

    /**
     * Create a TestCase.
     * 
     * @param string $path
     * @param array $options [Default empty]
     * @param string $baseURL [Default current host]
     */
    function __construct(string $path, array $options = []) {
        $p = $path;
        if (strlen($p) < 1 || $p[0] != '/')
            $p = "/$p";

        $url = (isset($options['host']) ? $options['host'] : URL::host()) 
            . (isset($options['port']) ? ':' . $options['port'] : '') . $p;
        $this->path = $p;

        $method = $options['method'] ?? 'GET';

        $ops = arr($options)->force([
            'url' => $url,
            'method' => $method,
            'data' => $options['data'] ?? [],
            'headers' => $options['headers'] ?? [],
            'format' => $options['format'] ?? 'json',
            'curl' => [],
            'info' => [],
            'content-type' => $options['content-type'] ? $options['content-type'] : 'application/json',
            'decode' => $options['decode'] ?? 'json',
            'auth' => '',
            'report' => true,
            'timeout' => $options['timeout'] ?? 30,
            'user-agent' => $options['user-agent'] ?? ''
        ])->getArray();
        
        http::request($ops)
        ->finally(function($response) {
            $this->response = $response;
        });
    }

    /**
     * Get the response code.
     * 
     * @return int
     */
    public function getCode() : int {
        return $this->response['code'];
    }

    /**
     * Check the response code.
     * 
     * @param int $num
     * 
     * @return bool
     */
    public function isCode(int $num) : bool {

        return $this->response['code'] == $num;
    }

    /**
     * Get the response message.
     * 
     * @return mixed
     */
    public function getResponse() {
        return $this->response['response'];
    }

    /**
     * Get response message as plain test.
     * 
     * @return string
     */
    public function getText() : string {
        return strip_tags($this->getResponse());
    }

}