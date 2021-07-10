<?php

class PromiseTest extends Test {

    function run() {

        Promise::create(function($resolve, $reject) {

            if (5 > 2) {
                $resolve('works');
            } else {
                $reject();
            }

        })
        ->then(function($arg) {
            $this->is($arg, 'works', 'Checking first promise');
        })
        ->catch(function() {
            $this->addError('Promise 1 should not be rejected');
        });

        // Causing error on purpose
        Promise::create(function($resolve, $reject) {

            $obj = null;
            $obj->doSomething();

        })
        ->then(function($arg) {
            $this->addError('Promise 2 should not work');
        })
        ->catch(function($e) {
            $this->is($e instanceof Throwable, true, 'Checking if error is exception');
        });

    }

}

new PromiseTest();