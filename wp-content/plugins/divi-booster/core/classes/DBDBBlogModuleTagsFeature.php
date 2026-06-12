<?php

if (!class_exists('DBDBBlogModuleTagsFeature')) {
	
	class DBDBBlogModuleTagsFeature {

        static function create() {
            return new self();
        }

        public function articleId($article) {
            $match = false;
            preg_match('/<article id="post-(\d*)"/', $article, $match);
            if (isset($match[1])) {
                return intval($match[1]);
            }
            return false;
        }

        public function mapArticles($content, $fn) {
            $open = preg_quote('<article', '/');
            $close = preg_quote('</article>', '/');
            return preg_replace_callback('/'.$open.'.*?'.$close.'/', $fn, $content);
        }
    }
		
}