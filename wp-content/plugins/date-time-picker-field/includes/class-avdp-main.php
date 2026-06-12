<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * @package Availability_Datepicker
 * @since 1.0.0
 */

class AVDP_Main
{

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     *
     * @since 1.0.0
     * @var AVDP_Loader
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since 1.0.0
     * @var string
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since 1.0.0
     * @var string
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->plugin_name = 'availability-datepicker';
        $this->version = AVDP_VERSION;

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();

        // Register upgrader hooks (version tracking + breaking-change notices).
        AVDP_Upgrader::register();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since 1.0.0
     */
    private function load_dependencies()
    {
        // Core classes.
        require_once AVDP_PLUGIN_DIR . 'includes/class-avdp-loader.php';
        require_once AVDP_PLUGIN_DIR . 'includes/class-avdp-i18n.php';
        require_once AVDP_PLUGIN_DIR . 'includes/class-avdp-settings.php';

        // Legacy migration (dtpicker/dtpicker_advanced -> avdp_settings).
        require_once AVDP_PLUGIN_DIR . 'includes/migration/class-avdp-migration-sections.php';
        require_once AVDP_PLUGIN_DIR . 'includes/migration/class-avdp-migration-parsers.php';
        require_once AVDP_PLUGIN_DIR . 'includes/migration/class-avdp-migration.php';

        // Upgrader: version tracking and breaking-change notices.
        require_once AVDP_PLUGIN_DIR . 'includes/class-avdp-upgrader.php';

        // DateTime Utility (UTC-based operations).
        require_once AVDP_PLUGIN_DIR . 'includes/class-avdp-datetime.php';

        // Availability Logic.
        require_once AVDP_PLUGIN_DIR . 'includes/availability/class-avdp-availability-slots.php';
        require_once AVDP_PLUGIN_DIR . 'includes/availability/class-avdp-availability-date-checks.php';
        require_once AVDP_PLUGIN_DIR . 'includes/availability/class-avdp-availability-booking-window.php';
        require_once AVDP_PLUGIN_DIR . 'includes/availability/class-avdp-availability-calendar-config.php';
        require_once AVDP_PLUGIN_DIR . 'includes/availability/class-avdp-availability.php';

        // Integrations.
        require_once AVDP_PLUGIN_DIR . 'includes/integrations/class-avdp-xdsoft.php';


        // Admin classes.
        require_once AVDP_PLUGIN_DIR . 'admin/class-avdp-admin-form-handlers.php';
        require_once AVDP_PLUGIN_DIR . 'admin/class-avdp-admin-ajax.php';
        require_once AVDP_PLUGIN_DIR . 'admin/class-avdp-admin.php';
        require_once AVDP_PLUGIN_DIR . 'admin/class-avdp-admin-components.php';

        // Admin view classes.
        require_once AVDP_PLUGIN_DIR . 'admin/views/class-avdp-availability-views.php';
        require_once AVDP_PLUGIN_DIR . 'admin/views/class-avdp-integration-views.php';
        require_once AVDP_PLUGIN_DIR . 'admin/views/class-avdp-settings-views.php';
        require_once AVDP_PLUGIN_DIR . 'admin/views/class-avdp-support-views.php';

        // Public classes.
        require_once AVDP_PLUGIN_DIR . 'public/class-avdp-public.php';

        $this->loader = new AVDP_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * @since 1.0.0
     */
    private function set_locale()
    {
        $plugin_i18n = new AVDP_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     *
     * @since 1.0.0
     */
    private function define_admin_hooks()
    {
        $plugin_admin = new AVDP_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
        $this->loader->add_action('admin_init', $plugin_admin, 'handle_admin_actions');
        $this->loader->add_action('wp_ajax_avdp_get_week_preview',  $plugin_admin, 'handle_get_week_preview');

    }

    /**
     * Register all of the hooks related to the public-facing functionality.
     *
     * @since 1.0.0
     */
    private function define_public_hooks()
    {
        $plugin_public = new AVDP_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since 1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it.
     *
     * @since 1.0.0
     * @return string
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks.
     *
     * @since 1.0.0
     * @return AVDP_Loader
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since 1.0.0
     * @return string
     */
    public function get_version()
    {
        return $this->version;
    }
}
