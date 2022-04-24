<?php

class DatabaseTest extends Test {

    public function run() {

        $table = 'tests_table';
        $def = 'ID INT NOT NULL AUTO_INCREMENT, email VARCHAR(200), createdAt DATETIME, PRIMARY KEY(ID)';

        $res = DB::query("CREATE TABLE $table ($def)");
        $this->is($res->error, null, 'Create table with raw query');

        $res = DB::query("DROP TABLE $table");
        $this->is($res->error, null, 'Delete table with raw query');

        $definition = [
            'name' => $table,
            'columns' => [
                [
                    'name' => 'ID',
                    'type' => 'INT',
                    'notnull' => true,
                    'primary' => true
                ],
                [
                    'name' => 'email',
                    'type' => 'VARCHAR(200)',
                    'unique' => true
                ],
                [
                    'name' => 'createdAt',
                    'type' => 'DATETIME'
                ]
            ]
        ];

        $error = DB::createTable($definition);
        $this->is($error, null, 'Table created with definition');

        $this->is(DB::tableExists($table), true, 'Check if table exists');

        $res = DB::insert($table, [
            'email' => 'test@gmail.com',
            'createdAt' => datenow()
        ]);
        $this->is($res->error, null, 'Insertion');

        $this->is(DB::count($table), 1, 'Count rows');
        $this->is(DB::count($table, 'email = :email', [
            'email' => 'test@gmail.com'
        ]), 1, 'Count rows with email test@gmail.com');

        $results = DB::result("SELECT * FROM $table");
        $this->is(is_array($results) && sizeof($results) == 1, true, 'Select results');

        $row = DB::row("SELECT * FROM $table WHERE email = :email", ['email' => 'test@gmail.com']);
        $this->is($row->email, 'test@gmail.com', 'Select row');

        $email = DB::value('email', $table, 'email = :email', ['email' => 'test@gmail.com']);
        $this->is($email, 'test@gmail.com', 'Select value directly');

        $res = DB::update($table, [
            'email' => 'test@gmail.com'
        ], [
            'email' => 'test2@gmail.com'
        ]);
        $this->is($res->error, null, 'Update email');

        $this->is(DB::count($table, 'email = :email', [
            'email' => 'test2@gmail.com'
        ]), 1, 'Count rows with email test2@gmail.com');


        DB::delete($table, 'email = :email', [
            'email' => 'test2@gmail.com'
        ]);

        $this->is(DB::count($table), 0, 'Count rows after deletion');

        DB::query("DROP TABLE $table");

    }

}

if (Config::get('database.forTests') != null) {
    new DatabaseTest();
}