<?php

class TimeTest extends Test {

    public function run() {

        $time = new Time('2020-06-17 20:05');

        $tests = [
            'Y' => '2020',
            'y' => '20',
            'm' => '06',
            'n' => '6',
            'M' => 'Jun',
            'F' => 'June',
            't' => '30', // Days in this month
            'd' => '17',
            'D' => 'Wed',
            'N' => '3',  // day of week
            'H' => '20',
            'h' => '08',
            'i' => '05',
            's' => '00',
            'S' => 'th',
            'j' => '17',
            'l' => 'Wednesday',
            'z' => '168',  // The day of the year
            'a' => 'pm',
            'g' => '8'
        ];

        foreach($tests as $format => $expected) {
            $this->is(
                $time->format($format),
                $expected,
                "Format $format"
            );
        }

        $tests = [
            'time' => '20:05:00',
            'date' => '17/06/2020',
            'datetime' => '17/06/2020 20:05',
            'string' => 'Wednesday 17th June 2020',
            'day of year' => '168'
        ];

        foreach($tests as $format => $expected) {
            $this->is(
                $time->get($format),
                $expected,
                "Get predefined format $format"
            );
        }

        $tests = [
            'day' => 17,
            'month' => 6,
            'year' => 2020,
            'hour' => 20,
            'minute' => 05,
            'second' => 0
        ];

        foreach($tests as $format => $expected) {
            $this->is(
                $time->{$format},
                $expected,
                "Get date value $format"
            );
        }

        $time
        ->setMonth(7)
        ->setDay(31)
        ->setYear(2019)
        ->setHour(15)
        ->setMinute(23)
        ->setSecond(8);

        $tests = [
            'day' => 31,
            'month' => 7,
            'year' => 2019,
            'hour' => 15,
            'minute' => 23,
            'second' => 8
        ];

        foreach($tests as $format => $expected) {
            $this->is(
                $time->{$format},
                $expected,
                "After change, get date value $format"
            );
        }

        $time
        ->add(1, 'day')
        ->add(2, 'hour')
        ->add(-4, 'minute')
        ->add(-1, 'second')
        ->add(1, 'month')
        ->add(-50, 'year');

        $tests = [
            'day' => 1,
            'month' => 9,
            'year' => 1969,
            'hour' => 17,
            'minute' => 19,
            'second' => 7
        ];

        foreach($tests as $format => $expected) {
            $this->is(
                $time->{$format},
                $expected,
                "After add, get date value $format"
            );
        }

    }

}
new TimeTest();