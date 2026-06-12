<?php
if (!class_exists('DBDBCustomCssBoxes')) {
	class DBDBCustomCssBoxes {

        private $boxes;

        static function create() {
            return self::fromArray(
                array(
                    'css'=>array(''),
                    'enabled'=>array(1),
                    'mediaqueries'=>array('all')
                )
            );
        }

        static function fromArray($boxes) {
            return new self($boxes);
        }
	 
		private function __construct($boxes) {
            $this->boxes = $boxes;
        }

        public function toArray() {
            return $this->boxes;
        }
	}
}