<?php

class http {

    /**
     * [Managed by framework] Prepare array of headers for request.
     * 
     * @param array $headers
     * 
     * @return array
     */
    private static function prepareHeaders(array $headers) : array {
        $aux = [];
        foreach($headers as $k => $v) {
            $aux[] = "$k:$v";
        }
        return $aux;
    }

    /**
     * Make a request.
     * 
     * @param array $options Request settings: url, method, headers, etc.
     * @param callable $onSuccess
     * @param callable $onError
     * 
     * @param Promise
     */
    public static function request(array $options = []) : Promise {

        return new Promise(function($resolve, $reject) use ($options) {

            $ops = arr($options)->force([
                'url' => '',
                'method' => 'GET',
                'data' => [],
                'headers' => [],
                'format' => 'json',
                'curl' => [],
                'info' => [],
                'content-type' => 'application/json',
                'auth' => '',
                'decode' => 'json',
                'report' => false,
                'timeout' => 30,
                'user-agent' => ''
            ]);

            foreach($ops as $k => $v) {
                ${$k} = $v;
            }

            $dta = null;
            if (!empty($data)) {

                if ($method == 'GET' || $method == 'DELETE') {

                    $url = $url . (strpos($url, '?') ? '&' :  '?') . http_build_query($data);

                } else {
                    if ($format == 'json') {
                        $dta = json_encode($data);
                    } else if ($format == 'url') {
                        $dta = urlencode($data);
                    } else if ($format == 'http') {
                        $dta = http_build_query($data);
                    } else {
                        $dta = $data;
                    }
                }

            }
        
            // prepare headers
            $hds = [];
            $hds['Content-Type'] = $ops['content-type'];
            if (!empty($ops['auth'])) {
                $hds['Authorization'] = $ops['auth'];
            }
            foreach($headers as $k => $h)
                    $hds[$k] = $h;

            $hds = http::prepareHeaders($hds);
        
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);

            if ($method == 'POST')
                curl_setopt($ch, CURLOPT_POST, true);
            else if ($method == 'PUT')
                curl_setopt($ch, CURLOPT_PUT, true);
            else if ($method != 'GET')
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

            curl_setopt($ch, CURLOPT_HTTPHEADER, $hds);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_USERAGENT , $ops['user-agent'] );
            if ($dta != null)
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dta);

            foreach($ops['curl'] as $k => $v) {
                curl_setopt($ch, $k, $v);
            }

            $result = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($ops['decode'] == 'json') {
                $result = json_decode($result, true);
            } else if ($ops['decode'] == 'xml') {
                $result = simplexml_load_string($result);
            }

            if (!$ops['report']) {
                curl_close($ch);
                if ($code >= 200 && $code < 300) {

                    $resolve($result);
                    return;

                } else {

                    $reject([
                        'code' => $code,
                        'error' => $result
                    ]);
                    return;

                }
            }

            $total_time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
            $dns_time = curl_getinfo($ch, CURLINFO_NAMELOOKUP_TIME);
            $connecting_time = curl_getinfo($ch, CURLINFO_CONNECT_TIME);
            $redirecting = curl_getinfo($ch, CURLINFO_REDIRECT_TIME);
            $redirects = curl_getinfo($ch, CURLINFO_REDIRECT_COUNT);
        
            $infos = [];
            if (isset($ops['info'])) {
                foreach($ops['info'] as $inf) {
                    $infos[$inf] = curl_getinfo($ch, constant('CURLINFO_' . strtoupper($inf)));
                }
            }

            $curlerr = curl_error($ch);
            curl_close($ch);
        
            $resp = [
                'url' => $url,
                'method' => $method,
                'response' => $result,
                'code' => $code,
                'time' => [
                    'dns' => $dns_time,
                    'connecting' => $connecting_time,
                    'redirecting' => $redirecting,
                    'total' => $total_time
                ],
                'redirects' => $redirects,
                'error' => isset($_SERVER['HTTP_HOST']) ? $curlerr : 'Only available in browser'
            ];

            if (sizeof($infos) > 0) {
                foreach($infos as $k => $v) {
                    $resp[$k] = $v;
                }
            }

            if ($code >= 200 && $code < 300) {
                $resolve($resp);
            } else {
                $reject($resp);
            }

        });
    }

    /**
     * Make a POST request.
     * 
     * @param string $url
     * @param array $options
     * 
     * @return Promise
     */
    public static function post(string $url, array $options = []) : Promise {
        $ops = $options;
        $ops['url'] = $url;
        $ops['method'] = 'POST';

        return http::request($ops);
    }

    /**
     * Make a GET request.
     * 
     * @param string $url
     * @param array $options
     * 
     * @return Promise
     */
    public static function get(string $url, array $options = []) : Promise {

        $ops = $options;
        $ops['url'] = $url;

        return http::request($ops);
    }

    /**
     * Make a PUT request.
     * 
     * @param string $url
     * @param array $options
     * 
     * @return Promise
     */
    public static function put(string $url, array $options = []) : Promise {
        $ops = $options;
        $ops['url'] = $url;
        $ops['method'] = 'PUT';

        return http::request($ops);
    }

    /**
     * Make a PATCH request.
     * 
     * @param string $url
     * @param array $options
     * 
     * @return Promise
     */
    public static function patch(string $url, array $options = []) : Promise {
        $ops = $options;
        $ops['url'] = $url;
        $ops['method'] = 'PATCH';

        return http::request($ops);
    }

    /**
     * Make a DELETE request.
     * 
     * @param string $url
     * @param array $options
     * 
     * @return Promise
     */
    public static function delete(string $url, array $options = []) : Promise {
        $ops = $options;
        $ops['url'] = $url;
        $ops['method'] = 'DELETE';

        return http::request($ops);
    }

    /**
     * Make a request with a custom method.
     * 
     * @param string $method
     * @param string $url
     * @param array $options
     * 
     * @return Promise
     */
    public static function req(string $method, string $url, array $options = []) : Promise {
        $ops = $options;
        $ops['url'] = $url;
        $ops['method'] = $method;

        return http::request($ops);
    }

    /**
     * Download a file with the possibility of using headers and other http options.
     * 
     * @param string $url
     * @param string $filename
     * @param array $options
     * @param callable $onError
     * 
     */
    public static function download(string $url, string $filename, array $options = [], callable $onError = null) {
        $ops = $options;
        $ops['url'] = $url;
        $ops['report'] = false;
        $ops['method'] = 'GET';
        
        $curl = isset($options['curl']) ? $options['curl'] : [];
        $curl[CURLOPT_SSL_VERIFYPEER] = true;
        $ops['curl'] = $curl;

        http::request($ops, function($result, $code) use($filename) {
            file_put_contents($filename, $result);
        }, $onError);
    }

    /**
     * Upload a file with the possibility of using headers and other http options.
     * 
     * @param string $filename
     * @param string $url
     * @param array $options
     * @param callable $onError
     * 
     * @return Promise
     */
    public static function upload(string $filename, string $url, array $options = []) : Promise {

        return new Promise(function($resolve, $reject) {

            if (!file_exists($filename) && is_file($filename)) {
                $reject('Files does not exist.');
                return;
            }
    
            $ops = $options;
            $ops['url'] = $url;
            $ops['report'] = false;
            $ops['method'] = 'POST';
    
            $data = isset($options['data']) ? $options['data'] : [];
            $name = isset($options['filename']) ? $options['filename'] : 'file';
    
            $data[$name] = file_get_contents($filename);
            $ops['data'] = $data;
            
            $curl = isset($options['curl']) ? $options['curl'] : [];
            $curl[CURLOPT_SSL_VERIFYPEER] = true;
            $ops['curl'] = $curl;

            http::request($ops)
            ->then(function($result) use ($filename, $resolve) {
                file_put_contents($filename, $result);
                $resolve();
            })
            ->catch(function($error) use ($reject) {
                $reject($error);
            });

        });
    }


}