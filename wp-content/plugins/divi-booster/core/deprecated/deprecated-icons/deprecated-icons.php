<?php

if (!function_exists('DBDBDeprecatedIcons')) {

    class DBDBDeprecatedIcons {

        private $deprecated = array('googleplus');

        function __construct() {
            if (function_exists('add_filter')) {
                add_filter('dbdb_icons_socicon_data', array($this, 'remove_from_socicon'));
                add_filter('dbdb_customizer_social_icons', array($this, 'remove_from_customizer_option'));
            }
        }

        function remove_from_socicon($networks) {
            if (is_array($networks)) {
                foreach($this->deprecated as $id) {
                    if (isset($networks[$id])) { 
                        unset($networks[$id]);
                    }
                }
            }
            return $networks;
        }
        
        function remove_from_customizer_option($icons) {
            if (is_array($icons)) {
                foreach($icons as $k=>$icon) {
                    if (in_array($icon['id'], $this->deprecated)) {
                        unset($icons[$k]);
                    }
                }
                return array_values($icons);
            }
            return $icons; 
        }
    }
    
    new DBDBDeprecatedIcons;
}