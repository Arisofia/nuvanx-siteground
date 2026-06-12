<?php
namespace DiviBooster\DiviBooster;

if (function_exists('add_filter')) {
    add_filter('init', array(new GalleryOrderFeature, 'init'));
}

class GalleryOrderFeature {

    public function init() {
        add_filter('dbdb_et_pb_module_shortcode_attributes', array($this, 'db_reverse_gallery_ids'), 10, 3);
        add_filter('et_pb_all_fields_unprocessed_et_pb_gallery', array($this, 'add_fields'));
        add_action('wp_ajax_et_pb_process_computed_property', array($this, 'apply_to_vb_preview'), 9);
    }
    
    public function apply_to_vb_preview() {
        if (empty($_POST['module_type']) || $_POST['module_type'] !== 'et_pb_gallery') { return; }
        if (empty($_POST['depends_on']['gallery_ids'])) { return; }
        if (empty($_POST['depends_on']['gallery_orderby'])) { return; }
        if ($_POST['depends_on']['gallery_orderby'] === 'dbdb_reverse') {
            $_POST['depends_on']['gallery_ids'] = implode(',', array_reverse(explode(',', $_POST['depends_on']['gallery_ids'])));
        } 
        elseif ($_POST['depends_on']['gallery_orderby'] === 'dbdb_by_id') {
            $gallery_ids = $_POST['depends_on']['gallery_ids'];
            $gallery_ids_arr = explode(',', $gallery_ids);
            sort($gallery_ids_arr);
            $_POST['depends_on']['gallery_ids'] = implode(',', $gallery_ids_arr);
        }
        elseif ($_POST['depends_on']['gallery_orderby'] === 'dbdb_by_id_reverse') {
            $gallery_ids = $_POST['depends_on']['gallery_ids'];
            $gallery_ids_arr = explode(',', $gallery_ids);
            rsort($gallery_ids_arr);
            $_POST['depends_on']['gallery_ids'] = implode(',', $gallery_ids_arr);
        }
    }
    
    public function db_reverse_gallery_ids($props, $attrs, $render_slug) {
        if (isset($_GET['et_fb'])) { return $props; }
        if ($render_slug !== 'et_pb_gallery') { return $props; }
        if (!isset($props['gallery_orderby'])) { return $props; }
        if ($props['gallery_orderby'] === 'dbdb_reverse') { 
            $gallery_ids = empty($props['gallery_ids'])?'':$props['gallery_ids'];
            $props['gallery_ids'] = implode(',', array_reverse(explode(',', $gallery_ids)));
        } 
        elseif ($props['gallery_orderby'] === 'dbdb_by_id') { 
            $gallery_ids = empty($props['gallery_ids'])?'':$props['gallery_ids'];
            $gallery_ids_arr = explode(',', $gallery_ids);
            sort($gallery_ids_arr);
            $props['gallery_ids'] = implode(',', $gallery_ids_arr);
        } 
        elseif ($props['gallery_orderby'] === 'dbdb_by_id_reverse') { 
            $gallery_ids = empty($props['gallery_ids'])?'':$props['gallery_ids'];
            $gallery_ids_arr = explode(',', $gallery_ids);
            rsort($gallery_ids_arr);
            $props['gallery_ids'] = implode(',', $gallery_ids_arr);
        } 
        return $props;
    }

    public function add_fields($fields) {
        if (isset($fields['gallery_orderby']['options']) && is_array($fields['gallery_orderby']['options'])) {
            $fields['gallery_orderby']['options']['dbdb_reverse'] = esc_html__('Reverse', 'divi-booster');
            $fields['gallery_orderby']['options']['dbdb_by_id'] = esc_html__('By ID', 'divi-booster');
            $fields['gallery_orderby']['options']['dbdb_by_id_reverse'] = esc_html__('By ID (Reverse)', 'divi-booster');
        }
        if (isset($fields['gallery_orderby']['description'])) {
            $fields['gallery_orderby']['description'] = $fields['gallery_orderby']['description'].' '.esc_html__('Additional ordering methods <a href="https://divibooster.com/sorting-the-divi-gallery-images/" target="_blank">added by Divi Booster</a>.', 'divi-booster');
        }
        return $fields;
    }
}
