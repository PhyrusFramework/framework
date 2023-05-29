<?php

class DBType {
    const BOOL = 'TINYINT(1)';
    const NUMBER = 'INT';
    const BIG_NUMBER = 'BIGINT';
    const SMALL_NUMBER = 'TINYINT';
    const SMALL_TEXT = 'VARHCAR(50)';
    const MEDIUM_TEXT = 'VARCHAR(255)';
    const LARGE_TEXT = 'TEXT';
    const DATETIME = 'DATETIME';
    const DATE = 'DATE';
    const TIME = 'TIME';
    const DECIMAL2 = 'FLOAT(12, 2)';
    const DECIMAL3 = 'FLOAT(13, 3)';
    const DECIMAL4 = 'FLOAT(14, 4)';
}