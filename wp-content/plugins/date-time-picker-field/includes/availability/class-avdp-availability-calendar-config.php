<?php
/**
 * Availability Calendar Config Trait.
 *
 * Handles calendar configuration generation for the frontend.
 *
 * @package Availability_Datepicker
 * @since 2.4.0.13
 */

trait AVDP_Availability_Calendar_Config
{

    /**
     * Get Calendar Configuration for Frontend.
     *
     * @since 2.4.0
     * @return array
     */
    public function get_calendar_config()
    {
        $settings_hash = md5(serialize(AVDP_Settings::get_all()));
        $cache_key = 'avdp_calendar_config_' . $settings_hash;

        $cached = get_transient($cache_key);
        if (false !== $cached) {
            if (!isset($cached['allowed_dates_with_times'])) {
                $cached['allowed_dates_with_times'] = array();
            }
            // Recalculate date-dependent fields (min_date, max_date) as they change daily
            $cached['min_date'] = $this->calculate_min_date();
            $cached['max_date'] = $this->calculate_max_date();
            return $cached;
        }

        $rules = AVDP_Settings::get_section('availability_rules');
        $gen_rules = AVDP_Settings::get_section('general_settings');

        $config = array(
            'booking_type' => $this->get_booking_type(),
            'min_date' => $this->calculate_min_date(),
            'max_date' => $this->calculate_max_date(),
            'disabled_dates' => array(),
            'disabled_weekdays' => array(),
            'exempted_dates' => array(),
            'allowed_dates_with_times' => array(),
            'time_24hr' => isset($gen_rules['time_format']) && (
                $gen_rules['time_format'] === '24' ||
                $gen_rules['time_format'] === 'H:i' ||
                preg_match('/[HG]/', $gen_rules['time_format']) === 1
            ),
            'slot_interval' => 30
        );

        if (isset($rules['time_settings'])) {
            if (isset($rules['time_settings']['slot_interval'])) {
                $config['slot_interval'] = (int) $rules['time_settings']['slot_interval'];
            }
            if ($config['booking_type'] === 'flexible') {
                $config['min_days'] = 0;
                $config['max_days'] = 0;
            } else {
                $config['min_days'] = array_key_exists('min_days', $rules['time_settings']) ? (int) $rules['time_settings']['min_days'] : 1;
                $config['max_days'] = array_key_exists('max_days', $rules['time_settings']) ? (int) $rules['time_settings']['max_days'] : 14;
            }
            $config['min_duration'] = array_key_exists('min_duration', $rules['time_settings']) ? (float) $rules['time_settings']['min_duration'] : 1;
            $config['max_duration'] = array_key_exists('max_duration', $rules['time_settings']) ? (float) $rules['time_settings']['max_duration'] : 24;
            $config['buffer_before'] = array_key_exists('buffer_before', $rules['time_settings']) ? (int) $rules['time_settings']['buffer_before'] : 0;
            $config['buffer_after'] = array_key_exists('buffer_after', $rules['time_settings']) ? (int) $rules['time_settings']['buffer_after'] : 0;
            $config['minimum_notice'] = array_key_exists('minimum_notice', $rules['time_settings']) ? (int) $rules['time_settings']['minimum_notice'] : 0;
        }

        $config['weekly_hours'] = isset($rules['weekly_hours']) ? $rules['weekly_hours'] : array();

        if (isset($rules['date_overrides']) && is_array($rules['date_overrides'])) {
            $overrides = $rules['date_overrides'];

            if (isset($overrides['blocked_dates']) && is_array($overrides['blocked_dates'])) {
                $config['disabled_dates'] = array_values(array_filter(
                    array_map(function($date) {
                        if (is_string($date) && !empty($date)) {
                            if (strlen($date) > 10) {
                                $date = substr($date, 0, 10);
                            }
                            return $date;
                        }
                        return null;
                    }, $overrides['blocked_dates']),
                    function($date) {
                        return !empty($date);
                    }
                ));
            }

            if (isset($overrides['allowed_dates']) && is_array($overrides['allowed_dates'])) {
                $config['exempted_dates'] = array_values(array_filter(
                    array_map(function ($item) {
                        if (is_array($item) && isset($item['date'])) {
                            $date = $item['date'];
                            if (strlen($date) > 10) {
                                $date = substr($date, 0, 10);
                            }
                            return $date;
                        } elseif (is_string($item)) {
                            if (strlen($item) > 10) {
                                $item = substr($item, 0, 10);
                            }
                            return $item;
                        }
                        return null;
                    }, $overrides['allowed_dates']),
                    function($date) {
                        return !empty($date);
                    }
                ));

                $config['allowed_dates_with_times'] = array_values(array_filter(
                    array_map(function ($item) {
                        if (is_array($item) && isset($item['date'])) {
                            $date = $item['date'];
                            if (strlen($date) > 10) {
                                $date = substr($date, 0, 10);
                            }
                            return array(
                                'date' => $date,
                                'start' => isset($item['start']) ? $item['start'] : '09:00',
                                'end' => isset($item['end']) ? $item['end'] : '17:00'
                            );
                        }
                        return null;
                    }, $overrides['allowed_dates']),
                    function($item) {
                        return !empty($item) && is_array($item) && isset($item['date']);
                    }
                ));
            }
        }

        // Weekly Hours -> Disabled Weekdays (JS Date.getDay(): Sunday=0 ... Saturday=6)
        if (isset($rules['weekly_hours'])) {
            $wh = $rules['weekly_hours'];
            $map = ['sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6];
            foreach ($map as $name => $idx) {
                if (empty($wh[$name]['enabled'])) {
                    $config['disabled_weekdays'][] = $idx;
                }
            }
        }

        if (!isset($config['allowed_dates_with_times'])) {
            $config['allowed_dates_with_times'] = array();
        }

        set_transient($cache_key, $config, HOUR_IN_SECONDS);

        return $config;
    }
}
