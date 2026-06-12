<?php

class DBDBModuleFieldDescription {

    private $wp;
    private $description;

    static function create($wp, $description='') {
        return new self($wp, $description);
    }

    private function __construct($wp, $description) {
        $this->wp = $wp;
        $this->description = $description;
    }

    public function __toString() {
        return $this->description.' '.$this->wp->apply_filters('divibooster_module_options_credit', 'Added by Divi Booster');
    }
}
