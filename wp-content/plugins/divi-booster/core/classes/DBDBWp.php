<?php

interface DBDBAnyWp {
    static function create();
    public function get_filters($tag, $priority);
    public function add_filter($tag, $callback, $priority, $accepted_args);
    public function esc_attr($text);
    public function esc_html($text);
    public function apply_filters($tag, $value);
    public function esc_html__($text, $domain);
}

class DBDBWp implements DBDBAnyWp {

    static function create() {
        return new self();
    }

    public function get_filters($tag, $priority) {
        return array_values($GLOBALS['wp_filter'][$tag][$priority]);
    }

    public function add_filter($tag, $callback, $priority=10, $accepted_args=1) {
        return add_filter($tag, $callback, $priority, $accepted_args);
    }

    public function esc_attr($text) {
        return esc_attr($text);
    }

    public function esc_html($text) {
        return esc_html($text);
    }

    public function apply_filters($tag, $value) {
        $args = func_get_args();
        return call_user_func_array('apply_filters', $args);
    }

    public function esc_html__($text, $domain) {
        return esc_html__($text, $domain);
    }
}

class DBDBFakeWp implements DBDBAnyWp {

    private $filters = array();

    static function create() {
        return new self();
    }

    public function get_filters($tag, $priority) {
        return $this->filters[$tag][$priority];
    }

    public function add_filter($tag, $callback, $priority=10, $accepted_args=1) {
        if (!isset($this->filters[$tag])) {
            $this->filters[$tag] = array();
        }
        if (!isset($this->filters[$tag][$priority])) {
            $this->filters[$tag][$priority] = array();
        }
        $this->filters[$tag][$priority][] = array(
            'function' => $callback,
            'accepted_args' => $accepted_args
        );

    }

    public function esc_attr($text) {
        return $text;
    }

    public function esc_html($text) {
        return $text;
    }

    public function apply_filters($tag, $value) {
        if (isset($this->filters[$tag][10])) {
            foreach($this->filters[$tag][10] as $filter) {
                $value = $filter['function']($value);
            }
        }
        return $value;
    }

    public function esc_html__($text, $domain) {
        return $text;
    }
}
