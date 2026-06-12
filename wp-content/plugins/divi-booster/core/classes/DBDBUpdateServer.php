<?php
if (!class_exists('DBDBUpdateServer')) {
	class DBDBUpdateServer {
	 
		private $url;
        private $wp;
	 
		static function create(DBDBAnyWp $wp=null) {
            $wp = is_null($wp)?DBDBWp::create():$wp;
			return new self($wp);
		}
		
		private function __construct(DBDBAnyWp $wp) {
			$this->url = 'https://d3mraia2v9t5x8.cloudfront.net';
            $this->wp = $wp;
		}
		
		public function url($path='') {
			return $this->url.'/'.$path;
		}
		
		public function updatesUrl() {
			return $this->wp->apply_filters('dbdb_update_url', $this->url('updates.json'));
		}
	}
}