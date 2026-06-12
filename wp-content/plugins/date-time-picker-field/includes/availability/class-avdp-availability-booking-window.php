<?php
/**
 * Availability Booking Window Trait.
 *
 * Handles booking window and min/max date calculations.
 *
 * @package Availability_Datepicker
 * @since 2.4.0.13
 */

trait AVDP_Availability_Booking_Window
{

    /**
     * Calculate minimum date based on current time and settings.
     *
     * @since 2.4.0.13
     * @return string Date string in Y-m-d or Y-m-d H:i format.
     */
    private function calculate_min_date()
    {
        $rules = AVDP_Settings::get_section('availability_rules');
        $booking_window = $rules['booking_window'] ?? array();
        $booking_type = $this->get_booking_type();
        $today = AVDP_DateTime::now_utc_formatted('Y-m-d');
        $min_date = $today;

        // Handle predefined dates first
        if (isset($booking_window['from_type']) && $booking_window['from_type'] === 'predefined' && !empty($booking_window['from_value'])) {
            $predefined_date = $booking_window['from_value'];
            // Extract date part if it includes time
            if (strlen($predefined_date) > 10) {
                $predefined_date = substr($predefined_date, 0, 10);
            }

            // Validate predefined date is not in the past
            $predefined_timestamp = strtotime($predefined_date);
            $today_timestamp = strtotime($today);
            if ($predefined_timestamp < $today_timestamp) {
                // Predefined date in past, use today instead
                $predefined_date = $today;
            }

            $min_date = $predefined_date;

            // For daily bookings, always return date-only format
            if ($booking_type === 'daily') {
                return $min_date;
            }

            // For fixed/flexible, check if minimum_notice should add time component
            $minimum_notice = isset($rules['time_settings']['minimum_notice']) ? (int) $rules['time_settings']['minimum_notice'] : 0;
            if ($minimum_notice > 0) {
                // Use predefined date + minimum_notice minutes
                $min_timestamp = AVDP_DateTime::add_interval_timestamp(AVDP_DateTime::parse_utc($min_date . ' 00:00:00'), $minimum_notice, 'minutes');
                $min_date = gmdate('Y-m-d H:i', $min_timestamp);
            }
            return $min_date;
        }

        // Handle dynamic booking window
        $days_from = isset($booking_window['days_from']) ? (int) $booking_window['days_from'] : 0;
        // Ensure days_from is not negative
        if ($days_from < 0) {
            $days_from = 0;
        }
        $minimum_notice = isset($rules['time_settings']['minimum_notice']) ? (int) $rules['time_settings']['minimum_notice'] : 0;

        // Calculate base date from days_from
        if ($days_from > 0) {
            $min_date = AVDP_DateTime::add_interval($today, $days_from, 'days');
        }

        // For daily bookings, always return date-only format (ignore minimum_notice)
        if ($booking_type === 'daily') {
            return $min_date;
        }

        // For fixed/flexible bookings, apply minimum_notice if set
        // Minimum notice takes precedence - it's the earliest time a booking can be made
        if ($minimum_notice > 0) {
            $min_timestamp = AVDP_DateTime::add_interval_timestamp(AVDP_DateTime::now_utc(), $minimum_notice, 'minutes');
            $min_date_with_notice = gmdate('Y-m-d H:i', $min_timestamp);

            // Compare: use the later of days_from date OR minimum_notice datetime
            $min_date_start_of_day = AVDP_DateTime::parse_utc($min_date . ' 00:00:00');
            $min_notice_timestamp = strtotime($min_date_with_notice);

            if ($min_notice_timestamp >= $min_date_start_of_day) {
                // Minimum notice datetime is on or after days_from date, use it
                $min_date = $min_date_with_notice;
            } else {
                // days_from date is later, but we still need to respect minimum_notice
                // Use days_from date with time from minimum_notice calculation
                $min_date = gmdate('Y-m-d', $min_date_start_of_day) . ' ' . gmdate('H:i', $min_notice_timestamp);
            }
        }

        return $min_date;
    }

    /**
     * Calculate maximum date based on current time and settings.
     *
     * @since 2.4.0.13
     * @return string Date string in Y-m-d format.
     */
    private function calculate_max_date()
    {
        $rules = AVDP_Settings::get_section('availability_rules');
        $booking_window = $rules['booking_window'] ?? array();
        $today = AVDP_DateTime::now_utc_formatted('Y-m-d');
        $max_date = AVDP_DateTime::add_interval($today, 30, 'days'); // Default 30 days

        // Handle predefined dates first
        if (isset($booking_window['to_type']) && $booking_window['to_type'] === 'predefined' && !empty($booking_window['to_value'])) {
            $predefined_date = $booking_window['to_value'];
            // Extract date part if it includes time
            if (strlen($predefined_date) > 10) {
                $predefined_date = substr($predefined_date, 0, 10);
            }

            // Validate predefined date is not in the past
            $predefined_timestamp = strtotime($predefined_date);
            $today_timestamp = strtotime($today);
            if ($predefined_timestamp < $today_timestamp) {
                // Predefined date in past, use today as minimum
                $predefined_date = $today;
            }

            // Use predefined date directly
            return $predefined_date;
        }

        // Apply days_future if set (for dynamic booking window)
        if (isset($booking_window['days_future']) && $booking_window['days_future'] !== '') {
            $days = (int) $booking_window['days_future'];
            // Validate: must be positive and reasonable (default to 30 if invalid)
            if ($days <= 0 || $days > 3650) {
                $days = 30; // Default to 30 days
            }
            $max_date = AVDP_DateTime::add_interval($today, $days, 'days');
        }

        return $max_date;
    }

    /**
     * Check if date is within the booking window.
     *
     * @param string $date Date in Y-m-d format (UTC).
     * @return bool True if within window.
     */
    public function is_within_booking_window($date)
    {
        $rules = AVDP_Settings::get_section('availability_rules');
        $window = $rules['booking_window'] ?? array();
        $today = AVDP_DateTime::now_utc_formatted('Y-m-d');
        $target = AVDP_DateTime::start_of_day($date);

        // Calculate min_date - handle predefined dates first
        $min_date = null;
        if (isset($window['from_type']) && $window['from_type'] === 'predefined' && !empty($window['from_value'])) {
            $predefined_date = $window['from_value'];
            // Extract date part if it includes time
            if (strlen($predefined_date) > 10) {
                $predefined_date = substr($predefined_date, 0, 10);
            }

            // Validate predefined date is not in the past
            $predefined_timestamp = strtotime($predefined_date);
            $today_timestamp = strtotime($today);
            if ($predefined_timestamp < $today_timestamp) {
                // Predefined date in past, use today instead
                $predefined_date = $today;
            }

            $min_date = AVDP_DateTime::start_of_day($predefined_date);
        } else {
            // Handle dynamic booking window
            $days_from = isset($window['days_from']) ? (int) $window['days_from'] : 0;
            if ($days_from < 0) {
                $days_from = 0;
            }
            $today_start = AVDP_DateTime::start_of_day($today);
            $min_date = AVDP_DateTime::add_interval_timestamp($today_start, $days_from, 'days');
        }

        // Calculate max_date - handle predefined dates first
        $max_date = null;
        if (isset($window['to_type']) && $window['to_type'] === 'predefined' && !empty($window['to_value'])) {
            $predefined_date = $window['to_value'];
            // Extract date part if it includes time
            if (strlen($predefined_date) > 10) {
                $predefined_date = substr($predefined_date, 0, 10);
            }

            // Validate predefined date is not in the past
            $predefined_timestamp = strtotime($predefined_date);
            $today_timestamp = strtotime($today);
            if ($predefined_timestamp < $today_timestamp) {
                // Predefined date in past, use today as minimum
                $predefined_date = $today;
            }

            // For inclusive boundaries, use end_of_day so the entire end date is included
            $max_date = AVDP_DateTime::end_of_day($predefined_date);
        } else {
            // Handle dynamic booking window
            $days_future = isset($window['days_future']) ? (int) $window['days_future'] : 30;
            if ($days_future <= 0 || $days_future > 3650) {
                $days_future = 30; // Default to 30 days
            }
            $today_start = AVDP_DateTime::start_of_day($today);
            $max_date_timestamp = AVDP_DateTime::add_interval_timestamp($today_start, $days_future, 'days');
            // For inclusive boundaries, use end_of_day
            $max_date = AVDP_DateTime::end_of_day(gmdate('Y-m-d', $max_date_timestamp));
        }

        // Date must be within the window (from min_date to max_date, inclusive)
        // target is start_of_day($date), so it should be >= min_date and <= max_date (end_of_day)
        return ($target >= $min_date && $target <= $max_date);
    }
}
