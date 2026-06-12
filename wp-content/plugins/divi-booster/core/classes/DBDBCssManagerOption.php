<?php
if (!class_exists('DBDBCssManagerOption')) {
	class DBDBCssManagerOption {

        private $option;
        
        static function fromRawOption($option) {
            if (!is_array($option)) {
                $option = array();
            }
            if (empty($option['customcss']['css'])) { 
                // set up a blank custom css box if none exists
                $boxes = DBDBCustomCssBoxes::create(); 
                $option['customcss'] = $boxes->toArray();
            } else {
                // fix checkbox vals
                $option['customcss']['enabled'] = self::processEnabledVals($option['customcss']['enabled']);
            }
            return new self($option);
        }

        private function __construct($option) {
            $this->option = $option;
        }

        public function toArray() {
            return $this->option;
        }

        // Deals with html checkbox issue where unchecked values are not submitted. Uses zeros from hidden field as divider.
        private static function processEnabledVals($orig) {
            $vals = array();
            while ($count = count($orig)) {
                if ($count>=2 and $orig[1]=='1') { // starts with 0,1 so enabled
                    $vals[]=1; 
                    $orig = array_slice($orig, 2); 
                } else { 
                    $vals[]=0; 
                    $orig = array_slice($orig,1); 
                }
            }
            return $vals;
        }
	}
}