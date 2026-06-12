<?php 
namespace DiviBooster\PLUGIN\shared;

add_action('et_builder_ready', __NAMESPACE__.'\\add_module');
add_filter('et_required_module_assets', __NAMESPACE__.'\\load_button_styles');

function load_button_styles($modules) {
    $modules[] = 'et_pb_button';
    return $modules;
}

function add_module() {

	class PopupButtonModule extends \ET_Builder_Module_Button {

        function init() {
            if (is_callable('parent::init')) {
				parent::init(); 
			}
            add_action('wp_footer', array($this, 'handle_button_click'));
            add_action('wp_head', array($this, 'add_popup_styles'));
        }

		function render($attrs, $content, $render_slug) {
            $popup_content = $this->render_popup_content($attrs, $content, $render_slug);
            // Ensure button props exist
            $this->props = wp_parse_args(
                $this->props, 
                array(
                    'button_url' => '',
                    'button_rel' => '',
                    'url_new_window' => '',
                    'custom_button' => ''
                )
            );
			$button = is_callable('parent::render')?parent::render($attrs, $content, 'et_pb_button'):'';
            return sprintf(
                '<div class="%1$s_popup_wrapper"><div class="%1$s_popup_inner"><div class="%1$s_popup_close"><span class="%1$s_popup_close_icon">X</span></div>%2$s</div></div>%3$s',
                esc_attr($render_slug),
                $popup_content,
                $button
            );
		}

        function render_popup_content($attrs, $content, $render_slug) {
            return '<span>Popup content</span>';
        }

        public function get_fields() {
			$fields = is_callable('parent::get_fields')?parent::get_fields():array();
            if (isset($fields['button_text']) && is_array($fields['button_text'])) {
                $fields['button_text']['default'] = esc_html__('Click Here', 'et_builder');
            }
            if (isset($fields['button_url']) && is_array($fields['button_url'])) {
                $fields['button_url']['type'] = 'hidden';
            }
            if (isset($fields['url_new_window']) && is_array($fields['url_new_window'])) {
                $fields['url_new_window']['type'] = 'hidden';
            }
			return $fields;
		}

        public function get_advanced_fields_config() {
            return array(
                'button' => array(
                    'button' => array(
                        'label'          => 'Button',
                        'css'            => array(
                            'main'         => "{$this->main_css_element} .et_pb_button",
                            'limited_main' => "{$this->main_css_element} .et_pb_button",
                        ),
                        'box_shadow'     => false,
                        'margin_padding' => false,
                    ),
                ),      'borders'         => array(
                    'default' => false,
                ),
                'margin_padding'  => array(
                    'css' => array(
                        'padding'   => "{$this->main_css_element}_wrapper {$this->main_css_element}, {$this->main_css_element}_wrapper {$this->main_css_element}:hover",
                        'margin'    => "{$this->main_css_element}_wrapper",
                        'important' => 'all',
                    ),
                ),
                'text'            => array(
                    'use_text_orientation'  => false,
                    'use_background_layout' => true,
                    'options'               => array(
                        'background_layout' => array(
                            'default_on_front' => 'light',
                            'hover'            => 'tabs',
                        ),
                    ),
                ),
                'text_shadow'     => array(
                    // Text Shadow settings are already included on button's advanced style
                    'default' => false,
                ),
                'background'      => false,
                'fonts'           => false,
                'max_width'       => false,
                'height'          => false,
                'link_options'    => false,
                'position_fields' => array(
                    'css' => array(
                        'main' => "{$this->main_css_element}_wrapper",
                    ),
                ),
                'transform'       => array(
                    'css' => array(
                        'main' => "{$this->main_css_element}_wrapper",
                    ),
                )
            );
        }

        public function handle_button_click() { ?>
            <script>
            jQuery(function($){
                $('a.<?php esc_attr_e($this->slug); ?>').click(function(e){
                    e.preventDefault();
                    $(this).closest('.et_pb_module_inner').find('.<?php esc_attr_e($this->slug); ?>_popup_wrapper').show();
                    $('body').addClass('dbarm_popup_visible');
                });
                
                // Close if overlay background clicked
                $('.<?php esc_attr_e($this->slug); ?>_popup_wrapper').click(function(event){
                    var $target = $(event.target);
                    if ($target.is('.<?php esc_attr_e($this->slug); ?>_popup_wrapper')) {
                        $(this).hide();
                        $('body').removeClass('dbarm_popup_visible');
                    }
                });
                // Close if close button clicked
                $('.<?php esc_attr_e($this->slug); ?>_popup_close_icon').click(function(event){
                    $(this).closest('.<?php esc_attr_e($this->slug); ?>_popup_wrapper').hide();
                    $('body').removeClass('dbarm_popup_visible');
                });
                
            });
            </script>
            <?php	
        }

        public function add_popup_styles() {
            $slug = esc_html($this->slug);
            echo <<<END
<style>
.{$slug}_popup_wrapper {
    display: none;
}
.{$slug}_popup_wrapper {
    background-color: rgba(0, 0, 0, 0.6);
    z-index: 100000;
    position: fixed;
    inset: 0;
    text-align: center;
}

.{$slug}_popup_wrapper .arm_setup_form_container {
    display: inline-block !important;
}
.dbarm_popup_visible #main-content .et_builder_inner_content {
    z-index: 100000;
}
.{$slug}_popup_inner {
    display: inline-block;
    overflow-y: scroll;
    max-height: 80vh;
    margin-top: 10vh;
}
.{$slug} .arm_setup_form_container {
    padding: 0 40px 40px 40px;
}


.{$slug}_popup_content {
    background-color: white;
}

/* close icon */
.{$slug}_popup_close {
    background-color: white;
    text-align: right;
}
.{$slug}_popup_close_icon {
    color: black;
    font-size: 20px;
    padding: 8px 12px;
    line-height: 40px;
    cursor: pointer;
}

@media only screen and (max-width: 980px) {
    .{$slug}_popup_inner {
        max-width: 100% !important;
    }
    .{$slug} .arm_setup_form_container {
        max-width: 100% !important;
    }
    .{$slug} .arm_setup_form_container form {
        max-width: 100%;
        width: 100%;
    }
    .{$slug}_popup_inner {
        max-height: 100% !important;
        margin-top: 0 !important;
    }
}
</style>
END;
        }
        
    }

}