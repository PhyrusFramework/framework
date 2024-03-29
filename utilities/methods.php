<?php

/**
 * Get the user IP
 */
function IP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }

    return $_SERVER['REMOTE_ADDR'];
}

/**
 * Get the path to the file that called the current function.
 * 
 * @return ?string
 */
function caller() : ?string {

    $backtrace = debug_backtrace();
    if (sizeof($backtrace) < 3) return null;

    $b = $backtrace[1];

    $str = $b['file'];
    
    if (isset($b['function'])) {
        $str .= ': ' . $b['function'] . '()';
    }
    
    $str .= ' (line ' . $backtrace[1]['line'] . ')';

}

/**
 * Generate an encrypted password.
 * 
 * @param string $password
 * 
 * @return string
 */
function generate_password(string $password) : string
{
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Get date as NOW() in SQL
 * 
 * @return string
 */
function datenow() : string {
    return date('Y-m-d H:i:s');
}

/**
 * Calculate distance in meters using coordinates.
 * 
 * @param float $latitude A
 * @param float $longitude A
 * @param float $latitude B
 * @param float $longitude B
 * 
 * @return float
 */
function geoDistance(float $lat1, float $long1, float $lat2, float $long2) : float {
    // Coordinates to meters
    $R = 6371e3;
    $x = deg2rad($lat1);
    $y = deg2rad($lat2);
    $z = deg2rad($lat2 - $lat1);
    $w = deg2rad($long2 - $long1);

    $a = sin($z / 2) * sin($z / 2) + cos($x) * cos($y) * sin($w / 2) * sin($w / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $d = $R * $c;
    return $d;
}

/**
 * Detect operative system.
 * 
 * @return string 'windows'|'osx'|'linux'
 */
function detectOS() : string {

    if (defined('PHP_OS_FAMILY')) {

        return strtolower(PHP_OS_FAMILY);

    } else if (defined('PHP_OS')) {

        $osname = PHP_OS;
        if ($osname == null) return '';
    
        if (in_array($osname, ['Windows', 'WINNT', 'WIN32']))
            return 'windows';
    
        if (in_array($osname, ['Darwin']))
            return 'osx';
    
        if (in_array($osname, ['Linux', 'Unix'])) {
            return 'linux';
        }
    
        return 'other';

    }

    return '';
}

/**
 * Escape HTML string.
 * 
 * @param string $str
 * 
 * @return string
 */
function e(string $str) : string {
    $text = htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $fix = [
        '\&#039;' => "'",
        '&quot;' => '"'
    ];
    foreach($fix as $k => $v) {
        $text = str_replace($k, $v, $text);
    }
    return $text;
}

/**
 * Iterate a for loop in a simpler way using a callable.
 * 
 * @param int $n
 * @param callable $func
 */
function forn(int $n, callable $func) {
    for($i = 0; $i < $n; ++ $i) {
        $func($i);
    }
}

/**
 * Run a shell command and display the output in realtime.
 * 
 * @param string CLI command
 * @param bool Output response?
 * 
 * @return string output
 */
function cmd(string $command, bool $echo = true) : string {
    $proc = popen($command, 'r');
    $total = '';
    while (!feof($proc))
    {
        $str = fread($proc, 4096);
        if ($echo) echo $str;
        $total .= $str;
        @ flush();
    }
    return $total;
}

/**
* Returns a GUIDv4 string
*
* Uses the best cryptographically secure method
* for all supported pltforms with fallback to an older,
* less secure version.
*
* @param bool $trim
* @return string
*/
function GUID ($trim = true)
{
    // Windows
    if (function_exists('com_create_guid') === true) {
        if ($trim === true)
            return trim(com_create_guid(), '{}');
        else
            return com_create_guid();
    }

    // OSX/Linux
    if (function_exists('openssl_random_pseudo_bytes') === true) {
        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    // Fallback (PHP 4.2+)
    mt_srand((double)microtime() * 10000);
    $charid = strtolower(md5(uniqid(rand(), true)));
    $hyphen = chr(45);                  // "-"
    $lbrace = $trim ? "" : chr(123);    // "{"
    $rbrace = $trim ? "" : chr(125);    // "}"
    $guidv4 = $lbrace.
              substr($charid,  0,  8).$hyphen.
              substr($charid,  8,  4).$hyphen.
              substr($charid, 12,  4).$hyphen.
              substr($charid, 16,  4).$hyphen.
              substr($charid, 20, 12).
              $rbrace;
    return $guidv4;
}