<?php

class DBCondition {

    public string $column = '';
    public string $operator = '=';
    public $value = null;
    public string $connector = 'AND';
    public bool $useQuotes = true;

    public function toString(bool $showLogicOperator = true) : string {

        $str = $showLogicOperator ? " $this->connector " : '';
        $str .= Database::columnName($this->column);

        $str .= " $this->operator ";
        $str .= Database::prepare($this->value, $this->useQuotes);

        return $str;
    }

}