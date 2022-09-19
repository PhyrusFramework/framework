<?php

class DBCondition {

    private array $blocks = [];

    private string $next = 'AND';
    private DBCondition $current;
    private $parent;

    function __construct($parent = null)
    {
        if ($parent == null) {
            $b = ['AND', new DBCondition($this)];
            $this->blocks[] = $b;
            $this->current = $b[1];
        } else {
            $this->current = $this;
        }
        $this->parent = $parent;
    }

    public function add($condition) {
        if ($this->current == $this)
            $this->blocks[] = ['AND', $condition];
        else
            $this->current->add($condition);
    }

    public function openBlock($andOr) {
        if ($this->current == $this) {
            $b = [$andOr, new DBCondition($this)];
            $this->blocks[] = $b;
            $this->current = $b[1];
        } else
            $this->current->openBlock($andOr);
    }

    public function closeBlock() {
        if ($this->current == $this) {
            if ($this->parent != null) {
                $this->parent->current = $this->parent;
            }
        } else {
            $this->current->closeBlock();
        }
    }

    public function notEmpty() {
        return sizeof($this->blocks) > 0;
    }

    public function toString(&$params, $counter = 0) {
        if (!$this->notEmpty()) {
            return ['', $counter];
        }

        $str = '';
        $started = false;

        $c = $counter + 1;

        foreach($this->blocks as $block) {

            $op = $block[0];
            $cond = $block[1];

            if ($started) {
                $str .= " $op ";
            }
            $started = true;

            if (is_array($cond)) {

                $type = $cond['type'];

                if ($type == 'simple') {
                    $col = $cond['column'];
                    $op = $cond['operator'];
                    $v = $cond['value'];

                    $str .= "$col $op :p_" . $c;
                    $params["p_" . $c] = $v;
                    $c += 1;
                }
                else if ($type == 'wherein') {
                    $col = $cond['column'];
                    $in = $cond['in'];
                    $sub = $cond['subquery'];
                    $sub = $sub->buildQuery('select', $c);

                    $c = $sub[2];
                    $str .= "$col " . ($in ? 'IN ' : 'NOT IN ');
                    $str .= '(' . $sub[0] . ')';

                    // Params
                    foreach($sub[1] as $k => $v) {
                        $params[$k] = $v;
                    }
                }
                else if ($type == 'raw') {
                    $q = $cond['query'];
                    $pms = $cond['params'];

                    $str .= $q;
                    foreach($pms as $k => $v) {
                        $params[$k] = $v;
                    }
                }

            } else {
                $subcond = $cond->toString($params, $c);
                $str .= $subcond[0];
                $c = $subcond[1];
            }

        }

        
        if (sizeof($this->blocks) > 1) {
            $str = "($str)";
        }

        return [$str, $c];
    }

}