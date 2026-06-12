<?php
/*
Plugin Name: Divi Booster
Plugin URI: 
Description: Bug fixes and enhancements for Elegant Themes' Divi Theme.
Author: Dan Mossop
Version: 3.8.6
Requires PHP: 5.3
Author URI: https://divibooster.com
*/	

if (!defined('BOOSTER_VERSION')) {
    define('BOOSTER_VERSION', '3.8.6');
}

if (!function_exists('dbdb_file')) {
	function dbdb_file() {
		return __FILE__;
	}
}

if (!function_exists('dbdb_path')) {
	function dbdb_path($relpath='') {
		return plugin_dir_path(dbdb_file()).$relpath;
	}
}

if (!function_exists('dbdb_plugin_basename')) {
	function dbdb_plugin_basename() {
		return plugin_basename(dbdb_file());
	}
}

if (!function_exists('dbdb_slug')) {
	function dbdb_slug() {
		return 'divi-booster';
	}
}

// Run unit tests if applicable
if (defined('DB_UNIT_TESTS') && DB_UNIT_TESTS && file_exists(dbdb_path('tests/tests.php'))) {
	include_once(dbdb_path('tests/tests.php'));
}


// === Configuration === //
$slug = 'wtfdivi';
if (!defined('BOOSTER_DIR')) { 
    define('BOOSTER_DIR', dirname(dbdb_file()));
}
if (!defined('BOOSTER_CORE')) {
    define('BOOSTER_CORE', BOOSTER_DIR.'/core');
}
if (!defined('BOOSTER_SLUG')) {
    define('BOOSTER_SLUG', 'divi-booster');
}
if (!defined('BOOSTER_SLUG_OLD')) {
    define('BOOSTER_SLUG_OLD', $slug);
}
if (!defined('BOOSTER_VERSION_OPTION')) {
    define('BOOSTER_VERSION_OPTION', 'divibooster_version');
}
if (!defined('BOOSTER_SETTINGS_PAGE_SLUG')) {
    define('BOOSTER_SETTINGS_PAGE_SLUG', BOOSTER_SLUG_OLD.'_settings');
}
if (!defined('BOOSTER_NAME')) {
    define('BOOSTER_NAME', __('Divi Booster', BOOSTER_SLUG));
}

// Error Handling
if (!defined('BOOSTER_OPTION_LAST_ERROR')) {
    define('BOOSTER_OPTION_LAST_ERROR', 'wtfdivi_last_error');
}
if (!defined('BOOSTER_OPTION_LAST_ERROR_DESC')) {
    define('BOOSTER_OPTION_LAST_ERROR_DESC', 'wtfdivi_last_error_details');
}

// Directories
if (!defined('BOOSTER_DIR_FIXES')) {
    define('BOOSTER_DIR_FIXES', BOOSTER_CORE.'/fixes/');
}

// === Setup ===		
include_once(BOOSTER_CORE.'/index.php'); // Load the plugin framework




// === Start updates === 

$config = array(
    'plugin_slug' => 'divi-booster',
    'plugin_name' => __('Divi Booster', 'divi-booster'),
    'plugin_url' => 'https://divibooster.com/divi-booster-the-easy-way-to-customize-divi/',
    'edd_store_url' => 'https://divibooster.com',
    'edd_item_id' => 733, 
    'update_url' => 'https://d3mraia2v9t5x8.cloudfront.net',
    'plugin_file' => __FILE__
);

if (version_compare(phpversion(), '5.3', '>=')) {
    include_once(dirname(__FILE__).'/core/NO_EDIT_shared/licensing.php');
    if (get_option($config['plugin_slug'].'-license_status') === 'valid') {
        //include_once(dirname(__FILE__).'/core/NO_EDIT_shared/updates.php');
        booster_enable_updates(dbdb_file()); // Enable auto-updates for this plugin
    }
    //include_once(dirname(__FILE__).'/core/plugin-main.php');
}
else {
    add_action('admin_notices', 'dbdb_php_version_notice');
}

function dbdb_php_version_notice() { ?>
<div class="notice notice-warning">
    <p><?php esc_html_e('Important: Divi Booster requires PHP version 5.3 or higher.'); ?></p>
</div>
<?php
}

// === END updates ===



include_once(BOOSTER_CORE.'/update_patches.php'); // Apply update patches

// === Build the plugin ===

$sections = array(
	'general'=>'Site-wide Settings',
	'general-accessibility'=>'Accessibility',
	'general-icons'=>'Icons',
	'general-layout'=>'Layout',
	'general-links'=>'Links',
	'general-speed'=>'Site Speed',
	'header'=>'Header',
	'header-top'=>'Top Header',
	'header-main'=>'Main Header',
	'header-mobile'=>'Mobile Header',
	'posts'=>'Posts',
	'sidebar'=>'Sidebar',
	'footer'=>'Footer',
	'footer-layout'=>'Layout',
	'footer-menu'=>'Footer Menu',
	'footer-bottombar'=>'Bottom Bar',
	'pagebuilder'=>'Divi Builder',
	'pagebuilder-divi'=>'General',
	'pagebuilder-classic'=>'Classic Builder',
	'pagebuilder-visual'=>'Visual Builder',
	'modules'=>'Modules',
	'modules-accordion'=>'Accordion',
	//'modules-blurb'=>'Blurb',
	'modules-countdown'=>'Countdown',
	'modules-gallery'=>'Gallery',
	'modules-headerfullwidth'=>'Header (Full Width)',
	'modules-map'=>'Map',
	'modules-portfolio'=>'Portfolio',
	'modules-portfoliofiltered'=>'Portfolio (Filterable)',
	'modules-portfoliofullwidth'=>'Portfolio (Full Width)',
	'modules-postnav'=>'Post Navigation',
	'modules-postslider'=>'Post Slider',
	'modules-pricing'=>'Pricing Table',
	'modules-subscribe'=>'Signup',
	'modules-slider'=>'Slider',
	'modules-text'=>'Text',
	'plugins'=>'Plugins',
	'plugins-edd'=>'Easy Digital Downloads',
	'plugins-woocommerce'=>'WooCommerce',
	'plugins-other'=>'Other',
	'customcss'=>'CSS Manager',
	'developer'=>'Developer Tools',
	'developer-export'=>'Import / Export',
	'developer-css'=>'Generated CSS',
	'developer-js'=>'Generated JS',
	'developer-footer-html'=>'Generated Footer HTML',
	'developer-htaccess'=>'Generated .htaccess Rules',
	'deprecated'=>'Deprecated (now available in Divi)',
	'deprecated-divi4'=>'Divi 4',
	'deprecated-divi24'=>'Divi 2.4',
	'deprecated-divi23'=>'Pre Divi 2.4'
);

// === Set enabled-by-default fixes ===

add_filter('divibooster_fixes', 'db126_enable_feature_by_default');

if (!function_exists('db126_enable_feature_by_default')) {
    function db126_enable_feature_by_default($fixes) {
        
        if (!is_array($fixes)) { return $fixes; }
        
        $enabled_by_default = array(
            '126-customizer-social-icons'
        );
        
        foreach($enabled_by_default as $fix) {
            if (!isset($fixes[$fix]['enabled'])) { 
                $fixes[$fix]['enabled'] = true;
            }
        }
        
        return $fixes;
    }
}

// === Main plugin ===

if (!function_exists('dbdb_admin_menu_slug')) {
	function dbdb_admin_menu_slug() {
		if (dbdb_is_divi_2_4_up()) { // Recent Divis
			$result = 'et_divi_options';
		} elseif (dbdb_is_divi()) { // Early Divis
			$result = 'themes.php';
		} elseif (dbdb_is_extra()) { // Extra
			$result = 'et_extra_options';
		} else { // Assume Divi Builder
			$result = 'et_divi_options';
		}
		return $result;
	}
}

if (!function_exists('dbdb_settings_page_url')) {
	function dbdb_settings_page_url() {
		$page = (dbdb_admin_menu_slug()=='themes.php'?'themes.php':'admin.php');
		return admin_url($page.'?page=wtfdivi_settings');
	}
}

if (class_exists('wtfplugin_1_0')) {
	$wtfdivi = new wtfplugin_1_0(
		array(
			'plugin'=>array(
				'name'=>BOOSTER_NAME,
				'shortname'=>BOOSTER_NAME, // menu name
				'slug'=>$slug,
				'package_slug'=>dbdb_slug(),
				'plugin_file'=>dbdb_file(),
				'url'=>'https://divibooster.com/themes/divi/',
				'basename'=>plugin_basename(dbdb_file())
			),
			'sections'=>$sections
		)
	);
} else {
	add_action('admin_notices', 'db_admin_notice_main_class_missing');
}

// Store and return an instance of the plugin
if (!function_exists('dbdb_plugin')) {
    function dbdb_plugin($instance=null) {
        static $plugin;
        if (!is_null($instance)) { 
            $plugin = $instance;
        }
        return $plugin;
    }
}
dbdb_plugin($wtfdivi);

if (!function_exists('db_admin_notice_main_class_missing')) {
	function db_admin_notice_main_class_missing() {
		echo apply_filters('db_admin_notice_main_class_missing', '<div class="notice notice-error"><p>Error: The main Divi Booster class cannot be found. This suggests a corrupted plugin directory. Please try reinstalling Divi Booster, or <a href="https://divibooster.com/contact-form/" target="_blank">let me know</a>.</p></div>'); 
	}
}


// === Load the settings ===
if (!function_exists('divibooster_load_settings')) {
    function divibooster_load_settings($wtfdivi) {
        $settings_files = glob(BOOSTER_DIR_FIXES.'*/settings.php');
        if ($settings_files) { 
            foreach($settings_files as $file) { include_once($file); }
        }
    }
    add_action("$slug-before-settings-page", 'divibooster_load_settings');
}

// === Add settings page hook ===
if (!function_exists('divibooster_settings_page_init')) {
    function divibooster_settings_page_init() {
        global $pagenow, $plugin_page;
        if ($pagenow == 'admin.php' and $plugin_page == BOOSTER_SETTINGS_PAGE_SLUG) {
            do_action('divibooster_settings_page_init');
        }
    }
    add_action('admin_init', 'divibooster_settings_page_init');
}


// Load media library
if (!function_exists('db_enqueue_media_loader')) {
    function db_enqueue_media_loader() { 
        wp_enqueue_media(); 
    }
}
add_action('admin_enqueue_scripts', 'db_enqueue_media_loader', 11); // Priority > 10 to avoid visualizer plugin conflict

// =========================================================== //
// ==                          FOOTER                       == //
// =========================================================== //

// === Footer ===
if (!function_exists('divibooster_footer')) {
    function divibooster_footer() { ?>
    <p>Spot a problem with this plugin? Want to make another change to the Divi Theme? <a href="https://divibooster.com/contact-form/">Let me know</a>.</p>
    <p><i>This plugin is an independent product which is not associated with, endorsed by, or supported by Elegant Themes.</i></p>
    <?php
    }	
}
add_action($slug.'-plugin-footer', 'divibooster_footer');

