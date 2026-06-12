<?php
if (!class_exists('DBDBDynamicAsset')) {

    class DBDBDynamicAsset {

        private $type;
        private $handle;
        private $filename;
        private $divi;

        static function socialMediaFollowCss() {
            return self::css('dbdb-social-media-follow', 'social_media_follow.css');
        }

        static function css($handle, $filename) {
            return new self('css', $handle, $filename);
        }

        static function js($handle, $filename) {
            return new self('js', $handle, $filename);
        }

        private function __construct($type, $handle, $filename) {
            $this->type = $type;
            $this->handle = $handle;
            $this->filename = $filename;
            $this->divi = DBDBDivi::create();
        }

        public function init() {
            add_action('wp_enqueue_scripts', array($this, 'register'), 11); // Enqueue later than 10 to avoid triggering child theme enqueued stylesheet detection in et_divi_enqueue_stylesheet()
        }

        public function register() {
            if ($this->divi->supports_dynamic_assets()) { 
                if ($this->type === 'css') {
                    wp_register_style($this->handle, $this->divi->dynamic_assets_url('/css/'.$this->filename), array(), $this->divi->version() );
                }
                elseif ($this->type === 'js') {
                    wp_register_script($this->handle, $this->divi->dynamic_assets_url('/js/'.$this->filename), array( 'jquery' ), $this->divi->version(), true );
                }
            }
        }

        public function load() {
            if ($this->type === 'css') {
                wp_enqueue_style($this->handle);
            }
            elseif ($this->type === 'js') {
                wp_enqueue_script($this->handle);
            }
        }
    }
}