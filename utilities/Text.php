<?php

class Text {

    /**
     * @var string $_txt Content
     */
    private $_txt;

    /**
     * Get a Text object instance.
     * 
     * @param string $txt
     * 
     * @return Text
     */
    public static function instance(string $txt) : Text {
        return new Text($txt);
    }

    public function __construct($txt) {
        $this->_txt = $txt;
    }

    /**
     * Explode string.
     * 
     * @param mixed $delimiter String or array
     * @param bool $empty [Default true] Accept empty strings.
     * 
     * @return array
     */
    public function split($delimiter, bool $empty = true) : array {

        if (is_array($delimiter)) $d = $delimiter;
        else $d = [$delimiter];

        $string = $this->_txt;

        $parts = [];
        $current = '';
        for($i = 0; $i<strlen($string); ++$i) {
            
            $found = false;
            foreach($d as $char) {
                if ($string[$i] == $char) {
                    $found = true;
                    break;
                }
            }
            if (!$found)
                $current .= $string[$i];
            else {
                if (!empty($current) || $current === '0') $parts[] = $current;
                else if ($empty) $parts[] = $current;
                $current = '';
            }
        }
        if (!empty($current) || $current === '0') $parts[] = $current;

        return $parts;

    }

    /**
     * Get string between two delimiters.
     * 
     * @param string $a
     * @param string $b
     * 
     * @return string
     */
    public function between(string $a = '(', string $b = ')') : string {
        $string = $this->_txt;

        $capture = '';
        $afound = false;

        for($i = 0; $i<strlen($string); ++$i)
        {
            if ($afound)
            {
                if ($string[$i] == $b)
                    return $capture;
                $capture .= $string[$i];
            }
            else if ($string[$i] == $a)
                $afound = true;
        }
        return $capture;
    }

    /**
     * Converts text to lowercase
     * 
     * @return string lowercase
     */
    public function toLower() : string {
        return mb_strtolower($this->_txt);
    }

    /**
     * Converts text to lowercase
     * 
     * @return string lowercase
     */
    public function toUpper() : string {
        return mb_strtoupper($this->_txt);
    }

    /**
     * Sanitize text to remove special characters.
     * 
     * @param array $custom [Default empty] Custom conversion.
     * 
     * @return string
     */
    public function sanitize(array $custom = []) : string {
        $t = mb_strtolower(trim($this->_txt));
        $changes = [
            ' ' => '-',
            '¿' => '',
            '?' => '',
            '*' => '',
            ',' => '',
            '.' => '',
            '(' => '',
            ')' => '',
            '[' => '',
            ']' => '',
            '!' => '',
            '¡' => '',
            'á' => 'a',
            'à' => 'a',
            'â' => 'a',
            'ä' => 'a',
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'í' => 'i',
            'ì' => 'i',
            'î' => 'i',
            'ï' => 'i',
            'ó' => 'o',
            'ò' => 'o',
            'ô' => 'o',
            'ö' => 'o',
            'ú' => 'u',
            'ù' => 'u',
            'û' => 'u',
            'ü' => 'u'
        ];
        if (is_array($custom)) {
            foreach($custom as $k => $v) {
                $changes[$k] = $v;
            }
        }

        foreach($changes as $k => $v) {
            $t = str_replace($k, $v, $t);
        }
        return $t;
    }

    /**
     * Check if string starts with.
     * 
     * @param string $needle
     * 
     * @return bool
     */
    public function startsWith(string $needle) : bool {
		return strncmp($this->_txt, $needle, strlen($needle)) === 0;
	}

    /**
     * Check if string ends with
     * 
     * @param string $needle
     * 
     * @param bool
     */
	public function endsWith(string $needle) : bool {
		return $needle === '' || substr($this->_txt, -strlen($needle)) === $needle;
    }
    
    /**
     * Check if string contains $needle
     * 
     * @param string $needle
     * 
     * @return bool
     */
    public function contains(string $needle) : bool {
		return strpos($this->_txt, $needle) !== false;
    }
    
    /**
     * Reverse string.
     * 
     * @return string
     */
    public function reverse() : string {
        $aux = '';
        for($i = strlen($this->_txt) - 1; $i >= 0; --$i) {
            $aux .= $this->_txt[$i];
        }
        return $aux;
    }

    /**
     * Get index where string starts. -1 if not found.
     * 
     * @param string $needle
     * 
     * @return int
     */
    public function indexOf(string $needle) : int {
		$pos = strpos($this->_txt, $needle);
		return $pos === false
			? -1
			: $pos;
    }
    
    /**
     * Replace string.
     * 
     * @param string $a
     * @param string $b
     * 
     * @return string
     */
    public function replace(string $a, string $b) : string {
        return str_replace($a, $b, $this->_txt);
    }

    /**
     * Generate random string.
     * 
     * @param int $length [Default 20]
     * 
     * @return string
     */
    public static function random(int $length = 20) : string {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * Get the similarity percentage between two strings.
     * 
     * @param string $a
     * @param string $b
     * 
     * @return float
     */
    public static function similarity(string $a, string $b) : float {
        if ($a == '' && $b == '') return 100;
        similar_text($a, $b, $percent);
        return $percent;
    }

    /**
     * Removes characters from the left and right of the text, by default spaces.
     * 
     * @param string $charlist
     * 
     * @return string
     */
    public function trim($charlist = ' ') {
        return trim($this->_txt, $charlist);
    }

    /**
     * Convert text encoding.
     * 
     * @param string $encoding (Example: UTF-8)
     * 
     * @return string encoded
     */
    public function encoding(string $encoding) {
        return mb_convert_encoding($this->_txt, $encoding);
    }

    /**
     * Check the string agains a regex pattern.
     * 
     * @param string $pattern
     * 
     * @return bool
     */
    public function match(string $pattern) : bool {
        $regex = '|^'. str_replace('\*', '.*', preg_quote($pattern)) .'$|is';
        return (bool) preg_match($regex, $this->_txt);
    }

}