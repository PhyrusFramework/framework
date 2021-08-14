<?php

class JWT {

    /**
     * Key used to encode or decode tokens.
     * 
     * @var string
     */
    private string $key;

    /**
     * Time until the token expires.
     * 0 means never.
     * 
     * @var int
     */
    private int $age;

    /**
     * Algorithm used to encode and decode the token.
     * 
     * @var string
     */
    private string $algorithm;

    /**
     * Library supported algorithms
     * 
     * @var array
     */
    private array $supported_algs = [
        'ES384',
        'ES256',
        'HS256',
        'HS384',
        'HS512',
        'RS256',
        'RS384',
        'RS512',
        'EdDSA'
    ];

    public function __construct(string $key, int $age = 0, string $algo = 'HS256') {

        $this->key = $key;
        $this->age = $age;
        $this->algorithm = in_array($algo, $this->supported_algs) ? $algo : 'HS256';

    }

    /**
     * Set the time until the token expires.
     * 
     * @param int $age
     */
    public function setAge(int $age) {
        $this->age = $age;
    }

    /**
     * Generate a JWT instance.
     * 
     * @param string $key
     * @param int age.
     * 
     * @return JWT
     */
    public static function instance(string $key, int $age = 3600) : JWT {
        return new JWT($key, $age);
    }

    /**
     * Generate a JWT object using a RS256 Key file.
     * 
     * @param string $file
     * 
     * @return JWT
     */
    public static function fromKey(string $file) {
        if (!file_exists($file)) return null;

        $key = file_get_contents($file);
        return new JWT($key, 0, 'RS256');
    }

    /**
     * Encode data into a token
     * 
     * @param array $payload
     * 
     * @return string
     */
    public function encode($payload = []) : string {

        $data = [];
        if ($this->age > 0) {
            $data['exp'] = time() + $this->age;
        }

        foreach($payload as $k => $v) {
            $data[$k] = $v;
        }

        return \Firebase\JWT\JWT::encode($data, $this->key, $this->algorithm);

    }

    /**
     * Decode the token. Fails if expired or key is invalid.
     * 
     * @param string $token
     * 
     * @return array|false $payload
     */
    public function decode(string $token) {
        try {
            $payload = \Firebase\JWT\JWT::decode($token, $this->key, [$this->algorithm]);
            return $payload;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if the token is expired.
     * 
     * @param string $token
     * 
     * @return bool
     */
    public function isExpired(string $token) : bool {
        try {
            $payload = \Firebase\JWT\JWT::decode($token, $this->key, [$this->algorithm]);
            return false;
        } catch (Exception $e) {
            return strpos(get_class($e), 'ExpiredException') !== FALSE;
        }
    }

}