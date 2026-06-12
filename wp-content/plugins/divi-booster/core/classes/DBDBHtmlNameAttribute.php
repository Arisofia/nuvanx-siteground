<?php
if (!class_exists('DBDBHtmlNameAttribute')) {
	class DBDBHtmlNameAttribute {

        private $name;
        
        static function fromString($name) {
            return new self($name);
        }

        private function __construct($name) {
            $this->name = $name;
        }

        public function withFields($field) {
            $field = ltrim($field, '[');
            $field = rtrim($field, ']');
            $field = '['.$field.']';
            return new self($this->name.$field);
        }

        public function toString() {
            return $this->name;
        }
	}
}