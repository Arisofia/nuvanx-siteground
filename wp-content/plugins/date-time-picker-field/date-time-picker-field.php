<?php
/**
 * Plugin Name: Availability Datepicker - Input WP
 * Plugin URI: https://inputwp.com
 * Description: Availability datepicker & booking calendar for any form. Add a date-time picker, manage availability, sync ICS calendar files. Integrates with Contact Form 7.
 * Version: 3.0
 * Author: Input WP
 * Author URI: https://inputwp.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: availability-datepicker
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.9.1
 * Requires PHP: 7.4
 *
 * @package Availability_Datepicker
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Current plugin version.
 */
define('AVDP_VERSION', '3.0');

/**
 * Plugin directory path.
 */
define('AVDP_PLUGIN_DIR', plugin_dir_path(__FILE__));

/**
 * Plugin directory URL.
 */
define('AVDP_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Plugin basename.
 */
define('AVDP_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function avdp_activate_plugin()
{
    require_once AVDP_PLUGIN_DIR . 'includes/class-avdp-activator.php';
    AVDP_Activator::activate();

    require_once AVDP_PLUGIN_DIR . 'includes/class-avdp-upgrader.php';
    AVDP_Upgrader::on_activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function avdp_deactivate_plugin()
{
    require_once AVDP_PLUGIN_DIR . 'includes/class-avdp-deactivator.php';
    AVDP_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'avdp_activate_plugin');
register_deactivation_hook(__FILE__, 'avdp_deactivate_plugin');

/**
 * The core plugin class.
 */
require_once AVDP_PLUGIN_DIR . 'includes/class-avdp-main.php';

/**
 * Begins execution of the plugin.
 *
 * @since 1.0.0
 */
function avdp_run_plugin()
{
    $plugin = new AVDP_Main();
    $plugin->run();
}

avdp_run_plugin();
