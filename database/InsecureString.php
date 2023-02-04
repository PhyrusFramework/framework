<?php

class InsecureString implements JsonSerializable {

    private $text = '';

    public static function instance($value) {
        return new InsecureString($value);
    }

    public function jsonSerialize(): mixed {
        return $this->text;
    }

    function __construct($value) {
        if (is_array($value)) {
            $this->text = JSON::stringify($value);
        } else if (gettype($value) == 'Arr') {
            $this->text = JSON::stringify($value->getArray());
        } else {
            $this->text = "$value";
        }
    }

    /**
     * Returns the string.
     * 
     * @return string
     */
    public function getString() : string {
        return $this->text;
    }

    /**
     * Remove HTML script tags from string.
     * 
     * @return InsecureString
     */
    public function removeScriptTags() : InsecureString {
        $t = Text::instance($this->text);
        $this->text = $t->replacer('<script', '</script>', function($content) {
            return '';
        })->getString();
        return $this;
    }
}