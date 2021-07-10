<?php

class EventListenerTest extends Test {

    public function run() {

        EventListener::on('test', function(&$arg) {
            $arg[] = 'A';
        });

        $list = [];
        EventListener::trigger('test', $list);

        $this->is($list, ['A'], 'Checking list after Event trigger');

    }

}

new EventListenerTest();