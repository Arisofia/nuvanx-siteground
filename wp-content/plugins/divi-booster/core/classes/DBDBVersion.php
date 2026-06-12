<?php
class DBDBVersion {

    private $version;
 
    static function create($version) {
        return new self($version);
    }

    private function __construct($version) {
        $this->version = $version;        
    }

    public function newerThan($version) {
        return version_compare($this->version, $version, '>');
    }
}