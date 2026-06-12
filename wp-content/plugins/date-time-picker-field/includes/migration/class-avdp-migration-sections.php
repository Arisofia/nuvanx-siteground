<?php
/**
 * Migration Sections Trait.
 *
 * Handles per-section migration logic from legacy formats.
 *
 * @package Availability_Datepicker
 * @since 3.0
 */

trait AVDP_Migration_Sections
{

    /**
     * Migrate weekly hours from legacy format.
     *
     * @param array $data Merged legacy data (dtpicker + dtpicker_advanced).
     * @return array v3.0 weekly_hours structure.
     */
    private function migrate_weekly_hours($data)
    {
        $day_map = array(
            'sunday'    => 'sunday_times',
            'monday'    => 'monday_times',
            'tuesday'   => 'tuesday_times',
            'wednesday' => 'wednesday_times',
            'thursday'  => 'thursday_times',
            'friday'    => 'friday_times',
            'saturday'  => 'saturday_times',
        );

        $disabled_days = isset($data['disabled_days']) ? $data['disabled_days'] : array();
        if (!is_array($disabled_days)) {
            $disabled_days = array();
        }
        $disabled_day_numbers = array();
        foreach ($disabled_days as $day) {
            $day_num = strval($day);
            if (in_array($day_num, array('0', '1', '2', '3', '4', '5', '6'))) {
                $disabled_day_numbers[] = $day_num;
            }
        }

        $min_time_raw  = isset($data['minTime']) ? trim($data['minTime']) : '';
        $max_time_raw  = isset($data['maxTime']) ? trim($data['maxTime']) : '';
        $normalized_min = !empty($min_time_raw) ? $this->normalize_time_for_slots($min_time_raw) : null;
        $normalized_max = !empty($max_time_raw) ? $this->normalize_time_for_slots($max_time_raw) : null;

        $default_slot = null;
        if ($normalized_min || $normalized_max) {
            $start     = $normalized_min ?: '09:00';
            $end       = $normalized_max ?: '17:00';
            $start_obj = DateTime::createFromFormat('H:i', $start);
            $end_obj   = DateTime::createFromFormat('H:i', $end);
            if ($start_obj && $end_obj && $start_obj < $end_obj) {
                $default_slot = array('start' => $start, 'end' => $end);
            }
        }

        $global_times = isset($data['allowed_times']) ? trim($data['allowed_times']) : '';
        $global_slot  = !empty($global_times)
            ? $this->times_string_to_range_slot($global_times)
            : null;

        $day_number_map = array(
            'sunday' => '0', 'monday' => '1', 'tuesday' => '2', 'wednesday' => '3',
            'thursday' => '4', 'friday' => '5', 'saturday' => '6',
        );

        $weekly_hours = array();
        foreach ($day_map as $day_name => $v2_key) {
            $day_number  = $day_number_map[$day_name];
            $is_disabled = in_array($day_number, $disabled_day_numbers);

            $slots = array();
            if (!$is_disabled) {
                $day_times = isset($data[$v2_key]) ? trim($data[$v2_key]) : '';
                if (!empty($day_times)) {
                    // Priority 1: day-specific times → take min→max range.
                    $day_slot = $this->times_string_to_range_slot($day_times);
                    if ($day_slot) {
                        $slots = array($day_slot);
                    }
                } elseif ($global_slot) {
                    // Priority 2: global allowed_times range.
                    $slots = array($global_slot);
                } elseif ($default_slot) {
                    // Priority 3: minTime/maxTime as default business hours.
                    $slots = array($default_slot);
                }
                // Priority 4: no time configuration — slots stays empty.
            }

            $weekly_hours[$day_name] = array(
                'enabled' => !$is_disabled,
                'slots'   => $slots,
            );
        }

        return $weekly_hours;
    }

    /**
     * Migrate time settings.
     *
     * @param array $data Legacy data.
     * @return array v3.0 time_settings structure.
     */
    private function migrate_time_settings($data)
    {
        $time_settings = array(
            'slot_interval' => 30,
            'buffer_before' => 0,
            'buffer_after' => 0,
            'minimum_notice' => 0,
        );

        if (isset($data['step'])) {
            $step = intval($data['step']);
            if ($step >= 1 && $step <= 1440) {
                $time_settings['slot_interval'] = $step;
            }
        }

        if (isset($data['offset']) && $data['offset'] !== '' && $data['offset'] !== null) {
            $offset = intval($data['offset']);
            if ($offset >= 0) {
                $time_settings['minimum_notice'] = $offset;
            }
        }

        return $time_settings;
    }

    /**
     * Migrate date overrides.
     *
     * @param array $data Legacy data.
     * @return array v3.0 date_overrides structure.
     */
    private function migrate_date_overrides($data)
    {
        $overrides = array(
            'blocked_dates' => array(),
            'allowed_dates' => array(),
        );

        if (isset($data['disabled_calendar_days']) && !empty($data['disabled_calendar_days'])) {
            $date_format = isset($data['dateformat']) ? $data['dateformat'] : 'YYYY-MM-DD';
            $overrides['blocked_dates'] = $this->parse_disabled_dates($data['disabled_calendar_days'], $date_format);
        }

        return $overrides;
    }

    /**
     * Migrate booking window.
     *
     * @param array $data Legacy data.
     * @return array v3.0 booking_window structure.
     */
    private function migrate_booking_window($data)
    {
        $booking_window = array(
            'from_type' => 'dynamic',
            'from_value' => '0',
            'to_type' => 'dynamic',
            'to_value' => '30',
            'days_future' => 30,
        );

        $min_offset = 0;
        $min_absolute_date = null;

        if (isset($data['minDate']) && $data['minDate'] === 'on') {
            $min_offset = 0;
        }

        if (isset($data['min_date']) && !empty($data['min_date'])) {
            $result = $this->parse_date_for_booking_window($data['min_date'], true);
            if ($result['type'] === 'relative') {
                $min_offset = max($min_offset, $result['days']);
            } elseif ($result['type'] === 'absolute') {
                $min_absolute_date = $result['date'];
                $booking_window['from_type'] = 'predefined';
                $booking_window['from_value'] = $min_absolute_date;
            }
        }

        if (isset($data['days_offset']) && $data['days_offset'] !== '' && $data['days_offset'] !== null) {
            $offset = intval($data['days_offset']);
            if ($offset > 0) {
                $min_offset = max($min_offset, $offset);
            }
        }

        if ($min_absolute_date === null) {
            $booking_window['from_type'] = 'dynamic';
            $booking_window['from_value'] = strval($min_offset);
        }

        if (isset($data['max_date']) && !empty($data['max_date'])) {
            $result = $this->parse_date_for_booking_window($data['max_date'], false);
            if ($result['type'] === 'relative') {
                $days = max(1, $result['days']);
                $booking_window['to_type'] = 'dynamic';
                $booking_window['to_value'] = strval($days);
                $booking_window['days_future'] = $days;
            } elseif ($result['type'] === 'absolute') {
                $booking_window['to_type'] = 'predefined';
                $booking_window['to_value'] = $result['date'];
                $today = new DateTime('now', new DateTimeZone('UTC'));
                $max_obj = DateTime::createFromFormat('Y-m-d', $result['date']);
                if ($max_obj) {
                    $diff = $today->diff($max_obj);
                    $booking_window['days_future'] = max(1, (int) $diff->days);
                }
            } elseif ($result['type'] === 'number') {
                $days = max(1, intval($data['max_date']));
                $booking_window['to_type'] = 'dynamic';
                $booking_window['to_value'] = strval($days);
                $booking_window['days_future'] = $days;
            }
        }

        return $booking_window;
    }

    /**
     * Migrate CSS selectors.
     *
     * @param array $data Legacy data.
     * @param mixed $integration_data Integration data from _dtpicker_new_integration.
     * @return array v3.0 css_selectors structure.
     */
    private function migrate_css_selectors($data, $integration_data)
    {
        $selectors = array(
            'single_field' => '.avdp-datepicker',
            'start_date' => '.avdp-start-date',
            'start_time' => '.avdp-start-time',
            'end_date' => '.avdp-end-date',
            'end_time' => '.avdp-end-time',
            'start_datetime' => '.avdp-start-datetime',
            'end_datetime' => '.avdp-end-datetime',
        );

        $picker_type = $this->infer_picker_type($data);

        $main_selector = null;
        if (isset($data['selector']) && !empty($data['selector'])) {
            $main_selector = $this->extract_first_selector($data['selector']);
        }

        $integration_selectors = $this->extract_integration_selectors($integration_data);

        $final_selector = !empty($integration_selectors)
            ? reset($integration_selectors)
            : $main_selector;

        if ($final_selector) {
            $selectors['single_field'] = $final_selector;
        }

        return $selectors;
    }

    /**
     * Infer picker type from legacy data (v1.x lacks picker_type).
     *
     * @param array $data Legacy data.
     * @return string 'datetimepicker', 'datepicker', or 'timepicker'.
     */
    private function infer_picker_type($data)
    {
        if (isset($data['picker_type']) && in_array($data['picker_type'], array('datepicker', 'timepicker', 'datetimepicker'))) {
            return $data['picker_type'];
        }

        $datepicker = isset($data['datepicker']) && ($data['datepicker'] === 'on' || $data['datepicker'] === '1');
        $timepicker = isset($data['timepicker']) && ($data['timepicker'] === 'on' || $data['timepicker'] === '1');

        if ($datepicker && $timepicker) {
            return 'datetimepicker';
        }
        if ($timepicker) {
            return 'timepicker';
        }
        return 'datepicker';
    }

    /**
     * Migrate general settings.
     *
     * @param array $data Legacy data.
     * @return array v3.0 general_settings structure.
     */
    private function migrate_general_settings($data)
    {
        $general = array(
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'timezone' => '',
            'datepicker_library' => 'xdsoft',
            'datepicker_language' => 'en',
            'datepicker_theme' => 'light',
            'datepicker_display_method' => 'dropdown',
        );

        if (isset($data['dateformat'])) {
            $general['date_format'] = $this->convert_date_format($data['dateformat']);
        }

        if (isset($data['hourformat'])) {
            $general['time_format'] = $this->convert_time_format($data['hourformat']);
        }

        $wp_timezone = function_exists('wp_timezone_string') ? wp_timezone_string() : '';
        if (!empty($wp_timezone)) {
            try {
                $tz = new DateTimeZone($wp_timezone);
                $now = new DateTime('now', new DateTimeZone('UTC'));
                $offset_seconds = $tz->getOffset($now);
                if ($offset_seconds == 0) {
                    $general['timezone'] = 'UTC';
                } else {
                    $hours = (int) ($offset_seconds / 3600);
                    $minutes = abs((int) (($offset_seconds % 3600) / 60));
                    $general['timezone'] = sprintf(
                        '%s%02d:%02d',
                        $offset_seconds >= 0 ? '+' : '-',
                        abs($hours),
                        $minutes
                    );
                }
            } catch (Exception $e) {
                $general['timezone'] = 'UTC';
            }
        }

        if (isset($data['locale']) && $data['locale'] !== 'auto') {
            $general['datepicker_language'] = $this->convert_locale($data['locale']);
        }

        if (isset($data['theme'])) {
            $general['datepicker_theme'] = ($data['theme'] === 'dark') ? 'dark' : 'light';
        }

        if (isset($data['inline']) && ($data['inline'] === 'on' || $data['inline'] === '1')) {
            $general['datepicker_display_method'] = 'inline';
        }

        return $general;
    }
}
