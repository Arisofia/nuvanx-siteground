<?php

if (!class_exists('DBDBGallery')) {
	class DBDBGallery {
		
		private $gridItem = '.et_pb_gallery_item.et_pb_grid_item';
		
		// Apply gallery options
		function db_pb_gallery_filter_content($content, $args) {
			
			// Handle presets
			if (class_exists('ET_Builder_Global_Presets_Settings') && is_callable('ET_Builder_Global_Presets_Settings::instance')) {
				$preset = ET_Builder_Global_Presets_Settings::instance();
				if (is_callable(array($preset, 'get_module_presets_settings'))) {
					$defaults = $preset->get_module_presets_settings('et_pb_gallery', $args);
					$args = wp_parse_args($args, $defaults);
				}
			}
			
			// Images per row
			if (!empty($args['db_images_per_row'])) {
				
				$media_queries = array(
					'db_images_per_row'=>'(min-width: 981px)', 
					'db_images_per_row_tablet'=>'(min-width: 768px) and (max-width: 980px)', 
					'db_images_per_row_phone'=>'(max-width: 767px)'
				);
				
				foreach($media_queries as $k=>$media_query) {
					if (!empty($args[$k]) && ($num = abs(intval($args[$k])))) {
						
						$width = 100/$num;
					
						dbdb_set_module_style('et_pb_gallery', array(
							'selector'    => ".et_pb_column %%order_class%% {$this->gridItem}",
							'declaration' => 'margin-right: 0 !important; width: '.$width.'% !important; clear: none !important;',
							'media_query' => '@media only screen and '.$media_query
						));
						dbdb_set_module_style('et_pb_gallery', array(
							'selector'    => ".et_pb_column %%order_class%% {$this->gridItem}:nth-of-type({$num}n+1)",
							'declaration' => 'clear: both !important;',
							'media_query' => '@media only screen and '.$media_query
						));	
					}
				}
			}
			
			// Get the order class
			$class = divibooster_get_order_class_from_content('et_pb_gallery', $content);
			if (!$class) { 
				return $content; 
			}
			
			$css = '';
			$galleryItem = ".{$class} {$this->gridItem}";
			
			// Set defaults
			$useNewDefaults = (!empty($args['dbdb_version']) && version_compare($args['dbdb_version'], '3.2.6', '>='));
			if (!empty($args['db_images_per_row']) && !isset($args['db_image_max_width'])) {
				$args['db_image_max_width'] = $useNewDefaults?'83.5%':'100%';
			}
			if (!empty($args['db_images_per_row']) && !isset($args['db_image_row_spacing'])) {
				$args['db_image_row_spacing'] = $useNewDefaults?'5.5%':'0%';
			}
			
			// Max width
			if (!empty($args['db_image_max_width'])) {
				$media_queries = array(
					'db_image_max_width'=>'(min-width: 981px)', 
					'db_image_max_width_tablet'=>'(min-width: 768px) and (max-width: 980px)', 
					'db_image_max_width_phone'=>'(max-width: 767px)'
				);
				foreach($media_queries as $k=>$mq) {
					if (isset($args[$k])) {
						$num = esc_html($args[$k]);
						$css.="
							@media only screen and {$mq} {
								.et_pb_column {$galleryItem} .et_pb_gallery_title, 
								.et_pb_column {$galleryItem} .et_pb_gallery_image { 
									max-width: {$num}; 
									margin-left: auto !important; 
									margin-right: auto !important; 
								}
								.et_pb_column {$galleryItem} .et_pb_gallery_image img {
									width: 100%; 
								}
							}
						";	
						
					}
				}
			}
			
			// Max Height
			if (!empty($args['db_image_max_height'])) {

				$media_queries = array(
					'db_image_max_height'=>'(min-width: 981px)', 
					'db_image_max_height_tablet'=>'(min-width: 768px) and (max-width: 980px)', 
					'db_image_max_height_phone'=>'(max-width: 767px)'
				);
				foreach($media_queries as $k=>$mq) {
					if (!empty($args[$k]) && ($num = abs(intval($args[$k])))) {

						$css.="
							@media only screen and {$mq} {
								.et_pb_column {$galleryItem} .et_pb_gallery_image { 
									position: relative;
									padding-bottom: {$num}%;
									height: 0;
									overflow: hidden;
								}
								.et_pb_column {$galleryItem} .et_pb_gallery_image img { 
									position: absolute;
									top: 0;
									left: 0;
									width: 100%;
									height: 100%;
								}
							}
						";	
						
					}
				}
			}
			
			// Row spacing
			if (isset($args['db_image_row_spacing'])) {
				$media_queries = array(
					'db_image_row_spacing'=>'(min-width: 981px)', 
					'db_image_row_spacing_tablet'=>'(min-width: 768px) and (max-width: 980px)', 
					'db_image_row_spacing_phone'=>'(max-width: 767px)'
				);
				foreach($media_queries as $k=>$mq) {
					if (isset($args[$k])) {
						$num = esc_html($args[$k]);
						$css.="
							@media only screen and {$mq} {
								.et_pb_column {$galleryItem} { 
									margin-bottom: {$num} !important; 
								}
							}
						";	
						
					}
				}
			}
			
			// Center image titles
			if (!empty($args['db_image_center_titles'])) {
				
				$align = $args['db_image_center_titles'];
				
				$css.="
					/* Center titles */
					.et_pb_column {$galleryItem} .et_pb_gallery_title {
						text-align: {$align};
					}
				";
			}
			
			// Object fit
			if (!empty($args['db_image_object_fit'])) {
				
				$object_fit = $args['db_image_object_fit'];
				
				$css.= $this->dbdbGallery_objectFitCss($galleryItem, $object_fit);
			}
			
			if (!empty($css)) { $content.="<style>$css</style>"; }
			
			// Disable image cropping when image area / scaling modified
			if (!empty($args['db_image_max_height'])) {
				$content.= <<<END
				<script>jQuery(function($){
					var items = $("{$galleryItem}");
					items.each(function() {
						var href = $(this).find('a').attr('href');
						$(this).find('a > img').attr('src', href).attr('srcset', '').attr('sizes', '');
					});
					
				});
				</script>
END;
			}
			
			return $content;
		}

		function dbdbGallery_objectFitCss($galleryItemSelector, $objectFit) {
			return "{$galleryItemSelector} .et_pb_gallery_image img { object-fit: {$objectFit} !important; }";
		}
		
	}
}