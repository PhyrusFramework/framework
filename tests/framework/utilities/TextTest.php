<?php

class TextTest extends Test {

    public function Run() {

        $text = Text::instance('This is a Test, brother, ');

        // Split
        $this->is(
            $text->split(' '), 
            [ 'This', 'is', 'a', 'Test,', 'brother,' ]
        , 'Split space with empty strings');

        $this->is(
            $text->split(',', false), 
            [ 'This is a Test', ' brother', ' ' ]
        , 'Split comma with empty strings');

        $this->is(
            $text->split([' ', ','], true), 
            [ 'This', 'is', 'a', 'Test', '', 'brother', '' ]
        , 'Split space and comma with empty strings');

        $this->is(
            $text->split([' ', ','], false), 
            [ 'This', 'is', 'a', 'Test', 'brother' ]
        , 'Split space and comma without empty strings');

        // Between
        $this->is(
            $text->between(',', ','),
            ' brother'
        , 'Text between commas');

        // Lower, Upper
        $this->is(
            $text->toLower(),
            'this is a test, brother, '
        , 'Text to lowercase');

        $this->is(
            $text->toUpper(),
            'THIS IS A TEST, BROTHER, '
        , 'Text to uppercase');

        $this->is(
            $text->sanitize(),
            'this-is-a-test-brother'
        , 'Sanitize');

        $this->is(
            $text->startsWith('This is '),
            true
        , 'Text starts with');

        $this->is(
            $text->startsWith('This as'),
            false
        , 'Text does not start with');

        $this->is(
            $text->endsWith('brother, '),
            true,
        'Text ends with');

        $this->is(
            $text->endsWith('brother'),
            false,
        'Text does not end with');

        $this->is(
            $text->contains('a Test'),
            true,
        'Text contains 1');

        $this->is(
            $text->contains('This is a'),
            true,
        'Text contains 2');

        $this->is(
            $text->contains('space'),
            false,
        'Text does not contain');

        $this->is(
            $text->reverse(),
            ' ,rehtorb ,tseT a si sihT',
        'Text reversed');

        $this->is(
            $text->indexOf(','),
            14,
        'Text index of');

        $this->is(
            $text->replace(', brother', ', sister'),
            'This is a Test, sister, ',
        'Text replace');

        $this->is(
            Text::similarity('Hello', 'Hallo'),
            80.0
        , 'Text similarity is 80%');

        $this->is(
            '' . Text::similarity('This as', 'This Hallo'),
            '70.588235294118',
        'Text similarity is 70.58%');

        $this->is(
            $text->trim(),
            'This is a Test, brother,',
        'Text trim');

        $this->is(
            $text->trim(" ,"),
            'This is a Test, brother',
        'Text trim');

    }

}
new TextTest();