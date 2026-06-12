<?php

class DBDBMagnificPopup {

    private $js;
    private $css;

    static function create() {
        return new self();
    }

    private function __construct() {
        $this->css = DBDBDynamicAsset::css('dbdb-magnific-popup', 'magnific_popup.css');
        $this->js = DBDBDynamicAsset::js('dbdb-magnific-popup', 'magnific-popup.js');
    }

    public function init() {
        $this->css->init();
        $this->js->init();
    }

    public function enqueue() {
        $this->css->load();
        $this->js->load();
    }
}