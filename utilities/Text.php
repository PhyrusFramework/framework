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
     * Get the modified text.
     * 
     * @return string
     */
    public function getString() : string {
        return $this->_txt;
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
     * @return Text self
     */
    public function toLower() : Text {
        $this->_txt = mb_strtolower($this->_txt);
        return $this;
    }

    /**
     * Converts text to lowercase
     * 
     * @return Text self
     */
    public function toUpper() : Text {
        $this->_txt = mb_strtoupper($this->_txt);
        return $this;
    }

    /**
     * Sanitize text to remove special characters.
     * 
     * @param array $custom [Default empty] Custom conversion.
     * 
     * @return Text self
     */
    public function sanitize(array $custom = []) : Text {
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

        $this->_txt = $t;
        return $this;
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
     * @return Text self
     */
    public function reverse() : Text {
        $aux = '';
        for($i = strlen($this->_txt) - 1; $i >= 0; --$i) {
            $aux .= $this->_txt[$i];
        }

        $this->_txt = $aux;
        return $this;
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
     * @return Text
     */
    public function replace(string $a, string $b) : Text {
        $this->_txt = str_replace($a, $b, $this->_txt);
        return $this;
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
     * @return Text self
     */
    public function trim($charlist = ' ') : Text {
        $this->_txt = trim($this->_txt, $charlist);
        return $this;
    }

    /**
     * Convert text encoding.
     * 
     * @param string $encoding (Example: UTF-8)
     * 
     * @return Text self
     */
    public function encoding(string $encoding) : Text {
        $this->_txt = mb_convert_encoding($this->_txt, $encoding);
        return $this;
    }

    /**
     * Check the string agains a regex pattern. Ex: {{ param }}
     * 
     * @param string $pattern
     * 
     * @return bool
     */
    public function match(string $pattern) : bool {
        $regex = '|^'. str_replace('\*', '.*', preg_quote($pattern)) .'$|is';
        return (bool) preg_match($regex, $this->_txt);
    }

    /**
     * Extract words from any text.
     * 
     * @return array
     */
    public function extractWords() {
        $matches = [];
        preg_match_all("/\b[a-zA-Z]+\b/", $this->_txt, $matches);
        return $matches;
    }

    /**
     * Check if string contains specific word.
     * 
     * @return bool
     */
    public function containsWord($word) : bool {
        return preg_match("/\b$word\b/i", $this->_txt);
    }

    /**
     * Find parameters in the text and replace them dynamically.
     * 
     * @param string $opener
     * @param string $closer
     * @param callable $replacer
     * 
     * @return Text self
     */
    public function replacer(string $opener, string $closer, callable $replacer) : Text {

        $str = '';
        $in = false;
        $current = '';
        $tag = '';
        $pos = 0;

        for($i = 0; $i < strlen($this->_txt); ++$i) {

            $ch = $this->_txt[$i];

            if (!$in) {

                if ($pos < strlen($opener) && $ch == $opener[$pos]) {
                    $tag .= $ch;
                    $pos += 1;

                    if ($pos == strlen($opener)) {
                        $in = true;
                        $pos = 0;
                        $current = '';
                        $tag = '';
                    }
                } else {
                    $str .= $tag . $ch;
                    $tag = '';
                    $pos = 0;
                }

            } else {

                if ($pos < strlen($closer) && $ch == $closer[$pos]) {
                    $pos += 1;

                    if ($pos == strlen($closer)) {
                        $in = false;
                        $pos = 0;
                        $str .= $replacer(trim($current));
                        $current = '';
                        $tag = '';
                    }
                } else {
                    $current .= $tag . $ch;
                    $tag = '';
                    $pos = 0;
                }

            }

        }

        $this->_txt = $str;
        return $this;

    }

}