<?php

class OpenSSL {

    private static $defaultIV = '0000000000000000';

    public static function encrypt($data, $key, $iv = null) : string {
        return openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv ? $iv : self::$defaultIV);
    }

    public static function decrypt($token, $key, $iv = null) {
        return openssl_decrypt($token, 'AES-256-CBC', $key, 0, $iv ? $iv : self::$defaultIV);
    }

}