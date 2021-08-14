<?php

class ValidatorTest extends Test {

    public function run() {

        $validators = [
            'email' => Validator::for('example@test.com'),
            'text' => Validator::for('Hello world'),
            'phone' => Validator::for('+1555-555-555'),
            'number' => Validator::for(234),
            'bool' => Validator::for(true),
            'arr' => Validator::for(['prop' => 'value'])
        ];

        // Email
        $checks = [
            [
                'func' => 'isString',
                'validators' => [
                    'email' => true,
                    'text' => true,
                    'phone' => true,
                    'number' => false,
                    'bool' => false,
                    'arr' => false
                ]
            ],
            [
                'func' => 'is',
                'param' => 'example@test.com',
                'validators' => [
                    'email' => true,
                    'phone' => false
                ]
            ],
            [
                'func' => 'is',
                'param' => 234,
                'validators' => [
                    'email' => false,
                    'phone' => false,
                    'number' => true,
                    'bool' => false
                ]
            ],
            [
                'func' => 'isNot',
                'param' => true,
                'validators' => [
                    'email' => true,
                    'number' => true,
                    'bool' => false
                ]
            ],
            [
                'func' => 'isNot',
                'param' => 234,
                'validators' => [
                    'email' => true,
                    'phone' => true,
                    'number' => false,
                    'bool' => true
                ]
            ],
            [
                'func' => 'typeIs',
                'param' => 'boolean',
                'validators' => [
                    'email' => false,
                    'phone' => false,
                    'bool' => true,
                    'number' => false
                ]
            ],
            [
                'func' => 'typeIs',
                'param' => 'array',
                'validators' => [
                    'email' => false,
                    'number' => false,
                    'bool' => false,
                    'arr' => true
                ]
            ],
            [
                'func' => 'typeIsNot',
                'param' => 'boolean',
                'validators' => [
                    'email' => true,
                    'bool' => false,
                    'arr' => true
                ]
            ],
            [
                'func' => 'contains',
                'param' => '@test',
                'validators' => [
                    'email' => true,
                    'phone' => false
                ]
            ],
            [
                'func' => 'minLength',
                'param' => 3,
                'validators' => [
                    'email' => true,
                    'phone' => true,
                    'number' => true,
                    'bool' => false,
                    'bool' => false
                ]
            ],
            [
                'func' => 'minLength',
                'param' => 20,
                'validators' => [
                    'email' => false
                ]
            ],
            [
                'func' => 'maxLength',
                'param' => 5,
                'validators' => [
                    'email' => false,
                    'number' => true,
                    'arr' => true
                ]
            ],
            [
                'func' => 'maxLength',
                'param' => 20,
                'validators' => [
                    'email' => true
                ]
            ],
            [
                'func' => 'isEmail',
                'validators' => [
                    'email' => true,
                    'text' => false,
                    'phone' => false
                ]
            ],
            [
                'func' => 'hasNotUppercase',
                'validators' => [
                    'email' => true,
                    'text' => false
                ]
            ],
            [
                'func' => 'isLowercase',
                'validators' => [
                    'email' => true,
                    'text' => false
                ]
            ],
            [
                'func' => 'isUppercase',
                'validators' => [
                    'email' => false,
                    'text' => false
                ]
            ],
            [
                'func' => 'hasSpecialChars',
                'param' => 2,
                'validators' => [
                    'email' => true,
                    'text' => false,
                    'phone' => true
                ]
            ],
            [
                'func' => 'hasSpecialChars',
                'param' => 3,
                'validators' => [
                    'email' => false
                ]
            ],
            [
                'func' => 'hasNumbers',
                'validators' => [
                    'email' => false,
                    'text' => false,
                    'phone' => true
                ]
            ],
            [
                'func' => 'hasNotNumbers',
                'validators' => [
                    'email' => true,
                    'text' => true,
                    'phone' => false
                ]
            ],
            [
                'func' => 'isLetters',
                'validators' => [
                    'email' => false,
                    'text' => true,
                    'phone' => false
                ]
            ],
            [
                'func' => 'hasLetters',
                'param' => 3,
                'validators' => [
                    'email' => true,
                    'text' => true,
                    'phone' => false
                ]
            ],
            [
                'func' => 'hasNotLetters',
                'validators' => [
                    'email' => false,
                    'phone' => true
                ]
            ],
            [
                'func' => 'isPhone',
                'validators' => [
                    'email' => false,
                    'phone' => true
                ]
            ]
        ];

        foreach($checks as $check) {

            foreach($check['validators'] as $name => $expected) {
                $validator = $validators[$name];

                $validator->{$check['func']} ( isset($check['param']) ? $check['param'] : null );
    
                if ($validator->validate() != $expected) {
                    $str = "Validator for $name failed: ";
                    $str .= $check['func'] . '(';
                    $str .= isset($check['param']) ? $check['param'] : '';
                    $str .= ') was expecting ' . ($expected ? 'true' : 'false');
                    $this->addError($str);
                    return;
                }
    
                $validator->clear();
            }


        }

    }

}

new ValidatorTest();