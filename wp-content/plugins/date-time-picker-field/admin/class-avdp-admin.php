<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package Availability_Datepicker
 * @since 1.0.0
 */

class AVDP_Admin
{
    use AVDP_Admin_Form_Handlers;
    use AVDP_Admin_Ajax;

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
     * SVG icon for menu.
     *
     * @since 1.0.0
     * @var string
     */
    public static $menu_svg = 'PHN2ZyB3aWR0aD0iMTI4IiBoZWlnaHQ9IjEyOCIgdmlld0JveD0iMCAwIDEyOCAxMjgiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxwYXRoIGZpbGwtcnVsZT0iZXZlbm9kZCIgY2xpcC1ydWxlPSJldmVub2RkIiBkPSJNMzIuOTY5NyAxOS4zOTM5QzI1LjQ3MiAxOS4zOTM5IDE5LjM5MzkgMjUuNDcyIDE5LjM5MzkgMzIuOTY5N1Y5NS4wMzAzQzE5LjM5MzkgMTAyLjUyOCAyNS40NzIgMTA4LjYwNiAzMi45Njk3IDEwOC42MDZIMzguMTc5NUMzOS43MjI1IDEwOC42MDYgNDEuMjAyNCAxMDcuOTkzIDQyLjI5MzUgMTA2LjkwMkw3Ni41MzcxIDcyLjY1ODNMOTAuMjUwNyA4Ni4zNzE5TDU2LjAwNzEgMTIwLjYxNkM1MS4yNzg5IDEyNS4zNDQgNDQuODY2MSAxMjggMzguMTc5NSAxMjhIMzIuOTY5N0MxNC43NjEgMTI4IDAgMTEzLjIzOSAwIDk1LjAzMDNWMzIuOTY5N0MwIDE0Ljc2MSAxNC43NjEgMCAzMi45Njk3IDBWMTkuMzkzOVpNNzEuNzU3NiAxOS4zOTM5SDU2LjI0MjRWMEg3MS43NTc2VjE5LjM5MzlaTTk1LjAzMDMgMEMxMTMuMjM5IDAgMTI4IDE0Ljc2MSAxMjggMzIuOTY5N1Y0OC40ODQ4SDEwOC42MDZWMzIuOTY5N0MxMDguNjA2IDI1LjQ3MiAxMDIuNTI4IDE5LjM5MzkgOTUuMDMwMyAxOS4zOTM5VjBaIiBmaWxsPSIjOUVBM0E5Ii8+CjxwYXRoIG9wYWNpdHk9IjAuNCIgZmlsbC1ydWxlPSJldmVub2RkIiBjbGlwLXJ1bGU9ImV2ZW5vZGQiIGQ9Ik0xMjUuMTYgODYuMzcxOUw4Ni4zNzE5IDEyNS4xNkM4Mi41ODUgMTI4Ljk0NyA3Ni40NDUyIDEyOC45NDcgNzIuNjU4MyAxMjUuMTZMNjUuNzczNCAxMTguMjc1TDc5LjQ4NyAxMDQuNTYxTDc5LjUxNTEgMTA0LjU4OUwxMTEuNDQ2IDcyLjY1ODNMMTI1LjE2IDg2LjM3MTlaIiBmaWxsPSIjOUVBM0E5Ii8+CjxyZWN0IHg9IjMyLjk2OTciIHk9IjE5LjM5MzkiIHdpZHRoPSIyMy4yNzI3IiBoZWlnaHQ9IjE5LjM5MzkiIGZpbGw9IiM5RUEzQTkiLz4KPHJlY3QgeD0iNzEuNzU3OCIgeT0iMTkuMzkzOSIgd2lkdGg9IjIzLjI3MjciIGhlaWdodD0iMTkuMzkzOSIgZmlsbD0iIzlFQTNBOSIvPgo8L3N2Zz4K';


    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.0
     * @param string $plugin_name The name of this plugin.
     * @param string $version     The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since 1.0.0
     */
    public function enqueue_styles()
    {
        $screen = get_current_screen();
        if ( null === $screen || strpos( $screen->id, 'avdp' ) === false ) {
            return;
        }

        wp_enqueue_style(
            $this->plugin_name,
            AVDP_PLUGIN_URL . 'admin/css/avdp-admin.css',
            array(),
            $this->version,
            'all'
        );

    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since 1.0.0
     */
    public function enqueue_scripts()
    {
        $screen = get_current_screen();
        if ( null === $screen || strpos( $screen->id, 'avdp' ) === false ) {
            return;
        }

        $deps = array('jquery');

        wp_enqueue_script(
            $this->plugin_name,
            AVDP_PLUGIN_URL . 'admin/js/avdp-admin.js',
            $deps,
            $this->version,
            true
        );

        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        $restore_defaults = array();
        if ($page === 'avdp-availability') {
            $restore_defaults = AVDP_Settings::get_default_section('availability_rules');
        } elseif ($page === 'avdp-css-selectors') {
            $restore_defaults = AVDP_Settings::get_default_section('css_selectors');
        } elseif ($page === 'avdp-settings') {
            $restore_defaults = AVDP_Settings::get_default_section('general_settings');
        }

        $weekday_slots = function ($start, $end) {
            return array('enabled' => true, 'slots' => array(array('start' => $start, 'end' => $end)));
        };
        $day_off = array('enabled' => false, 'slots' => array());

        $presets = array();
        if ($page === 'avdp-availability') {
            $presets = array(
                array(
                    'id'                 => 'doctor_appointment',
                    'name'               => __('Doctor / Medical', 'availability-datepicker'),
                    'description'        => __('Fixed 30-min slots, Mon–Fri, 24 h advance notice.', 'availability-datepicker'),
                    'recommended_fields' => __('Date + Time (single datetime or separate date & time — no range)', 'availability-datepicker'),
                    'data'               => array(
                        'method'         => 'fixed',
                        'booking_window' => array('from_type' => 'dynamic', 'from_value' => '0', 'to_type' => 'dynamic', 'to_value' => '60'),
                        'weekly_hours'   => array(
                            'monday'    => $weekday_slots('09:00', '17:00'),
                            'tuesday'   => $weekday_slots('09:00', '17:00'),
                            'wednesday' => $weekday_slots('09:00', '17:00'),
                            'thursday'  => $weekday_slots('09:00', '17:00'),
                            'friday'    => $weekday_slots('09:00', '17:00'),
                            'saturday'  => $day_off,
                            'sunday'    => $day_off,
                        ),
                        'time_settings'  => array('slot_interval' => 30, 'minimum_notice' => 1440, 'buffer_before' => 10, 'buffer_after' => 10, 'min_days' => 1, 'max_days' => 14, 'min_duration' => 1, 'max_duration' => 24),
                        'date_overrides' => array('blocked_dates' => array(), 'allowed_dates' => array()),
                    ),
                ),
                array(
                    'id'                 => 'salon_beauty',
                    'name'               => __('Salon & Beauty', 'availability-datepicker'),
                    'description'        => __('Fixed 60-min slots, Mon–Sat, 2 h advance notice.', 'availability-datepicker'),
                    'recommended_fields' => __('Date + Time (single datetime or separate date & time — no range)', 'availability-datepicker'),
                    'data'               => array(
                        'method'         => 'fixed',
                        'booking_window' => array('from_type' => 'dynamic', 'from_value' => '0', 'to_type' => 'dynamic', 'to_value' => '30'),
                        'weekly_hours'   => array(
                            'monday'    => $weekday_slots('09:00', '19:00'),
                            'tuesday'   => $weekday_slots('09:00', '19:00'),
                            'wednesday' => $weekday_slots('09:00', '19:00'),
                            'thursday'  => $weekday_slots('09:00', '19:00'),
                            'friday'    => $weekday_slots('09:00', '19:00'),
                            'saturday'  => $weekday_slots('09:00', '19:00'),
                            'sunday'    => $day_off,
                        ),
                        'time_settings'  => array('slot_interval' => 60, 'minimum_notice' => 120, 'buffer_before' => 0, 'buffer_after' => 15, 'min_days' => 1, 'max_days' => 14, 'min_duration' => 1, 'max_duration' => 24),
                        'date_overrides' => array('blocked_dates' => array(), 'allowed_dates' => array()),
                    ),
                ),
                array(
                    'id'                 => 'equipment_rental',
                    'name'               => __('Equipment Rental', 'availability-datepicker'),
                    'description'        => __('Flexible overnight/multi-day rentals, 12–72 h duration.', 'availability-datepicker'),
                    'recommended_fields' => __('Date & Time Range (start datetime + end datetime)', 'availability-datepicker'),
                    'data'               => array(
                        'method'         => 'flexible',
                        'booking_window' => array('from_type' => 'dynamic', 'from_value' => '0', 'to_type' => 'dynamic', 'to_value' => '60'),
                        'weekly_hours'   => array(
                            'monday'    => $weekday_slots('08:00', '20:00'),
                            'tuesday'   => $weekday_slots('08:00', '20:00'),
                            'wednesday' => $weekday_slots('08:00', '20:00'),
                            'thursday'  => $weekday_slots('08:00', '20:00'),
                            'friday'    => $weekday_slots('08:00', '20:00'),
                            'saturday'  => $weekday_slots('08:00', '20:00'),
                            'sunday'    => $weekday_slots('08:00', '20:00'),
                        ),
                        'time_settings'  => array('slot_interval' => 60, 'minimum_notice' => 120, 'buffer_before' => 30, 'buffer_after' => 30, 'min_days' => 1, 'max_days' => 14, 'min_duration' => 12, 'max_duration' => 72),
                        'date_overrides' => array('blocked_dates' => array(), 'allowed_dates' => array()),
                    ),
                ),
                array(
                    'id'                 => 'hotel_rental',
                    'name'               => __('Hotel / Vacation Rental', 'availability-datepicker'),
                    'description'        => __('Day-based bookings, 2–30 night stays, all week.', 'availability-datepicker'),
                    'recommended_fields' => __('Date Range (check-in date + check-out date — no time)', 'availability-datepicker'),
                    'data'               => array(
                        'method'         => 'daily',
                        'booking_window' => array('from_type' => 'dynamic', 'from_value' => '1', 'to_type' => 'dynamic', 'to_value' => '365'),
                        'weekly_hours'   => array(
                            'monday'    => $weekday_slots('00:00', '23:59'),
                            'tuesday'   => $weekday_slots('00:00', '23:59'),
                            'wednesday' => $weekday_slots('00:00', '23:59'),
                            'thursday'  => $weekday_slots('00:00', '23:59'),
                            'friday'    => $weekday_slots('00:00', '23:59'),
                            'saturday'  => $weekday_slots('00:00', '23:59'),
                            'sunday'    => $weekday_slots('00:00', '23:59'),
                        ),
                        'time_settings'  => array('slot_interval' => 30, 'minimum_notice' => 0, 'buffer_before' => 0, 'buffer_after' => 0, 'min_days' => 2, 'max_days' => 30, 'min_duration' => 1, 'max_duration' => 24),
                        'date_overrides' => array('blocked_dates' => array(), 'allowed_dates' => array()),
                    ),
                ),
                array(
                    'id'                 => 'car_rental',
                    'name'               => __('Car Rental', 'availability-datepicker'),
                    'description'        => __('Flexible pickup & return, 4 h–7 day rentals, every day.', 'availability-datepicker'),
                    'recommended_fields' => __('Date & Time Range (start datetime + end datetime)', 'availability-datepicker'),
                    'data'               => array(
                        'method'         => 'flexible',
                        'booking_window' => array('from_type' => 'dynamic', 'from_value' => '0', 'to_type' => 'dynamic', 'to_value' => '90'),
                        'weekly_hours'   => array(
                            'monday'    => $weekday_slots('08:00', '20:00'),
                            'tuesday'   => $weekday_slots('08:00', '20:00'),
                            'wednesday' => $weekday_slots('08:00', '20:00'),
                            'thursday'  => $weekday_slots('08:00', '20:00'),
                            'friday'    => $weekday_slots('08:00', '20:00'),
                            'saturday'  => $weekday_slots('08:00', '20:00'),
                            'sunday'    => $weekday_slots('08:00', '20:00'),
                        ),
                        'time_settings'  => array('slot_interval' => 60, 'minimum_notice' => 120, 'buffer_before' => 0, 'buffer_after' => 0, 'min_days' => 1, 'max_days' => 14, 'min_duration' => 4, 'max_duration' => 168),
                        'date_overrides' => array('blocked_dates' => array(), 'allowed_dates' => array()),
                    ),
                ),
                array(
                    'id'                 => 'meeting_room',
                    'name'               => __('Meeting Room', 'availability-datepicker'),
                    'description'        => __('Flexible 1–8 h bookings, Mon–Fri, 15 min buffers.', 'availability-datepicker'),
                    'recommended_fields' => __('Date & Time Range (start datetime + end datetime)', 'availability-datepicker'),
                    'data'               => array(
                        'method'         => 'flexible',
                        'booking_window' => array('from_type' => 'dynamic', 'from_value' => '0', 'to_type' => 'dynamic', 'to_value' => '30'),
                        'weekly_hours'   => array(
                            'monday'    => $weekday_slots('08:00', '18:00'),
                            'tuesday'   => $weekday_slots('08:00', '18:00'),
                            'wednesday' => $weekday_slots('08:00', '18:00'),
                            'thursday'  => $weekday_slots('08:00', '18:00'),
                            'friday'    => $weekday_slots('08:00', '18:00'),
                            'saturday'  => $day_off,
                            'sunday'    => $day_off,
                        ),
                        'time_settings'  => array('slot_interval' => 30, 'minimum_notice' => 60, 'buffer_before' => 15, 'buffer_after' => 15, 'min_days' => 1, 'max_days' => 14, 'min_duration' => 1, 'max_duration' => 8),
                        'date_overrides' => array('blocked_dates' => array(), 'allowed_dates' => array()),
                    ),
                ),
            );
        }

        wp_localize_script(
            $this->plugin_name,
            'avdpAdmin',
            array(
                'ajaxUrl'             => admin_url('admin-ajax.php'),
                'nonce'               => wp_create_nonce('avdp_admin_nonce'),
                'restoreDefaults'     => $restore_defaults,
                'restoreDefaultsPage' => $page,
                'presets'             => $presets,
                'timezone'            => (function () {
                    $gs = AVDP_Settings::get_section('general_settings');
                    $tz = !empty($gs['timezone']) ? $gs['timezone'] : '';
                    return $tz ?: ( function_exists('wp_timezone_string') ? wp_timezone_string() : 'UTC' );
                })(),
                'i18n' => array(
                    'remove'                 => __('Remove', 'availability-datepicker'),
                    'to'                     => __('to', 'availability-datepicker'),
                    'from'                   => __('From', 'availability-datepicker'),
                    'addRange'               => __('Add range', 'availability-datepicker'),
                    'add'                    => __('Add', 'availability-datepicker'),
                    'cancel'                 => __('Cancel', 'availability-datepicker'),
                    'copied'                 => __('Copied!', 'availability-datepicker'),
                    'unsavedChanges'         => __('You have unsaved changes. Are you sure you want to leave?', 'availability-datepicker'),
                    'themeLight'             => __('Light (Default)', 'availability-datepicker'),
                    'themeDark'              => __('Dark', 'availability-datepicker'),
                    'available'              => __('Available', 'availability-datepicker'),
                    'unavailable'            => __('Unavailable', 'availability-datepicker'),
                    'restoreDefaultsConfirm' => __("Reset to default\n\nThis will reset all settings on this page to their factory defaults. Your current configuration will be replaced — but only after you click Save Changes.\n\nClick OK to load the default values, or Cancel to keep your current settings.", 'availability-datepicker'),
                    'loadPreset'             => __('Load Preset', 'availability-datepicker'),
                    'loadPresetConfirm'      => __("Load preset\n\nThis will replace your current availability settings with the selected preset — but only after you click Save Changes.\n\nClick OK to load the preset, or Cancel to keep your current settings.", 'availability-datepicker'),
                    'applyPreset'            => __('Apply Preset', 'availability-datepicker'),
                    'selectPreset'           => __('Select a preset to get started quickly', 'availability-datepicker'),
                    'recommendedFields'      => __('Recommended fields:', 'availability-datepicker'),
                ),
            )
        );
    }

    /**
     * Add admin menu.
     *
     * @since 1.0.0
     */
    public function add_admin_menu()
    {

        // Main menu - Availability
        add_menu_page(
            __('Availability Datepicker', 'availability-datepicker'),
            __('Availability', 'availability-datepicker'),
            'manage_options',
            'avdp-availability',
            array($this, 'render_availability_page'),
            'data:image/svg+xml;base64,' . self::$menu_svg,
            30
        );

        // CSS Selectors submenu
        add_submenu_page(
            'avdp-availability',
            __('Integration', 'availability-datepicker'),
            __('Integration', 'availability-datepicker'),
            'manage_options',
            'avdp-css-selectors',
            array($this, 'render_css_selectors_page')
        );

        // Settings submenu
        add_submenu_page(
            'avdp-availability',
            __('Settings', 'availability-datepicker'),
            __('Settings', 'availability-datepicker'),
            'manage_options',
            'avdp-settings',
            array($this, 'render_settings_page')
        );

        // Support submenu
        add_submenu_page(
            'avdp-availability',
            __('Support', 'availability-datepicker'),
            __('Support', 'availability-datepicker'),
            'manage_options',
            'avdp-support',
            array($this, 'render_support_page')
        );
    }

    // Render Methods

    /**
     * Render the Availability Settings page.
     *
     * @since 1.0.0
     */
    public function render_availability_page()
    {
        AVDP_Admin_Components::header_bar();
        $rules = AVDP_Settings::get_section('availability_rules');
        AVDP_Availability_Views::render($rules);
    }

    /**
     * Render the CSS Selectors page.
     *
     * @since 1.0.0
     */
    public function render_css_selectors_page()
    {
        AVDP_Admin_Components::header_bar();
        $selectors = AVDP_Settings::get_section('css_selectors');
        AVDP_Integration_Views::render($selectors);
    }

    /**
     * Render the General Settings page.
     *
     * @since 1.0.0
     */
    public function render_settings_page()
    {
        AVDP_Admin_Components::header_bar();
        $settings = AVDP_Settings::get_section('general_settings');
        AVDP_Settings_Views::render($settings);
    }

    /**
     * Render the Support page.
     *
     * @since 1.0.0
     */
    public function render_support_page()
    {
        AVDP_Admin_Components::header_bar( 'pro' );
        AVDP_Support_Views::render();
    }
}
