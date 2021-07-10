<?php

class DBQueryResult
{
    /**
     * Executed query
     * 
     * @var string $query
     */
    public ?string $query;

    /**
     * Query error. Empty if not error.
     * 
     * @var string $error
     */
    public ?string $error;

    /**
     * Query result.
     * 
     * @var array $result
     */
    public array $result = [];

    /**
     * If at least one row was returned.
     * 
     * @var bool $something
     */
    public bool $something = false;

    /**
     * First row.
     * 
     * @var Generic $first
     */
    public ?Generic $first;

    /**
     * Number of returned rows.
     * 
     * @var int $count
     */
    public int $count;

    public function __construct(string $q, ?string $e, array $r) {
        $this->query = $q;
        $this->error = $e;

        $res = [];
        foreach($r as $re)
        {
            $res[] = new Generic($re);
        }
        $this->result = $res;
        $this->count = sizeof($res);
        $this->something = ($this->count > 0);
        $this->first = null;
        if ($this->something)
            $this->first = $res[0];
    }

    /**
     * If the query caused an error.
     * 
     * @return bool
     */
    public function caused_error() : bool {
        return !empty($this->error);
    }

    /**
     * Generates an empty DBQueryResult
     * 
     * @return DBQueryResult
     */
    public static function empty() : DBQueryResult {
        return new DBQueryResult('', '', []);
    }
}