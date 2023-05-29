<?php

/**
 * Import all php files in directory and sub-directories
 * 
 * @param string Directory
 * @param bool Instead of loading php files, use autoload to load only when class with the same name as the field is used.
 */
function php_in(string $directory, bool $autoload = false) {

    if (!file_exists($directory)) {
        return;
    }

    if (!is_dir($directory)) {
        if (!$autoload)
            require_once($directory);
        else {
            $cl = basename($directory);
            $cl = str_replace('.php', '', $cl);

            autoload($cl, $directory);
        }

        return;
    }

    $___files = subfiles($directory, 'php');
    foreach($___files as $_file) {
        php_in($_file, $autoload);
    }

    $dirs = subfolders($directory);
    foreach($dirs as $dir) {
        php_in($dir, $autoload);
    }

}

/**
 * Check if variable is a closure: callable but not string.
 * 
 * @param mixed Variable
 * 
 * @return bool
 */
function is_closure($var) : bool {
    return !is_string($var) && is_callable($var);
}

/**
 * Get classes extending the specified class.
 * 
 * @param string Class name
 * 
 * @return array
 */
function getSubclassesOf(string $className) : array {
    $subclasses = [];
    $allClasses = get_declared_classes();
    
    foreach ($allClasses as $class) {
        $classReflector = new ReflectionClass($class);
        if ($classReflector->isSubclassOf($className)) {
            $subclasses[] = $class;
        }
    }
    
    return $subclasses;
}

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
    return $str;
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
function now() : string {
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

/**
 * Run a CLI command.
 * 
 * @param string Command
 */
function CLI(string $command) {

    if (!class_exists('CLI')) {
        require_once(realpath(__DIR__ . '/../cli/loader.php'));
    }

    $args = explode(' ', $command);
    array_unshift($args, 'phyrus');

    // Initialize the CLI
    global $CLI;
    $CLI = new CLI($args);

    // Run the CLI
    $CLI->run();
}

/**
 * Get the file name with or without extension.
 * 
 * @param string $path
 * @param bool $extension [Default true]
 * 
 * @return string
 */
function file_name(string $path, bool $extension = true) : string {
    $path_parts = pathinfo($path);
    if ($extension) {
        return $path_parts['basename'];
    } else {
        return $path_parts['filename'];
    }
}

/**
 * Get the file extension.
 * 
 * @param string $path
 * 
 * @return string
 */
function file_extension(string $path) : string {
    $path_parts = pathinfo($path);
    return $path_parts['extension'];
}

/**
 * File last modification date in format Y-m-d H:i:s
 * 
 * @param string $filename
 * 
 * @return string
 */
function last_modification_date(string $filename) : string {
    if (!file_exists($filename)) return '';
    return date('Y-m-d H:i:s', filemtime($filename));
}

/**
 * Create a folder and all parent folders needed to complete the path.
 * 
 * @param string $path
 * @param int $permissions [Default 0777]
 * 
 * @return bool
 */
function create_folder(string $path, int $permissions = 0777) : bool {
    $root = Path::root();
    $diff = str_replace($root, '', $path);
    $diff = str_replace("\\", '/', $diff);
    $parts = explode('/', $diff);

    $r = '';
    $oldMask = umask(0);
    foreach($parts as $p) {
        if (empty($p)) continue;
        
        $r .= "/$p";
        if (!in_array($p, array('.', '..')) && !is_dir(Path::root() . $r) && !file_exists($path)) {
            mkdir($path, $permissions, true);
        }
    }
    umask($oldMask);
    
    return is_dir($path);
}

/**
 * Get directory subfolders.
 * 
 * @param string $path
 * 
 * @return string[]
 */
function subfolders(string $path) : array {
    if (!is_dir($path)) return [];
    return array_filter(glob($path . '/{,.}*[!.]*',GLOB_MARK|GLOB_BRACE), 'is_dir');
}

/**
 * Get files in directory.
 * 
 * @param string $path
 * @param string $extension [Default *]
 * 
 * @return string[]
 */
function subfiles(string $path, string $extension = '*') : array {
    $ext = "";
    if ($extension != '*') {
        $ext = ".$extension";
    }
    $aux = glob($path . "/{,.}*[!.]*$ext",GLOB_MARK|GLOB_BRACE);

    $list = [];
    foreach($aux as $i) {
        if (!is_dir($i)) {
            $list[] = $i;
        }
    }
    return $list;
}

/**
 * Deletes a folder.
 * 
 * @param string $path
 */
function delete_folder(string $path) {
    $folder = new Folder($path);
    $folder->delete();
}

/**
 * Get extension from Mime
 * 
 * @param string Mime
 * 
 * @return string
 */
function getMimeExtension(string $mime) : string {
    $mime_map = [
        'video/3gpp2'                                                               => '3g2',
        'video/3gp'                                                                 => '3gp',
        'video/3gpp'                                                                => '3gp',
        'application/x-compressed'                                                  => '7zip',
        'audio/x-acc'                                                               => 'aac',
        'audio/ac3'                                                                 => 'ac3',
        'application/postscript'                                                    => 'ai',
        'audio/x-aiff'                                                              => 'aif',
        'audio/aiff'                                                                => 'aif',
        'audio/x-au'                                                                => 'au',
        'video/x-msvideo'                                                           => 'avi',
        'video/msvideo'                                                             => 'avi',
        'video/avi'                                                                 => 'avi',
        'application/x-troff-msvideo'                                               => 'avi',
        'application/macbinary'                                                     => 'bin',
        'application/mac-binary'                                                    => 'bin',
        'application/x-binary'                                                      => 'bin',
        'application/x-macbinary'                                                   => 'bin',
        'image/bmp'                                                                 => 'bmp',
        'image/x-bmp'                                                               => 'bmp',
        'image/x-bitmap'                                                            => 'bmp',
        'image/x-xbitmap'                                                           => 'bmp',
        'image/x-win-bitmap'                                                        => 'bmp',
        'image/x-windows-bmp'                                                       => 'bmp',
        'image/ms-bmp'                                                              => 'bmp',
        'image/x-ms-bmp'                                                            => 'bmp',
        'application/bmp'                                                           => 'bmp',
        'application/x-bmp'                                                         => 'bmp',
        'application/x-win-bitmap'                                                  => 'bmp',
        'application/cdr'                                                           => 'cdr',
        'application/coreldraw'                                                     => 'cdr',
        'application/x-cdr'                                                         => 'cdr',
        'application/x-coreldraw'                                                   => 'cdr',
        'image/cdr'                                                                 => 'cdr',
        'image/x-cdr'                                                               => 'cdr',
        'zz-application/zz-winassoc-cdr'                                            => 'cdr',
        'application/mac-compactpro'                                                => 'cpt',
        'application/pkix-crl'                                                      => 'crl',
        'application/pkcs-crl'                                                      => 'crl',
        'application/x-x509-ca-cert'                                                => 'crt',
        'application/pkix-cert'                                                     => 'crt',
        'text/css'                                                                  => 'css',
        'text/x-comma-separated-values'                                             => 'csv',
        'text/comma-separated-values'                                               => 'csv',
        'application/vnd.msexcel'                                                   => 'csv',
        'application/x-director'                                                    => 'dcr',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
        'application/x-dvi'                                                         => 'dvi',
        'message/rfc822'                                                            => 'eml',
        'application/x-msdownload'                                                  => 'exe',
        'video/x-f4v'                                                               => 'f4v',
        'audio/x-flac'                                                              => 'flac',
        'video/x-flv'                                                               => 'flv',
        'image/gif'                                                                 => 'gif',
        'application/gpg-keys'                                                      => 'gpg',
        'application/x-gtar'                                                        => 'gtar',
        'application/x-gzip'                                                        => 'gzip',
        'application/mac-binhex40'                                                  => 'hqx',
        'application/mac-binhex'                                                    => 'hqx',
        'application/x-binhex40'                                                    => 'hqx',
        'application/x-mac-binhex40'                                                => 'hqx',
        'text/html'                                                                 => 'html',
        'image/x-icon'                                                              => 'ico',
        'image/x-ico'                                                               => 'ico',
        'image/vnd.microsoft.icon'                                                  => 'ico',
        'text/calendar'                                                             => 'ics',
        'application/java-archive'                                                  => 'jar',
        'application/x-java-application'                                            => 'jar',
        'application/x-jar'                                                         => 'jar',
        'image/jp2'                                                                 => 'jp2',
        'video/mj2'                                                                 => 'jp2',
        'image/jpx'                                                                 => 'jp2',
        'image/jpm'                                                                 => 'jp2',
        'image/jpeg'                                                                => 'jpeg',
        'image/pjpeg'                                                               => 'jpeg',
        'application/x-javascript'                                                  => 'js',
        'application/json'                                                          => 'json',
        'text/json'                                                                 => 'json',
        'application/vnd.google-earth.kml+xml'                                      => 'kml',
        'application/vnd.google-earth.kmz'                                          => 'kmz',
        'text/x-log'                                                                => 'log',
        'audio/x-m4a'                                                               => 'm4a',
        'audio/mp4'                                                                 => 'm4a',
        'application/vnd.mpegurl'                                                   => 'm4u',
        'audio/midi'                                                                => 'mid',
        'application/vnd.mif'                                                       => 'mif',
        'video/quicktime'                                                           => 'mov',
        'video/x-sgi-movie'                                                         => 'movie',
        'audio/mpeg'                                                                => 'mp3',
        'audio/mpg'                                                                 => 'mp3',
        'audio/mpeg3'                                                               => 'mp3',
        'audio/mp3'                                                                 => 'mp3',
        'video/mp4'                                                                 => 'mp4',
        'video/mpeg'                                                                => 'mpeg',
        'application/oda'                                                           => 'oda',
        'audio/ogg'                                                                 => 'ogg',
        'video/ogg'                                                                 => 'ogg',
        'application/ogg'                                                           => 'ogg',
        'font/otf'                                                                  => 'otf',
        'application/x-pkcs10'                                                      => 'p10',
        'application/pkcs10'                                                        => 'p10',
        'application/x-pkcs12'                                                      => 'p12',
        'application/x-pkcs7-signature'                                             => 'p7a',
        'application/pkcs7-mime'                                                    => 'p7c',
        'application/x-pkcs7-mime'                                                  => 'p7c',
        'application/x-pkcs7-certreqresp'                                           => 'p7r',
        'application/pkcs7-signature'                                               => 'p7s',
        'application/pdf'                                                           => 'pdf',
        'application/octet-stream'                                                  => 'pdf',
        'application/x-x509-user-cert'                                              => 'pem',
        'application/x-pem-file'                                                    => 'pem',
        'application/pgp'                                                           => 'pgp',
        'application/x-httpd-php'                                                   => 'php',
        'application/php'                                                           => 'php',
        'application/x-php'                                                         => 'php',
        'text/php'                                                                  => 'php',
        'text/x-php'                                                                => 'php',
        'application/x-httpd-php-source'                                            => 'php',
        'image/png'                                                                 => 'png',
        'image/x-png'                                                               => 'png',
        'application/powerpoint'                                                    => 'ppt',
        'application/vnd.ms-powerpoint'                                             => 'ppt',
        'application/vnd.ms-office'                                                 => 'ppt',
        'application/msword'                                                        => 'doc',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'application/x-photoshop'                                                   => 'psd',
        'image/vnd.adobe.photoshop'                                                 => 'psd',
        'audio/x-realaudio'                                                         => 'ra',
        'audio/x-pn-realaudio'                                                      => 'ram',
        'application/x-rar'                                                         => 'rar',
        'application/rar'                                                           => 'rar',
        'application/x-rar-compressed'                                              => 'rar',
        'audio/x-pn-realaudio-plugin'                                               => 'rpm',
        'application/x-pkcs7'                                                       => 'rsa',
        'text/rtf'                                                                  => 'rtf',
        'text/richtext'                                                             => 'rtx',
        'video/vnd.rn-realvideo'                                                    => 'rv',
        'application/x-stuffit'                                                     => 'sit',
        'application/smil'                                                          => 'smil',
        'text/srt'                                                                  => 'srt',
        'image/svg+xml'                                                             => 'svg',
        'application/x-shockwave-flash'                                             => 'swf',
        'application/x-tar'                                                         => 'tar',
        'application/x-gzip-compressed'                                             => 'tgz',
        'image/tiff'                                                                => 'tiff',
        'font/ttf'                                                                  => 'ttf',
        'text/plain'                                                                => 'txt',
        'text/x-vcard'                                                              => 'vcf',
        'application/videolan'                                                      => 'vlc',
        'text/vtt'                                                                  => 'vtt',
        'audio/x-wav'                                                               => 'wav',
        'audio/wave'                                                                => 'wav',
        'audio/wav'                                                                 => 'wav',
        'application/wbxml'                                                         => 'wbxml',
        'video/webm'                                                                => 'webm',
        'image/webp'                                                                => 'webp',
        'audio/x-ms-wma'                                                            => 'wma',
        'application/wmlc'                                                          => 'wmlc',
        'video/x-ms-wmv'                                                            => 'wmv',
        'video/x-ms-asf'                                                            => 'wmv',
        'font/woff'                                                                 => 'woff',
        'font/woff2'                                                                => 'woff2',
        'application/xhtml+xml'                                                     => 'xhtml',
        'application/excel'                                                         => 'xl',
        'application/msexcel'                                                       => 'xls',
        'application/x-msexcel'                                                     => 'xls',
        'application/x-ms-excel'                                                    => 'xls',
        'application/x-excel'                                                       => 'xls',
        'application/x-dos_ms_excel'                                                => 'xls',
        'application/xls'                                                           => 'xls',
        'application/x-xls'                                                         => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
        'application/vnd.ms-excel'                                                  => 'xlsx',
        'application/xml'                                                           => 'xml',
        'text/xml'                                                                  => 'xml',
        'text/xsl'                                                                  => 'xsl',
        'application/xspf+xml'                                                      => 'xspf',
        'application/x-compress'                                                    => 'z',
        'application/x-zip'                                                         => 'zip',
        'application/zip'                                                           => 'zip',
        'application/x-zip-compressed'                                              => 'zip',
        'application/s-compressed'                                                  => 'zip',
        'multipart/x-zip'                                                           => 'zip',
        'text/x-scriptzsh'                                                          => 'zsh',
    ];

    return isset($mime_map[$mime]) ? $mime_map[$mime] : '';
}