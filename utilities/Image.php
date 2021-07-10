<?php

class Image {

  /**
   * Image object
   * 
   * @var $image
   */
  private $image;

  /**
   * File name
   * 
   * @var string $name
   */
  private string $name;

  /**
   * Image extension (jpg, png, etc)
   * 
   * @var string $extension
   */
  private string $extension;

  /**
   * 
   */
  private $format;

  /**
   * File path
   * 
   * @var string $file
   */
  private string $file;

  /**
   * Image width
   * 
   * @var int $_width
   */
  private $_width;

  /**
   * Image height
   * 
   * @var int $_height
   */
  private $_height;

  /**
   * Get image width.
   * 
   * @return int
   */
  public function width() : int { return $this->_width; }

  /**
   * Get image height
   * 
   * @return int
   */
  public function height() : int { return $this->_height; }

  /**
   * Get Image object instance.
   * 
   * @param string $filename
   * @param string $format [Default automatic]
   * 
   * @return Image
   */
  public static function instance(string $filename, string $format = null) : Image {
    return new Image($filename, $format);
  }

  /**
   * @param string|Resource $filename
   * @param string $format [Default automatic]
   */
  public function __construct($filename, string $format = null) {

    if (is_string($filename)) {
      $this->file = $filename;
      
      if (strpos($filename, '/')) {
        $parts = explode('/', $filename);
        $last = $parts[sizeof($parts) - 1];
      } else {
        $last = $filename;
      }
      
      if (strpos($last, '.')) {
        $parts = explode('.', $last);

        $name = '';
        $ext = '';
        for ($i = 0; $i<sizeof($parts); ++$i) {
          if ($i < sizeof($parts) - 1) {
            if ($name != '') $name .= '.';
            $name .= $parts[$i];
          } else {
            $ext = $parts[$i];
          }
        }

        $this->name = $name;
        $this->extension = $ext;
      } else {
        $this->name = $last;
        $this->extension = $format == null ? 'jpg' : $format;
      }
      
      $this->format = $format == null ? $this->extension : $format;

      if ($this->format == 'png')
        $this->image = imagecreatefrompng($filename);
      else
        $this->image = imagecreatefromjpeg($filename);

    }
    else {
      $this->image = $filename;
      $this->extension = $format;
      $this->format = $format;
    }

    $this->_width = imagesx($this->image);
    $this->_height = imagesy($this->image);
    // getimagesize($file);
  }

  /**
   * Correct image orientation if it's rotated.
   */
  public function correctOrientation() {

    if (function_exists('exif_read_data')) {
      $exif = exif_read_data($this->file);

      if($exif && isset($exif['Orientation'])) {
        $orientation = $exif['Orientation'];
        if($orientation != 1){

          $deg = 0;
          switch ($orientation) {
            case 3:
              $deg = 180;
              break;
            case 6:
              $deg = 270;
              break;
            case 8:
              $deg = 90;
              break;
          }
          if ($deg) {
            $this->image = imagerotate($this->image, $deg, 0);       
          }
        }
      }
    }    
  }

  /**
   * Save image into file.
   * 
   * @param string $savefile
   * @param string $format [Default jpg]
   * @param string $quality [Default 100] From 0 to 100
   * 
   * @return string $savefile
   */
  function save(string $savefile, string $format = 'jpg', int $quality = 100) : string {
    
      if ($format == 'png') {
        // For PNG quality goes from 0 to 9
        $q = 9 * ($quality / 100.0);
        imagepng($this->image, $savefile, round($q));
      } else {
        // White background for jpg, in case of transparency
        $bg = imagecreatetruecolor($this->_width, $this->_height);
        imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
        imagealphablending($bg, TRUE);
        imagecopy($bg, $this->image, 0, 0, 0, 0, $this->_width, $this->_height);

        imagejpeg($bg, $savefile, $quality);
        imagedestroy($bg);
      }
      return $savefile;
  }

  /**
   * Generate a duplicate of this image object.
   * 
   * @return Image
   */
  function copy() : Image {
    $bg = imagecreatetruecolor($this->_width, $this->_height);
    if ($this->format == 'jpg')
      imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
    imagealphablending($bg, TRUE);
    imagecopy($bg, $this->image, 0, 0, 0, 0, $this->_width, $this->_height);
    return new Image($bg, $this->format);
  }

  /**
   * Limit image size.
   * 
   * @param int $w Max width
   * @param int $h Max height
   * @param bool $newImage Get a different Image object.
   * 
   * @return Image self
   */
  function cap(int $w, int $h = null, bool $newImage = false) : Image {
    if ($h == null) $h = $w;

      if($this->_width <= $w && $this->_height <= $h)
      {
          if ($newImage) return $this->copy();
          return $this;

      }

      $nw = $this->_width;
      $nh = $this->_height;
      if ($this->_width > $this->_height)
      {
          $nw = $w;
          $nh = intval($this->_height * $w / $this->_width);
      }
      else{
          $nh = $h;
          $nw = intval($this->_width * $h / $this->_height);
      }

      $thumb = imagecreatetruecolor($nw, $nh);

      imagecopyresampled($thumb, $this->image, 0, 0, 0, 0, $nw, $nh, $this->_width, $this->_height);
      
      if ($newImage) return new Image($thumb, $this->format);
      $this->image = $thumb;
      $this->_width = $nw;
      $this->_height = $nh;
      return $this;
  }

  /**
   * Convert image to base64 string
   * 
   * @return string
   */
  public function base64() : string {
    $data = file_get_contents($this->file);
    $base64 = 'data:image/' . $this->format . ';base64,' . base64_encode($data);
    return $base64;
  }

  /**
   * Generate Image object from base64
   * 
   * @param string $base64
   * @param string $format
   * 
   * @return Image
   */
  public static function fromBase64(string $base64, string $format = 'jpg') : Image {
    $data = base64_decode($base64);
    $img = imagecreatefromstring($data);
    return new Image($img, $format);
  }

}