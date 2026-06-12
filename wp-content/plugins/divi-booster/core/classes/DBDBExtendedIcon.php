<?php

if (!class_exists('DBDBExtendedIcon')) {
	
	class DBDBExtendedIcon {
		
        private $wp;
		private $id;
		private $url;		
		
		public function __construct($id, $url='', $wp=null) {
			$this->id = $id;
			$this->url = $url;
            $this->wp = is_null($wp)?\DBDBWp::create():$wp;
		}
		
		public function init() {
			$fontSymbolsPriority = apply_filters('DBDBExtendedIcon_font_icon_symbols_priority', 50);
			add_filter('et_pb_font_icon_symbols', array($this, 'addToFontSymbols'), $fontSymbolsPriority);
			add_filter('dbdb_get_extended_font_icon_symbols', array($this, 'add_to_extended_fonts'));
            add_action('db_admin_css', array($this, 'iconPickerCss'));
            add_action('db_vb_css', array($this, 'iconPickerCss'));
			add_action('wp_footer', array($this, 'outputIconUpdateJs'));
			add_action('wp_head', array($this, 'db014_user_css_for_custom_button_icon'));
            add_filter('dbdb_et_pb_get_font_down_icon_symbols', array($this, 'add_to_down_icons'));
		}

        public function add_to_down_icons($symbols) {
            $symbols[] = esc_html($this->unicode());
            return $symbols; 
        }

        public function add_to_extended_fonts($symbols) {
            $custom_icon = array(
                array(
                    "search_terms"=>"divi-booster custom-icon",
                    "unicode"=>$this->unicode(),
                    "name"=>"Divi Booster Custom Icon",
                    "styles"=>array("divi","solid"),
                    "is_divi_icon"=>true,
                    "font_weight"=>400
                )
            );
            return array_merge($custom_icon, $symbols); 
        }

        public function unicode() {
            return "&#x".$this->unicode_value().";";
        }

        public function unicode_value() {
            // Assign this icon to unicode value (one not used by other Divi icons)
            $offset = 800; // Start unicode value, chosen to give unused block
            $code = $offset + $this->id;
            return $code;
        }

        private function id() {
            return "wtfdivi014-url{$this->id}";
        }
		
		public function db014_user_css_for_custom_button_icon() { 
			$id = $this->id();
			$url = $this->url;
			$icon = '.et_pb_custom_button_icon[data-icon="'.esc_html($id).'"]';
            $extended_icon = '.et_pb_button[data-icon="'.html_entity_decode($this->unicode(), ENT_QUOTES, 'UTF-8').'"]';
			$bg_img = empty($url)?'none':"url('".esc_html($url)."')";
			echo <<<END
			<style>
			$icon:before, 
			$icon:after,
            $extended_icon:before,
            $extended_icon:after {
				background-image: $bg_img;		
			}
			</style>
END;

			$is_svg = preg_match('#\.svg(\?[^.]*)?$#', $url);
			if ($is_svg) {
				// IE SVG background-size (as "auto" not supported) 
				// - width = half the 2em padding allocated for icon, and 50% height of button
				echo <<<END
				<style>
				body.ie $icon:before, 
				body.ie $icon:after,
                body.ie $extended_icon:before,
                body.ie $extended_icon:after {
					background-size: 1em 50%; 	
				}
				</style>
END;
			}
		}
		
		public function addToFontSymbols($fontSymbols) {
			$fontSymbols[] = $this->id();
			return $fontSymbols;
		}
		
		public function outputIconUpdateJs() { 
            $encoded_id = json_encode($this->id());
            $encoded_url = json_encode($this->url);
            $encoded_unicode = json_encode(html_entity_decode($this->unicode(), ENT_QUOTES, 'UTF-8'));
            ?>
			<script>
			jQuery(function($){
                
                function db014_update_all_icons() {
                    jQuery('.db-custom-icon:not(:has(.dbdb-custom-icon-img))').removeClass('db-custom-icon'); // Clear class on non-custom icons
                    jQuery('.et_pb_button[data-icon=<?php echo $encoded_unicode; ?>]').addClass('db-custom-extended-icon');
                    db014_update_icon(<?php echo $encoded_id; ?>, <?php echo $encoded_url; ?>); 
                    db014_update_icon(<?php echo $encoded_unicode; ?>, <?php echo $encoded_url; ?>);
                }

				<?php if (!function_exists('et_fb_enabled') || !et_fb_enabled()) { ?>
				setTimeout(
					function() { 
                        db014_update_all_icons();
					}, 100
				);
				<?php } ?>
				$(document).on('db_vb_custom_icons_updated', function () {
                    db014_update_all_icons();
				});
				$('#et-main-area, .et_pb_module').on('mouseover mouseout', function () {
					setTimeout(
						function() { 
                            db014_update_all_icons();
						},
						0
					);
				});
			});
            
			</script>
			<?php
		}		
		
		public function iconPickerCss() {
			$url = $this->wp->esc_html($this->url);
			$id = $this->wp->esc_attr($this->id());
            $utf = $this->unicode();
            $unicode_val = '\\'.$this->unicode_value();
			echo <<<END
            #et-fb-icon_picker li[data-icon-utf="{$utf}"]:after,
            #et-fb-scroll_down_icon li[data-icon="{$unicode_val}"]:after,
			.et-fb-option--select-icon li[data-icon="{$id}"]:after,
			.et-pb-option--select_icon li[data-icon="{$id}"]:before,
			.et-pb-option ul.et_font_icon li[data-icon="{$id}"]::before { 
				background: url('{$url}') no-repeat center center; 
				background-size: cover; 
				content: 'a' !important; 
				width: 16px !important; 
				height: 16px !important; 
				color: rgba(0,0,0,0) !important; 
			}
END;
		}		
		
	}
}
