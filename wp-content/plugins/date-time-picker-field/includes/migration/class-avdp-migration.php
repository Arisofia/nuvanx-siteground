<?php
/**
 * Legacy Migrator.
 *
 * Migrates settings from legacy versions (1.7.9.4 through 2.3) to v3.0 format.
 * All legacy versions use dtpicker and dtpicker_advanced options.
 *
 * @package Availability_Datepicker
 * @since 3.0
 */

class AVDP_Legacy_Migrator
{
    use AVDP_Migration_Sections;
    use AVDP_Migration_Parsers;

    /**
     * Option name for migration completion flag.
     *
     * @var string
     */
    const MIGRATION_COMPLETED_OPTION = 'avdp_migration_completed';

    /**
     * Run migration if legacy data exists and migration not yet completed.
     *
     * Idempotent: safe to call multiple times.
     *
     * @return bool True if migration ran and succeeded, false if skipped or failed.
     */
    public static function maybe_migrate()
    {
        if (get_option(self::MIGRATION_COMPLETED_OPTION, false)) {
            return false;
        }

        $dtpicker = get_option('dtpicker', false);
        $dtpicker_advanced = get_option('dtpicker_advanced', false);
        if (false === $dtpicker && false === $dtpicker_advanced) {
            return false;
        }

        $migrator = new self();
        return $migrator->migrate();
    }

    /**
     * Perform the migration.
     *
     * @return bool True on success, false on failure.
     */
    public function migrate()
    {
        $legacy_data = $this->load_legacy_data();

        $integration_data = get_option('_dtpicker_new_integration', false);

        if (!class_exists('AVDP_Settings')) {
            require_once AVDP_PLUGIN_DIR . 'includes/class-avdp-settings.php';
        }

        $settings = AVDP_Settings::get_all();

        $settings['availability_rules']['weekly_hours'] = $this->migrate_weekly_hours($legacy_data);
        $settings['availability_rules']['time_settings'] = $this->migrate_time_settings($legacy_data);
        $settings['availability_rules']['date_overrides'] = $this->migrate_date_overrides($legacy_data);
        $settings['availability_rules']['booking_window'] = $this->migrate_booking_window($legacy_data);
        $settings['css_selectors'] = $this->migrate_css_selectors($legacy_data, $integration_data);
        $settings['general_settings'] = $this->migrate_general_settings($legacy_data);

        update_option('avdp_settings', $settings);

        update_option(self::MIGRATION_COMPLETED_OPTION, true);
        AVDP_Settings::clear_calendar_cache();

        return true;
    }

    /**
     * Load and merge legacy options.
     *
     * @return array Merged legacy data.
     */
    private function load_legacy_data()
    {
        $dtpicker = get_option('dtpicker', array());
        $dtpicker_advanced = get_option('dtpicker_advanced', array());

        if (!is_array($dtpicker) && $dtpicker !== false) {
            $dtpicker = array();
        }
        if (!is_array($dtpicker_advanced) && $dtpicker_advanced !== false) {
            $dtpicker_advanced = array();
        }

        return is_array($dtpicker) && is_array($dtpicker_advanced)
            ? array_merge($dtpicker, $dtpicker_advanced)
            : (is_array($dtpicker) ? $dtpicker : array());
    }
}
