<?php

class DBConditionGroup {

    public string $connector = 'AND';
    private array $conditions = [];
    private string $raw = '';

    /**
     * Add a new condition to the group.
     * 
     * @param string Column
     * @param string Operator
     * @param mixed Value
     * @param string AND or OR
     * @param bool If value is string, wrap with quotes?
     */
    private function add(string $column, string $operator, $value, string $connector = 'AND', bool $wrapQuotes = true) {
        $cond = new DBCondition();
        $cond->column = $column;
        $cond->operator = $operator;
        $cond->value = $value;
        $cond->connector = $connector;
        $cond->useQuotes = $wrapQuotes;
        $this->conditions[] = $cond;
    }

    /**
     * Add a group to the list.
     * 
     * @param DBConditionGroup
     */
    public function addGroup(DBConditionGroup $group) {
        $this->conditions[] = $group;
    }

    /**
     * Create a DBConditionGroup using a raw SQL condition.
     * 
     * @param string Raw SQL condition.
     * 
     * @return DBConditionGroup
     */
    public static function fromRaw(string $query) : DBConditionGroup {
        $group = new DBConditionGroup();
        $group->raw = $query;
        return $group;
    }

    /**
     * Get last added condition.
     * 
     * @return DBCondition|DBConditionGroup
     */
    public function last() : DBCondition|DBConditionGroup {
        if (empty($this->conditions)) return null;
        return $this->conditions[count($this->conditions) - 1];
    }

    /**
     * Add a condition
     * 
     * @param string|callable Column name or group of conditions
     * @param mixed value or operator
     * @param mixed value if operator is used
     * 
     * @return DBConditionGroup self
     */
    public function where(string|callable $column, $valueOrOperator = '=', $value = null) : DBConditionGroup {

        if (is_closure($column)) {
            $group = new DBConditionGroup();
            $column($group);
            $this->addGroup($group);
            return $this;
        }
        //////////////////////////
        // If column is string:

        $useQuotes = true;

        $operator = $value === null ? '=' : $valueOrOperator;
        $val = $value === null ? $valueOrOperator : $value;
        if ($valueOrOperator === null) {
            $operator = 'IS';
            $val = null;
        }
        else if (is_string($valueOrOperator) && 
        in_array(strtolower($valueOrOperator), ['is', 'is not']) && 
        $value === null) {
            $operator = $valueOrOperator;
            $val = null;
        }
        
        if (is_array($val) && $operator == '=') {
            $operator = 'IN';
        }
        else if ($val instanceof DBQuery) {
            $val = InsecureString::instance('(' . $val->toString() . ')');
            $useQuotes = false;
        }

        $this->add(
            $column,
            $operator,
            $val,
            'AND',
            $useQuotes
        );

        return $this;

    }

    /**
     * Add a condition
     * 
     * @param string|callable Column name or group of conditions
     * @param mixed value or operator
     * @param mixed value if operator is used
     * 
     * @return DBConditionGroup self
     */
    public function orWhere(string|callable $column, $valueOrOperator = '=', $value = null) : DBConditionGroup {

        $this->where($column, $valueOrOperator, $value);
        $this->last()->connector = 'OR';

        return $this;
    }

    /**
     * Add a raw SQL condition.
     * 
     * @param string SQL Condition
     * 
     * @return DBConditionGroup
     */
    public function whereRaw(string $sql) : DBConditionGroup {
        $this->addGroup(DBConditionGroup::fromRaw($sql));
        return $this;
    }

    /**
     * Add a raw SQL condition.
     * 
     * @param string SQL Condition
     * 
     * @return DBConditionGroup
     */
    public function orWhereRaw(string $sql) : DBConditionGroup {
        $this->addGroup(DBConditionGroup::fromRaw($sql));
        $this->last()->connector = 'OR';
        return $this;
    }

    /**
     * Add condition where column is in list or subquery.
     * 
     * @param string Column name
     * @param array|DBQuery List or subquery
     * 
     * @return DBConditionGroup
     */
    public function whereIn(string $column, array|DBQuery $value) : DBConditionGroup {

        if (is_array($value)) {
            return $this->where($column, 'IN', $value);
        }

        return $this->whereRaw(Database::columnName($column) . ' IN (' . $value->toString() . ')');
    }

    /**
     * Add condition where column is NOT in list or subquery.
     * 
     * @param string Column name
     * @param array|DBQuery List or subquery
     * 
     * @return DBConditionGroup
     */
    public function whereNotIn(string $column, array|DBQuery $value) : DBConditionGroup {

        if (is_array($value)) {
            return $this->where($column, 'NOT IN', $value);
        }

        return $this->whereRaw(Database::columnName($column) . ' NOT IN (' . $value->toString() . ')');
    }

    /**
     * Add condition where column is in list or subquery.
     * 
     * @param string Column name
     * @param array|DBQuery List or subquery
     * 
     * @return DBConditionGroup
     */
    public function orWhereIn(string $column, array|DBQuery $value) : DBConditionGroup {
        $this->whereIn($column, $value);
        $this->last()->connector = 'OR';
        return $this;
    }

    /**
     * Add condition where column is NOT in list or subquery.
     * 
     * @param string Column name
     * @param array|DBQuery List or subquery
     * 
     * @return DBConditionGroup
     */
    public function orWhereNotIn(string $column, array|DBQuery $value) : DBConditionGroup {
        $this->whereNotIn($column, $value);
        $this->last()->connector = 'OR';
        return $this;
    }

    /**
     * Create condition string.
     * 
     * @return string
     */
    public function toString(bool $showLogicOperator = false) : string {

        $pS = '(';
        $pE = ')';
        if (count($this->conditions) < 2) {
            $pS = $pE = '';
        }

        $str = $showLogicOperator ? " $this->connector $pS" : $pS;

        if (!empty($this->raw)) {
            $str .= "($this->raw)" . $pE;
            return $str;
        }

        if (count($this->conditions) == 0) {
            return '';
        }

        $first = true;
        foreach($this->conditions as $con) {

            $str .= $con->toString(!$first);

            $first = false;
        }

        return $str . $pE;
    }

}