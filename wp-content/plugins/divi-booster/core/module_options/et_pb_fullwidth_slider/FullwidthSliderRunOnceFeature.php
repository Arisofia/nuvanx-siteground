<?php

namespace DiviBooster\DiviBooster;

FullwidthSliderRunOnceFeature::create()->init();

class FullwidthSliderRunOnceFeature {

    private $wp;

    static function create(\DBDBAnyWp $wp=null) {
        $wp = is_null($wp)?\DBDBWp::create():$wp;
        return new self($wp);
    }

    private function __construct(\DBDBAnyWp $wp) {
        $this->wp = $wp;
    }

    public function init() {
        if (function_exists('add_filter')) {
            add_filter('dbmo_et_pb_fullwidth_slider_whitelisted_fields', array($this, 'appendFieldSlugs'));
            add_filter('dbmo_et_pb_slider_whitelisted_fields', array($this, 'appendFieldSlugs'));
            add_filter('dbmo_et_pb_fullwidth_slider_fields', array($this, 'appendFields'));
            add_filter('dbmo_et_pb_slider_fields', array($this, 'appendFields'));
            add_filter('db_pb_fullwidth_slider_content', array($this, 'appendFullwidthSliderScript'), 10, 2);
            add_filter('db_pb_slider_content', array($this, 'appendSliderScript'), 10, 2);
        }
    }

    public function appendFullwidthSliderScript($content, $args) {
        if (!isset($args['auto']) || $args['auto'] !== 'on') return $content;
        if (!isset($args['db_run_once']) || $args['db_run_once'] !== 'on') return $content;
        $order_class = divibooster_get_order_class_from_content('et_pb_fullwidth_slider', $content);
	    if (!$order_class) { return $content; }
        $js = '<script>
        jQuery(function($) {
            var sliderClass = '.json_encode('.'.$order_class).'; 
            var $slider = $(sliderClass);
            var id = setInterval(
                function() {
                    if($slider.find(".et_pb_slide:last-child").is(":visible")) {
                        clearInterval(id);
                        $slider.removeClass("et_slider_auto_ignore_hover");
                        $slider.trigger("mouseenter");
                        $slider.off("mouseenter mouseleave");
                    } 
                }, 
                1000
            );
        });
        </script>';
        return $content.$js;
    }

    public function appendSliderScript($content, $args) {
        if (!isset($args['auto']) || $args['auto'] !== 'on') return $content;
        if (!isset($args['db_run_once']) || $args['db_run_once'] !== 'on') return $content;
        $order_class = divibooster_get_order_class_from_content('et_pb_slider', $content);
	    if (!$order_class) { return $content; }
        $js = '<script>
        jQuery(function($) {
            var sliderClass = '.json_encode('.'.$order_class).'; 
            var $slider = $(sliderClass);
            var id = setInterval(
                function() {
                    if($slider.find(".et_pb_slide:last-child").is(":visible")) {
                        clearInterval(id);
                        $slider.removeClass("et_slider_auto_ignore_hover");
                        $slider.trigger("mouseenter");
                        $slider.off("mouseenter mouseleave");
                    } 
                }, 
                1000
            );
        });
        </script>';
        return $content.$js;
    }

    public function appendFields($fields) {
        $fields['db_run_once'] = array(
			'label' => 'Run Animation Once',
			'type' => 'yes_no_button',
			'options' => array(
				'off' => $this->wp->esc_html__( 'No', 'et_builder' ),
				'on'  => $this->wp->esc_html__( 'Yes', 'et_builder' ),
			),
			'option_category' => 'configuration',
			'description' => (string) \DBDBModuleFieldDescription::create($this->wp, 'Enable this option to stop the automatic animation once it reaches the final slide.'),
			'default' => 'off',
			'tab_slug' => 'advanced',
			'toggle_slug' => 'animation',
            'show_if' => array(
                'auto' => 'on'
            )
		);	
        return $fields;
    }

    public function appendFieldSlugs($slugs) {
        $slugs[] = 'db_run_once';
        return $slugs;
    }
}