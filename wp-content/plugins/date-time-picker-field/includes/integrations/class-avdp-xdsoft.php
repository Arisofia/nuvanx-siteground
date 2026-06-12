<?php
/**
 * xdsoft DateTimePicker Integration.
 *
 * @package Availability_Datepicker
 * @since 1.0.0
 */

class AVDP_Xdsoft
{

    /**
     * Enqueue xdsoft assets.
     *
     * @since 1.0.0
     * @return void
     */
    public function enqueue_assets()
    {
        $settings = AVDP_Settings::get_section('general_settings');
        $theme = $settings['datepicker_theme'] ?? 'light';

        wp_enqueue_style(
            'xdsoft-datetimepicker',
            AVDP_PLUGIN_URL . 'assets/vendor/xdsoft-datetimepicker/jquery.datetimepicker.min.css',
            array(),
            '2.5.20'
        );

        if ($theme === 'dark') {
            wp_enqueue_style(
                'xdsoft-datetimepicker-dark',
                AVDP_PLUGIN_URL . 'assets/vendor/xdsoft-datetimepicker/xdsoft-dark.css',
                array('xdsoft-datetimepicker'),
                '2.5.20'
            );
        } elseif ($theme === 'auto') {
            wp_enqueue_style(
                'xdsoft-datetimepicker-dark',
                AVDP_PLUGIN_URL . 'assets/vendor/xdsoft-datetimepicker/xdsoft-dark.css',
                array('xdsoft-datetimepicker'),
                '2.5.20',
                '(prefers-color-scheme: dark)'
            );
        }

        wp_enqueue_script(
            'xdsoft-datetimepicker',
            AVDP_PLUGIN_URL . 'assets/vendor/xdsoft-datetimepicker/jquery.datetimepicker.full.min.js',
            array('jquery'),
            '2.5.20',
            true
        );
    }

    /**
     * Get Xdsoft configuration.
     *
     * @since 1.0.0
     * @param array $calendar_config Canonical config from AVDP_Availability::get_calendar_config().
     * @param array $settings        General settings from AVDP_Settings::get_section('general_settings').
     * @return array xdsoft-specific options ready to merge into the localized config.
     */
    public function get_config( array $calendar_config, array $settings )
    {
        $date_format = isset($settings['date_format']) && is_string($settings['date_format']) && !empty($settings['date_format'])
            ? $settings['date_format']
            : 'Y-m-d';

        $time_format_raw = isset($settings['time_format']) && is_string($settings['time_format']) && !empty($settings['time_format'])
            ? $settings['time_format']
            : 'H:i';

        if ($time_format_raw === '12') {
            $time_format = 'g:i A';
        } elseif ($time_format_raw === '24') {
            $time_format = 'H:i';
        } else {
            $time_format = $time_format_raw;
        }

        $has_time_in_date = preg_match('/[HhGgisAa]/', $date_format);
        if ($has_time_in_date) {
            $full_format = $date_format;
        } else {
            $full_format = trim($date_format) . ' ' . trim($time_format);
        }

        $min_date = $calendar_config['min_date'] ?? false;
        $max_date = $calendar_config['max_date'] ?? false;

        if (!empty($min_date) && strlen($min_date) > 10) {
            $min_datetime = $min_date;
            $min_date = substr($min_date, 0, 10);
        } else {
            $min_datetime = null;
        }

        if (!empty($max_date) && strlen($max_date) > 10) {
            $max_date = substr($max_date, 0, 10);
        }

        $disabled_dates = $calendar_config['disabled_dates'];
        $exempted_dates = $calendar_config['exempted_dates'];
        $allowed_dates_with_times = $calendar_config['allowed_dates_with_times'] ?? array();

        $config = array(
            'format' => $full_format,
            'formatDate' => $date_format,
            'formatTime' => $time_format,
            'lang' => $settings['datepicker_language'] ?? 'en',
            'minDate' => $min_date ?: false,
            'maxDate' => $max_date ?: false,
            'minDatetime' => $min_datetime,
            'disabledDates' => array_values(array_filter($disabled_dates)),
            'disabledWeekDays' => $calendar_config['disabled_weekdays'],
            'step' => $calendar_config['slot_interval'],
            'defaultTime' => '09:00',
            'exemptedDates' => array_values(array_filter($exempted_dates)),
            'allowed_dates_with_times' => $allowed_dates_with_times,
            'inline' => ($settings['datepicker_display_method'] ?? 'dropdown') === 'inline',
            'timepicker' => true,
            'booking_type' => $calendar_config['booking_type'] ?? 'fixed',
            'min_days' => $calendar_config['min_days'] ?? 1,
            'max_days' => $calendar_config['max_days'] ?? 14,
            'min_duration' => $calendar_config['min_duration'] ?? 1,
            'max_duration' => $calendar_config['max_duration'] ?? 24,
        );

        return $config;
    }
}
