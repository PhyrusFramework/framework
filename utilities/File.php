<?php

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
    foreach($parts as $p) {
        if (empty($p)) continue;
        
        $r .= "/$p";
        if (!in_array($p, array('.', '..')) && !is_dir(Path::root() . $r) && !file_exists($path)) {
            mkdir($path, $permissions, true);
        }
    }
    
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

class File {

    /**
     * File path.
     * 
     * @var string $path
     */
    public string $path;

    function __construct($filename) {
        $this->path = $filename;
    }

    /**
     * Get a File object instance.
     * 
     * @param string $path
     * 
     * @return File
     */
    public static function instance(string $path) : File {
        return new File($path);
    }

    /**
     * Check if this file exists.
     * 
     * @return bool
     */
    public function exists() : bool {
        return file_exists($this->path);
    }

    /**
     * Get the file name with or without extension.
     * 
     * @param bool $extension [Default true]
     * 
     * @return string
     */
    public function name(bool $extension = true) : string {
        $path_parts = pathinfo($this->path);
        if ($extension) {
            return $path_parts['basename'];
        } else {
            return $path_parts['filename'];
        }
    }

    /**
     * Get the file extension.
     * 
     * @return string
     */
    public function extension() : string {
        return file_extension($this->path);
    }

    /**
     * Get directory of this file.
     * 
     * @return string
     */
    public function folder() : string {
        return dirname($this->path);
    }

    /**
     * Get the file content.
     * 
     * @return string
     */
    public function content() : string {
        if (!file_exists($this->path)) return '';
        return file_get_contents($this->path);
    }

    /**
     * Write content into file.
     * 
     * @param string $content
     * 
     * @return File
     */
    public function write(string $content) : File {
        $file = fopen($this->path, 'w');
        fwrite($file, $content);
        fclose($file);
        return $this;
    }

    /**
     * Append content to file.
     * 
     * @param string $content
     * 
     * @return File
     */
    public function append(string $content) : File {
        $c = $this->content();
        $this->write($c . $content);
        return $this;
    }

    /**
     * Prepend content to file.
     * 
     * @param string $content
     * 
     * @return File
     */
    public function prepend($content) : File {
        $c = $this->content();
        $this->write($content . $c);
        return $this;
    }

    /**
     * Find and replace text inside the file.
     * 
     * @param string $text
     * @param string $replace
     * 
     * @return File self
     */
    public function replace(string $text, string $replace) : File {
        $c = $this->content();
        $c = str_replace($text, $replace, $c);
        $this->write($c);
        return $this;
    }

    /**
     * Delete file.
     * 
     * @return File
     */
    public function delete() : File {
        if (!$this->exists()) return $this;
        unlink($this->path);
        return $this;
    }

    /**
     * Get the file last modification date.
     * 
     * @param string
     */
    public function modification_date() : string {
        if (!file_exists($this->path)) return '';
        return date('Y-m-d H:i:s', filemtime($this->path));
    }

    /**
     * Get Folder object of the file directory.
     * 
     * @return Folder
     */
    public function getFolder() : Folder {
        return new Folder(dirname($this->path));
    }

    /**
     * Create a File from base64 string.
     * 
     * @param string $base64
     * @param string $filename
     * 
     * @return File
     */
    public static function parseBase64(string $base64, string $filename) : File {
        $ifp = fopen( $filename, 'wb' ); 
        $data = explode( ',', $base64 );
        fwrite( $ifp, base64_decode( $data[ 1 ] ) );
        fclose( $ifp ); 
        return new File($filename); 
    }

    /**
     * Try to guess the mime type.
     * 
     * @return string
     */
    public function getMime() : string {
        return mime_content_type($this->path);
    }

    /**
     * Convert this file to base64 string.
     * 
     * @param string $filetype [Default automatic] (png, jpeg, etc)
     * 
     * @return string
     */
    public function toBase64(string $filetype = null) : string {

        $type = $filetype ?? mime_content_type($this->path);

        $binary = fread(fopen($this->path, 'r'), filesize($this->path));
        return 'data:' . $type . ';base64,' . base64_encode($binary);

    }

    /**
     * Copy this file to another location.
     * 
     * @param string $newpath
     * @param bool $overwrite [Default false]
     * 
     * @param File
     */
    public function copyTo(string $newpath, bool $overwrite = false) : ?File {
        if (file_exists($newpath) && !$overwrite) {
            return null;
        }
        file_put_contents($newpath, file_get_contents($this->path));
        return File::instance($newpath);
    }

    /**
     * Move this file to another location.
     * 
     * @param string $newpath
     * @param bool $overwrite [Default false]
     * 
     * @return File
     */
    public function moveTo(string $newpath, bool $overwrite = false) : File {
        $this->copyTo($newpath, $overwrite);
        $this->delete();
        return File::instance($newpath);
    }

}