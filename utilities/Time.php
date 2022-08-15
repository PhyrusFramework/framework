<?php

class Time {

    /**
     * Date time object.
     * 
     * @var DateTime
     */
    private DateTime $_datetime;

    /**
     * Predefined formats.
     * 
     * @var array
     */
    public static array $formats = [
        'time' => 'H:i:s',
        'date' => 'd/m/Y',
        'datetime' => 'd/m/Y H:i',
        'string' => 'l jS F Y',
        'day of year' => 'z'
    ];

    /**
     * @param mixed $time String, Date or DateTime
     * @param string $format
     */
    public function __construct($time = null, string $format = 'Y-m-d H:i:s') {
        
        if ($time == null || is_string($time))
            $this->setDate($time, $format);
        else
            $this->_datetime = $time;

    }

    /**
     * Set the date
     * 
     * @param mixed $date
     * @param string $format
     */
    public function setDate($time, string $format = 'Y-m-d H:i:s') {
        $date = DateTime::createFromFormat($format, $time == null ? datenow() : $time);

        if (is_bool($date) && $format == 'Y-m-d H:i:s') {
            $date = DateTime::createFromFormat('Y-m-d H:i', $time == null ? datenow() : $time);
        }

        $this->_datetime = is_bool($date) ? new DateTime() : $date;
    }

    /**
     * Get Time object instance.
     * 
     * @param mixed $time
     * @param string $format
     * 
     * @return Time
     */
    public static function instance($time = null, string $format = 'Y-m-d H:i:s') : Time {
        return new Time($time, $format);
    }

    /**
     * Build a Time object from a timestamp number.
     * 
     * @param int $timestamp
     * 
     * @return Time
     */
    public static function fromTimestamp(int $timestamp) : Time {
        $datetime = new DateTime();
        $datetime->setTimestamp($timestamp);
        return new Time($datetime);
    }

    /**
     * Format date to text
     * 
     * @param string $format
     * 
     * @return string
     */
    public function format(string $format = 'Y-m-d H:i:s') : string {
        return $this->_datetime->format($format);
    }

    /**
     * Get a predefined format
     * 
     * @param string $formatName
     * 
     * @return string
     */
    public function get(string $formatName = 'datetime') : string {
        if (!isset(Time::$formats[$formatName])) return '';

        $format = Time::$formats[$formatName];
        return $this->_datetime->format($format);
    }

    /**
     * Get a copy of this Time object.
     * 
     * @return Time
     */
    public function copy() : Time {
        return new Time($this->format());
    }

    public function __get($name) {

        if ($name == 'datetime') {
            return $this->_datetime;
        }
        else if ($name == 'timestamp') {
            return $this->_datetime->getTimestamp();
        }
        else if ($name == 'day') {
            return intval($this->format('d'));
        }
        else if ($name == 'month') {
            return intval($this->format('m'));
        }
        else if ($name == 'year') {
            return intval($this->format('Y'));
        }
        else if ($name == 'hour') {
            return intval($this->format('H'));
        }
        else if ($name == 'minute') {
            return intval($this->format('i'));
        }
        else if ($name == 'second') {
            return intval($this->format('s'));
        }
    }

    /**
     * Get day number of the week (1-7).
     * 
     * @param bool $mondayFirst [Default true]
     * 
     * @return Generic [position, day]
     */
    public function dayOfWeek(bool $mondayFirst = true) : Generic {
        $position = intval($this->format($mondayFirst ? 'N' : 'w'));
        $day = strtolower($this->format('l'));

        if ($mondayFirst) {
            $position -= 1;
        }

        return new Generic(array(
            'position' => $position,
            'day' => $day
        ));
    }

    /**
     * Add time to this time.
     * 
     * @param int|TimeInterval $amount
     * @param string $type second|minute|hour|day|month|year
     * 
     * @return Time self
     */
    public function add($amount, string $type = 'day') : Time {

        if (gettype($amount) == 'object' && get_class($amount) == 'TimeInterval') {

            $this->add($amount->seconds, 'second');

            return $this;
        }

        $symbol = '+';
        $a = $amount;

        if (is_string($a)) {
            $a = intval($a);
        }

        if ($a < 0) {
            $a *= -1;
            $symbol = '-';
        }
        $this->_datetime->modify("$symbol $a $type");
        return $this;
    }

    /**
     * Set year.
     * 
     * @param int $year
     * 
     * @return Time
     */
    public function setYear(int $year) : Time {
        $this->setDate($year . $this->format('-m-d H:i:s'));
        return $this;
    }

    /**
     * Set month
     * 
     * @param int $month
     * 
     * @return Time self
     */
    public function setMonth(int $month) : Time {

        $str = "$month";
        if (strlen($str) < 2) $str = "0$str";

        $this->setDate($this->format('Y') . "-$str-" . $this->format('d H:i:s'));
        return $this;
    }

    /**
     * Set day
     * 
     * @param int $day
     * 
     * @return Time self
     */
    public function setDay(int $day) : Time {
        $str = "$day";
        if (strlen($str) < 2) $str = "0$str";

        $this->setDate($this->format('Y-m') . "-$str " . $this->format('H:i:s'));
        return $this;
    }

    /**
     * Set hour
     * 
     * @param int $hour
     * 
     * @return Time self
     */
    public function setHour(int $hour) : Time {
        $str = "$hour";
        if (strlen($str) < 2) $str = "0$str";

        $this->setDate($this->format('Y-m-d') . " $str:" . $this->format('i:s'));
        return $this;
    }

    /**
     * Set minute
     * 
     * @param int $minute
     * 
     * @return Time
     */
    public function setMinute(int $minute) : Time {
        $str = "$minute";
        if (strlen($str) < 2) $str = "0$str";

        $this->setDate($this->format('Y-m-d H') . ":$str:" . $this->format('s'));
        return $this;
    }

    /**
     * Set second
     * 
     * @param int $second
     * 
     * @return Time
     */
    public function setSecond(int $second) : Time {
        $str = "$second";
        if (strlen($str) < 2) $str = "0$str";

        $this->setDate($this->format('Y-m-d H:i') . ":$str");
        return $this;
    }

    /**
     * Get the TimeInterval from this date to now
     * 
     * @return TimeInterval
     */
    public function toNow() : TimeInterval {
        return new TimeInterval($this, new Time());
    }

    /**
     * Get the TimeInterval since...
     * 
     * @param Time $since
     * 
     * @return TimeInterval
     */
    public function since(Time $since) : TimeInterval {
        return new TimeInterval($since, $this);
    }

    /**
     * Get the TimeInterval until...
     * 
     * @param Time $until
     * 
     * @return TimeInterval
     */
    public function until(Time $until) : TimeInterval {
        return new TimeInterval($this, $until);
    }

    /**
     * Is this time before...
     * 
     * @param mixed $other Time or string
     * 
     * @param bool
     */
    public function isBefore($other) : bool {
        $t = $other;
        if (is_string($t)) {
            $t = new Time($t);
        }

        $diff = $this->since($t)->seconds;
        return $diff < 0;
    }

}

class TimeInterval {

    /**
     * Origin time
     * 
     * @var Time $__origin
     */
    private Time $__origin;

    /**
     * Destination time
     * 
     * @var Time $__destin
     */
    private Time $__destin;

    /**
     * Difference
     * 
     * @var $__diff
     */
    private $__diff;

    public function __construct($since, $until = null) {
        $this->__origin = $since;
        $this->__destin = $until == null ? new Time() : $until;
        $this->__diff = $this->__destin->datetime->diff($this->__origin->datetime);
    }

    /**
     * Invert the interval direction.
     * 
     * @return TimeInterval self
     */
    public function invert() : TimeInterval {
        $aux = $this->__origin;
        $this->__origin = $this->__destin;
        $this->__destin = $aux;
        $this->__diff = $this->__destin->datetime->diff($this->__origin->datetime);
        return $this;
    }

    public function __get($name) {

        if ($name == 'seconds') {
            return $this->__destin->timestamp - $this->__origin->timestamp;
        }
        else if ($name == 'minutes') {
            return $this->seconds / 60;
        }
        else if ($name == 'hours') {
            return $this->minutes / 60;
        }
        else if ($name == 'days') {
            return $this->hours / 24;
        }
        else if ($name == 'weeks') {
            return $this->days / 7;
        }
        else if ($name == 'months') {
            // Years
            $count = floor($this->days / 365) * 12;
            $rest = $this->days - $count * 12;

            // Half years
            $count2 = floor($rest / 183) * 6;
            $rest = $this->days - $count*12 - $count2*6;

            // Rest 1 month = 30 days
            return $count + $count2 + ($rest/30);
        }
        else if ($name == 'years') {
            return $this->days / 365;
        }
        else if ($name == 'total') {
            return array(
                'years' => $this->__diff->y,
                'months' => $this->__diff->m,
                'days' => $this->__diff->d,
                'hours' => $this->__diff->h,
                'minutes' => $this->__diff->i,
                'seconds' => $this->__diff->s
            );
        }

    }

}