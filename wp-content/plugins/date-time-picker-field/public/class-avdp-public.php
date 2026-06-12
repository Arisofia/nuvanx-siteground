<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package Availability_Datepicker
 * @since 1.0.0
 */

class AVDP_Public
{
    /**
     * The ID of this plugin.
     *
     * @since 1.0.0
     * @var string
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since 1.0.0
     * @var string
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.0
     * @param string $plugin_name The name of the plugin.
     * @param string $version     The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since 1.0.0
     */
    public function enqueue_styles()
    {
        // Only load assets if selectors are present on page or filter allows
        if (!$this->should_load_assets()) {
            return;
        }

        $settings = AVDP_Settings::get_section('general_settings');
        $library = $settings['datepicker_library'] ?? 'xdsoft';

        // Default to XDSoft
        $xdsoft = new AVDP_Xdsoft();
        $xdsoft->enqueue_assets();

        wp_enqueue_style(
            $this->plugin_name,
            AVDP_PLUGIN_URL . 'public/css/avdp-public.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since 1.0.0
     */
    public function enqueue_scripts()
    {
        // Only load assets if selectors are present on page or filter allows
        if (!$this->should_load_assets()) {
            return;
        }

        $settings = AVDP_Settings::get_section('general_settings');
        $library = $settings['datepicker_library'] ?? 'xdsoft';
        $deps = array('jquery', 'avdp-availability-core', 'xdsoft-datetimepicker');

        wp_enqueue_script(
            'avdp-availability-core',
            AVDP_PLUGIN_URL . 'public/js/avdp-availability-core.js',
            array(),
            $this->version,
            true
        );

        wp_enqueue_script(
            $this->plugin_name,
            AVDP_PLUGIN_URL . 'public/js/avdp-public.js',
            $deps,
            $this->version,
            true
        );

        $this->localize_script();
    }

    /**
     * Get the localized config array passed to avdpPublic.
     * Public for testing; used by localize_script().
     *
     * Orchestration flow:
     *   1. Build the canonical availability config via AVDP_Availability.
     *   2. Pass it to the active library adapter (AVDP_Xdsoft or future adapters).
     *   3. Merge canonical config + library-specific options for JS consumption.
     *
     * @return array Config array for datepicker initialization.
     */
    public function get_localized_config()
    {
        $settings_gen = AVDP_Settings::get_section('general_settings');

        // 1. Build canonical availability config (single source of truth).
        $av_engine = new AVDP_Availability();
        $canonical_config = $av_engine->get_calendar_config();

        // 2. Pass canonical config to the active library adapter.
        $library = $settings_gen['datepicker_library'] ?? 'xdsoft';
        $library_config = array();

        if ( $library === 'xdsoft' ) {
            $xdsoft = new AVDP_Xdsoft();
            $library_config = $xdsoft->get_config( $canonical_config, $settings_gen );
        }
        // Future libraries: elseif ( $library === 'flatpickr' ) { ... }

        // 3. Merge: canonical config provides the availability data; library config
        //    adds/overrides with presentation-layer options. Library keys take
        //    precedence so adapters can remap canonical fields to library expectations.
        $config = array_merge( $canonical_config, $library_config );
        $config['library'] = $library;

        return $config;
    }

    /**
     * Get the full localized data (config, selectors, theme, i18n) passed to avdpPublic.
     * Public for testing.
     *
     * @return array Full avdpPublic object.
     */
    public function get_localized_data()
    {
        $settings_css = AVDP_Settings::get_section('css_selectors');
        $settings_gen = AVDP_Settings::get_section('general_settings');

        return array(
            'config' => $this->get_localized_config(),
            'selectors' => array(
                'single_field' => $settings_css['single_field'] ?? '.avdp-datepicker',
                'start_date' => $settings_css['start_date'] ?? '.avdp-start-date',
                'start_time' => $settings_css['start_time'] ?? '.avdp-start-time',
                'end_date' => $settings_css['end_date'] ?? '.avdp-end-date',
                'end_time' => $settings_css['end_time'] ?? '.avdp-end-time',
                'start_datetime' => $settings_css['start_datetime'] ?? '.avdp-start-datetime',
                'end_datetime' => $settings_css['end_datetime'] ?? '.avdp-end-datetime',
            ),
            'theme' => $settings_gen['datepicker_theme'] ?? 'light',
            'i18n' => array(
                'select_time' => esc_html__('Select Time', 'availability-datepicker'),
            ),
        );
    }

    /**
     * Localize script with settings and availability data.
     */
    private function localize_script()
    {
        $localized = $this->get_localized_data();
        wp_localize_script($this->plugin_name, 'avdpPublic', $localized);
    }

    /**
     * Check if assets should be loaded on current page.
     *
     * Uses filter to allow themes/plugins to control loading.
     * Also checks page content for selector classes when possible.
     *
     * @since 2.4.0.13
     * @return bool True if assets should be loaded.
     */
    private function should_load_assets()
    {
        // Allow filter override
        $should_load = apply_filters('avdp_should_load_assets', null);
        if (null !== $should_load) {
            return (bool) $should_load;
        }

        // Check if we're in admin (don't load public assets in admin)
        if (is_admin()) {
            return false;
        }

        // Get CSS selectors
        $selectors = AVDP_Settings::get_section('css_selectors');
        $all_selectors = array_filter(array_values($selectors));

        // If no selectors configured, don't load
        if (empty($all_selectors)) {
            return false;
        }

        // Check page content for selector classes (basic check)
        // Note: This is a lightweight check - full DOM checking happens in JS
        if (is_singular()) {
            global $post;
            if ($post && isset($post->post_content)) {
                foreach ($all_selectors as $selector) {
                    // Remove leading dot/class indicator for content check
                    $class_name = ltrim($selector, '.');
                    if (strpos($post->post_content, $class_name) !== false) {
                        return true;
                    }
                }
            }
        }

        // For dynamic pages (forms, etc.), allow loading
        // JavaScript will handle actual initialization only if selectors exist
        // Default to true for compatibility, but JS will only initialize if needed
        return apply_filters('avdp_load_assets_by_default', true);
    }

}
