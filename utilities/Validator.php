<?php

class Validator {

    private $value;

    private $checks = [];

    function __construct($value) {
        $this->value = $value;
    }

    /**
     * Creates a Validator instance.
     * 
     * @param $value
     * 
     * @return Validator
     */
    public static function for($value) {
        return new Validator($value);
    }

    public function clear() {
        $this->checks = [];
    }

    // OPERATIONS

    /**
     * Check if value is a string.
     */
    public function isString() {
        $this->checks[] = [
            'function' => function($value) {
                return is_string($value);
            }
        ];

        return $this;
    }

    /**
     * Check if value is equal to.
     * 
     * @param mixed $e
     */
    public function is($e) {

        $this->checks[] = [
            'function' => function($value, $param) {
                return $value === $param;
            },
            'parameter' => $e
        ];

        return $this;

    }

    /**
     * Check if value is not equal to.
     * 
     * @param mixed $e
     */
    public function isNot($e) {

        $this->checks[] = [
            'function' => function($value, $param) {
                return $value !== $param;
            },
            'parameter' => $e
        ];

        return $this;

    }

    /**
     * Check if value's type is
     * 
     * @param string $type
     */
    public function typeIs(string $type) {

        $this->checks[] = [
            'function' => function($value, $param) {
                return mb_strtolower(gettype($value)) == $param;
            },
            'parameter' => $type
        ];

        return $this;

    }

    /**
     * Check if value's type is not
     * 
     * @param string $type
     */
    public function typeIsNot(string $type) {

        $this->checks[] = [
            'function' => function($value, $param) {
                return mb_strtolower(gettype($value)) != $param;
            },
            'parameter' => $type
        ];

        return $this;

    }

    /**
     * Check if value contains this element.
     * 
     * @param mixed $e
     */
    public function contains($e) {

        if (is_array($this->value)) {
            $this->checks[] = [
                'function' => function($value, $param) {
                    return in_array($param, $value);
                },
                'parameter' => $e
            ];
        } else {
            $this->checks[] = [
                'function' => function($value, $param) {
                    return !(strpos("$value", $param) === FALSE);
                },
                'parameter' => $e
            ];
        }

        return $this;

    }

    /**
     * Check if value's length or size is at least this long.
     * 
     * @param int $length
     */
    public function minLength($length) {
        if (is_array($this->value)) {
            $this->checks[] = [
                'function' => function($value, $param) {
                    return sizeof($value) >= $param;
                },
                'parameter' => $length
            ];
        } else {
            $this->checks[] = [
                'function' => function($value, $param) {
                    return strlen($value) >= $param;
                },
                'parameter' => $length
            ];
        }

        return $this;
    }

    /**
     * Check if value's length or size is at maximum this long.
     * 
     * @param int $length
     */
    public function maxLength($length) {
        if (is_array($this->value)) {
            $this->checks[] = [
                'function' => function($value, $param) {
                    return sizeof($value) <= $param;
                },
                'parameter' => $length
            ];
        } else {
            $this->checks[] = [
                'function' => function($value, $param) {
                    return strlen($value) <= $param;
                },
                'parameter' => $length
            ];
        }

        return $this;
    }

    /**
     * Validate if value is an email.
     */
    public function isEmail() {
        $this->checks[] = [
            'function' => function($value) {
                if (!is_string($value)) return false;
                if (strlen($value) < 5) return false;
                if (strpos($value, '@') === FALSE) return false;
                if (strpos(explode('@', $value)[1], '.') === FALSE) return false;

                return true;
            }
        ];

        return $this;
    }

    /**
     * Check if value uses only uppercase characters.
     */
    public function isUppercase() {
        $this->checks[] = [
            'function' => function($value) {
                if (!is_string($value)) return false;
                return $value == mb_strtoupper($value);
            }
        ];

        return $this;
    }

    /**
     * Check if value uses only lowercase characters.
     */
    public function isLowercase() {
        $this->checks[] = [
            'function' => function($value) {
                if (!is_string($value)) return false;
                return $value == mb_strtolower($value);
            }
        ];

        return $this;
    }

    /**
     * Check if value contains uppercase caracters.
     * 
     * @param int $min
     */
    public function hasUppercase($min = 1) {
        $this->checks[] = [
            'function' => function($value, $param) {
                if (!is_string($value)) return false;

                $count = 0;

                for($i = 0; $i < strlen($value); ++$i) {
                    if (ctype_upper($value[$i])) {
                        $count += 1;

                        if ($count >= $param) {
                            return true;
                        }
                    }
                }

                return false;
            },
            'parameter' => $min
        ];

        return $this;
    }

    /**
     * Check if value has not uppercase characters.
     */
    public function hasNotUppercase() {
        $this->checks[] = [
            'function' => function($value) {
                if (!is_string($value)) return true;

                for($i = 0; $i < strlen($value); ++$i) {
                    if (ctype_upper($value[$i])) {
                        return false;
                    }
                }

                return true;
            }
        ];

        return $this;
    }

    /**
     * Check if value contains lowercase characters.
     * 
     * @param int $min
     */
    public function hasLowercase($min = 1) {
        $this->checks[] = [
            'function' => function($value, $param) {
                if (!is_string($value)) return false;

                $count = 0;

                for($i = 0; $i < strlen($value); ++$i) {
                    if (ctype_lower($value[$i])) {
                        $count += 1;

                        if ($count >= $param) {
                            return true;
                        }
                    }
                }

                return false;
            },
            'parameter' => $min
        ];

        return $this;
    }

    /**
     * Check if value has not lowercase characters.
     */
    public function hasNotLowercase() {
        $this->checks[] = [
            'function' => function($value) {
                if (!is_string($value)) return true;

                for($i = 0; $i < strlen($value); ++$i) {
                    if (ctype_lower($value[$i])) {
                        return false;
                    }
                }

                return true;
            }
        ];

        return $this;
    }

    /**
     * Check if value is a number.
     */
    public function isNumber() {
        $this->checks[] = [
            'function' => function($value) {
                return is_numeric($value);
            }
        ];

        return $this;
    }

    /**
     * Check if value has numbers.
     * 
     * @param int $min
     */
    public function hasNumbers($min = 1) {

        $this->checks[] = [
            'function' => function($value, $param = 1) {
                
                $v = "$value";
                $count = 0;

                for($i = 0; $i < strlen($value); ++$i) {
                    if (is_numeric($value[$i])) {
                        $count += 1;

                        if ($count >= $param) {
                            return true;
                        }
                    }
                }

                return false;
            },
            'parameter' => $min
        ];

        return $this;

    }

    /**
     * Check if value has not numbers.
     */
    public function hasNotNumbers() {

        $this->checks[] = [
            'function' => function($value) {
                
                $v = "$value";

                for($i = 0; $i < strlen($value); ++$i) {
                    if (is_numeric($value[$i])) {
                        return false;
                    }
                }

                return true;
            }
        ];

        return $this;

    }

    /**
     * Check if value has only letters.
     */
    public function isLetters() {
        $this->checks[] = [
            'function' => function($value) {
                if (!is_string($value)) return false;

                for($i = 0; $i < strlen($value); ++$i) {
                    if (!ctype_alpha($value[$i]) && $value[$i] != ' ') {
                        return false;
                    }
                }
                return true;
            }
        ];

        return $this;
    }

    /**
     * Check if value contains letters.
     * 
     * @param int $min
     */
    public function hasLetters($min = 1) {
        $this->checks[] = [
            'function' => function($value, $param) {
                if (!is_string($value)) return false;

                $count = 0;

                for($i = 0; $i < strlen($value); ++$i) {
                    if (ctype_alpha($value[$i])) {
                        $count += 1;

                        if ($count >= $param) {
                            return true;
                        }
                    }
                }

                return false;
            },
            'parameter' => $min
        ];

        return $this;
    }

    /**
     * Check if value does not contain letters.
     */
    public function hasNotLetters() {
        $this->checks[] = [
            'function' => function($value) {
                if (!is_string($value)) return true;

                for($i = 0; $i < strlen($value); ++$i) {
                    if (ctype_alpha($value[$i])) {
                        return false;
                    }
                }

                return true;
            }
        ];

        return $this;
    }

    /**
     * Check if value has only special characters.
     */
    public function isSpecialChars() {
        $this->checks[] = [
            'function' => function($value) {
                if (!is_string($value)) return false;
                return ctype_punct($value);
            }
        ];

        return $this;
    }

    /**
     * Check if value contains special characters.
     * 
     * @param int $min
     */
    public function hasSpecialChars($min = 1) {
        $this->checks[] = [
            'function' => function($value, $param) {
                if (!is_string($value)) return false;

                $count = 0;

                for($i = 0; $i < strlen($value); ++$i) {
                    if (ctype_punct($value[$i])) {
                        $count += 1;

                        if ($count >= $param) {
                            return true;
                        }
                    }
                }

                return false;
            },
            'parameter' => $min
        ];

        return $this;
    }

    /**
     * Check if value does not contain special characters.
     */
    public function hasNotSpecialChars() {
        $this->checks[] = [
            'function' => function($value) {
                if (!is_string($value)) return true;

                for($i = 0; $i < strlen($value); ++$i) {
                    if (ctype_punct($value[$i])) {
                        return false;
                    }
                }

                return true;
            }
        ];

        return $this;
    }

    /**
     * Check if value is a phone number
     */
    public function isPhone() {

        $this->checks[] = [
            'function' => function($value) {
                $v = "$value";

                $valid = '+0123456789 -()';
                for($i = 0; $i < strlen($v); ++$i) {
                    if (strpos($valid, $v[$i]) === FALSE) {
                        return false;
                    }
                }
                
                return true;
            }
        ];

        return $this;
    }

    /**
     * Check if value is a boolean.
     */
    public function isBool() {
        $this->checks[] = [
            'function' => function($value) {
                return is_bool($value);
            }
        ];

        return $this;
    }

    /**
     * Check if value has property.
     * 
     * @param string|int property
     */
    public function has($property) {
        $this->checks[] = [
            'function' => function($value, $param) {
                if ($value == null) return false;
                if (!is_array($value)) return isset($value->{$param});
                return isset($value[$param]);
            },
            'parameter' => $property
        ]; 

        return $this;
    }

    /**
     * Check if value is not empty.
     * 
     * @param string|int property
     */
    public function notEmpty($property = null) {
        $this->checks[] = [
            'function' => function($value, $param) {

                if ($param != null) {
                    if ($value == null) return false;
                    if (!is_array($value)) return !empty($value->{$param});
                    return !empty($value[$param]);
                } else {
                    return !empty($value);
                }
            },
            'parameter' => $property
        ]; 

        return $this;
    }


    // VALIDATE

    public function validate() {

        foreach($this->checks as $check) {

            $function = $check['function'];
            $param = isset($check['parameter']) ? $check['parameter'] : null;

            $valid = true;
            if ($param == null)
                $valid = $function($this->value);
            else
                $valid = $function($this->value, $param);

            if (!$valid) {
                return false;
            }

        }

        return true;

    }

}