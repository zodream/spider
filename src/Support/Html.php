<?php
namespace Zodream\Spider\Support;

class Html {

    /**
     * @var string
     */
    protected $html;

    public function __construct($html) {
        $this->html = $html;
    }

    /**
     * @param $begin
     * @param null $end
     * @return Html
     */
    public function sub($begin, $end = null) {
        return new static($this->subValue($begin, $end));
    }

    /**
     * @param $begin
     * @param null $end
     * @return string
     */
    public function subValue($begin, $end = null) {
        if (!empty($begin) && !is_integer($begin)) {
            $begin = stripos($this->html, $begin);
        }
        $begin = intval($begin);
        if (!empty($end) && !is_integer($end)) {
            $end = stripos($this->html, $end, $begin);
        }
        $end = intval($end);
        if ($end > 0 && $begin >= $end) {
            return '';
        }
        if ($end <= 0) {
            return substr($this->html, $begin);
        }
        return substr($this->html, $begin, $end - $begin);
    }

    /**
     * @param $pattern
     * @param callable|null $func
     * @return $this|Collection
     */
    public function matches($pattern, callable $func = null) {
        if (!preg_match_all($pattern, $this->html, $matches, PREG_SET_ORDER)) {
            $matches = [];
        }
        if (empty($func)) {
            return new Collection($matches);
        }
        foreach ($matches as $match) {
            call_user_func($func, $match);
        }
        return $this;
    }

    /**
     * @param $pattern
     * @param callable|null $func
     * @return $this|Collection
     */
    public function match($pattern, callable $func = null) {
        if (!preg_match($pattern, $this->html, $match)) {
            $match = [];
        }
        if (empty($func)) {
            return new Collection($match);
        }
        call_user_func($func, $match);
        return $this;
    }

    /**
     * @param $pattern
     * @param int $index
     * @return string
     */
    public function matchValue($pattern, $index = 0) {
        if (!preg_match($pattern, $this->html, $match)) {
            return '';
        }
        if (isset($match[$index])) {
            return $match[$index];
        }
        return '';
    }

    public function __toString() {
        return $this->html;
    }
}