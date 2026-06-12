<?php
if (!class_exists('DBDBETModulesFont')) {
	class DBDBETModulesFont {

        static function create() {
            return new self();
        }

        public function load_full_font() {
            add_action('et_builder_ready', array($this, 'disable_dynamic_icons'));
        }

        public function disable_dynamic_icons() {
            global $shortname;
            if (empty($shortname)) { return; }
            $option = "et_get_option_et_{$shortname}_{$shortname}_dynamic_icons";
            if (function_exists('is_child_theme') && is_child_theme()) {
                $option .= '_child_theme';
            }
            add_filter($option, array($this, 'return_off'));
        }

        public function return_off() {
            return 'off';
        }
    }
}