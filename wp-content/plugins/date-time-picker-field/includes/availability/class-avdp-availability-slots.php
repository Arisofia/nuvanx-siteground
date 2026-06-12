<?php
/**
 * Availability Slots Trait.
 *
 * Handles time slot generation logic.
 *
 * @package Availability_Datepicker
 * @since 2.4.0.13
 */

trait AVDP_Availability_Slots
{

    /**
     * Get available time slots for a specific date.
     *
     * @since 1.0.0
     * @param string $date Date in Y-m-d format.
     * @return array Array of available time slots.
     */
    public function get_available_slots($date)
    {
        if (!$this->is_date_open($date)) {
            return array();
        }

        $rules = AVDP_Settings::get_section('availability_rules');

        $base_slots = $this->get_base_slots($date, $rules);

        if (empty($base_slots) && $this->is_date_exempted($date)) {
            $base_slots = $this->get_slots_for_exempted_date($date, $rules);
        }

        return $this->apply_time_settings($base_slots, $date, $rules);
    }

    /**
     * Generate time slots for an exempted date using its custom time range.
     *
     * @param string $date  Date in Y-m-d format.
     * @param array  $rules Availability rules.
     * @return array Array of 'H:i' slot strings, sorted ascending.
     */
    private function get_slots_for_exempted_date($date, $rules)
    {
        $overrides = $rules['date_overrides'] ?? array();
        $allowed   = $overrides['allowed_dates'] ?? array();

        foreach ($allowed as $item) {
            if (!isset($item['date']) || $item['date'] !== $date) {
                continue;
            }
            $start = isset($item['start']) ? $item['start'] : null;
            $end   = isset($item['end'])   ? $item['end']   : null;
            if (!$start || !$end) {
                return array();
            }

            $time_settings = isset($rules['time_settings']) ? $rules['time_settings'] : array();
            $interval      = isset($time_settings['slot_interval']) ? (int) $time_settings['slot_interval'] : 30;
            $buffer_before = isset($time_settings['buffer_before']) ? (int) $time_settings['buffer_before'] : 0;
            $buffer_after  = isset($time_settings['buffer_after'])  ? (int) $time_settings['buffer_after']  : 0;
            if ($interval <= 0) {
                $interval = 30;
            }

            $range_start_time = AVDP_DateTime::parse_utc($date . ' ' . $start);
            $range_end_time   = AVDP_DateTime::parse_utc($date . ' ' . $end);

            // Handle cross-midnight ranges (e.g. 22:00–02:00)
            if ($range_end_time <= $range_start_time) {
                $range_end_time = AVDP_DateTime::add_interval_timestamp(
                    AVDP_DateTime::parse_utc($date . ' ' . $end),
                    1,
                    'days'
                );
            }

            $effective_start = $range_start_time + ($buffer_before * 60);
            $current         = $effective_start;
            $increment       = ($interval + $buffer_after) * 60;
            if ($increment <= 0) {
                $increment = $interval * 60;
            }

            $slots      = array();
            $iterations = 0;
            while (($current + ($interval * 60)) <= $range_end_time && $iterations < 1440) {
                $slots[] = gmdate('H:i', $current);
                $current += $increment;
                $iterations++;
            }

            sort($slots);
            return $slots;
        }

        return array();
    }

    /**
     * Get base time slots from weekly hours.
     *
     * @param string $date  Date in Y-m-d format.
     * @param array  $rules Availability rules from settings.
     * @return array Array of time slots.
     */
    private function get_base_slots($date, $rules)
    {
        $today_slots = $this->get_slots_from_rules_for_date($date, $date, $rules);

        $yesterday = AVDP_DateTime::add_interval($date, -1, 'days');
        $yesterday_slots = $this->get_slots_from_rules_for_date($yesterday, $date, $rules);

        $all_slots = array_merge($today_slots, $yesterday_slots);
        sort($all_slots);
        return array_unique($all_slots);
    }

    /**
     * Generate slots from a specific rule-day that fall on the target date.
     *
     * @param string $rule_date   The date whose weekly-hours rules are applied.
     * @param string $target_date The date slots must land on.
     * @param array  $rules       Rules array.
     * @return array List of H:i strings.
     */
    private function get_slots_from_rules_for_date($rule_date, $target_date, $rules)
    {
        $slots = array();
        $day_of_week = AVDP_DateTime::get_day_of_week($rule_date);
        $weekly_hours = isset($rules['weekly_hours']) ? $rules['weekly_hours'] : array();

        if (empty($weekly_hours) || !isset($weekly_hours[$day_of_week])) {
            return $slots;
        }

        $day_settings = $weekly_hours[$day_of_week];
        if (empty($day_settings['enabled']) || empty($day_settings['slots'])) {
            return $slots;
        }

        $time_settings = isset($rules['time_settings']) ? $rules['time_settings'] : array();
        $interval = isset($time_settings['slot_interval']) ? (int) $time_settings['slot_interval'] : 30;
        $buffer_before = isset($time_settings['buffer_before']) ? (int) $time_settings['buffer_before'] : 0;
        $buffer_after = isset($time_settings['buffer_after']) ? (int) $time_settings['buffer_after'] : 0;

        if ($interval <= 0) {
            $interval = 30;
        }

        $target_start = AVDP_DateTime::start_of_day($target_date);
        $target_end = AVDP_DateTime::end_of_day($target_date);

        foreach ($day_settings['slots'] as $time_range) {
            $range_start_time = AVDP_DateTime::parse_utc($rule_date . ' ' . $time_range['start']);
            $range_end_time = AVDP_DateTime::parse_utc($rule_date . ' ' . $time_range['end']);
            $is_cross_midnight = false;

            // Handle cross-midnight ranges (e.g., 22:00-02:00)
            if ($range_end_time <= $range_start_time) {
                $is_cross_midnight = true;
                $range_end_time = AVDP_DateTime::add_interval_timestamp(
                    AVDP_DateTime::parse_utc($rule_date . ' ' . $time_range['end']),
                    1,
                    'days'
                );
            }

            $effective_start_time = $range_start_time + ($buffer_before * 60);
            $current_time = $effective_start_time;
            $increment = ($interval + $buffer_after) * 60;

            if ($increment <= 0) {
                $increment = $interval * 60;
            }

            $max_iterations = 1440;
            $iteration_count = 0;

            while (($current_time + ($interval * 60)) <= $range_end_time && $iteration_count < $max_iterations) {
                if ($is_cross_midnight && $rule_date === $target_date) {
                    $rule_date_start = AVDP_DateTime::start_of_day($rule_date);
                    if ($current_time >= $rule_date_start && $current_time < $range_end_time) {
                        $slots[] = gmdate('H:i', $current_time);
                    }
                } else {
                    if ($current_time >= $target_start && $current_time <= $target_end) {
                        $slots[] = gmdate('H:i', $current_time);
                    }
                }
                $current_time += $increment;
                $iteration_count++;
            }
        }

        return $slots;
    }

    /**
     * Apply time settings (minimum notice) to filter slots for the given date.
     *
     * For today: always filter out past slots (minimum_notice = 0 filters by current time).
     * For future dates: all slots are returned unchanged.
     *
     * @param array  $slots Base slots.
     * @param string $date  Date in Y-m-d format.
     * @param array  $rules Rules.
     * @return array Filtered slots.
     */
    private function apply_time_settings($slots, $date, $rules)
    {
        $time_settings    = isset($rules['time_settings']) ? $rules['time_settings'] : array();
        $minimum_notice   = isset($time_settings['minimum_notice']) ? (int) $time_settings['minimum_notice'] : 0;

        // Compare date against today in the plugin's timezone (matches filterTodaySlots in JS)
        $now_utc       = AVDP_DateTime::now_utc();
        $today_in_tz   = AVDP_DateTime::utc_to_local($now_utc, 'Y-m-d');
        if ($date !== $today_in_tz) {
            return $slots;
        }

        // Get current H:i in plugin timezone and convert to minutes — mirrors filterTodaySlots()
        $current_hhmm  = AVDP_DateTime::utc_to_local($now_utc, 'H:i');
        $current_parts = explode(':', $current_hhmm);
        $current_mins  = (int) $current_parts[0] * 60 + (int) $current_parts[1];
        $threshold     = $current_mins + $minimum_notice;

        $filtered_slots = array();
        foreach ($slots as $slot) {
            $slot_parts = explode(':', $slot);
            $slot_mins  = (int) $slot_parts[0] * 60 + (int) $slot_parts[1];
            if ($slot_mins >= $threshold) {
                $filtered_slots[] = $slot;
            }
        }

        return $filtered_slots;
    }
}
