<?php
/**
 * Availability Date Checks Trait.
 *
 * Handles date open/closed determination logic.
 *
 * @package Availability_Datepicker
 * @since 2.4.0.13
 */

trait AVDP_Availability_Date_Checks
{

    /**
     * Check if a specific date is available (Open).
     *
     * @since 1.0.0
     * @param string $date Date in Y-m-d format.
     * @return bool True if available.
     */
    public function is_date_available($date)
    {
        if (!$this->is_date_open($date)) {
            return false;
        }

        // For daily bookings, we don't need slots
        if ($this->get_booking_type() === 'daily') {
            return true;
        }

        return !empty($this->get_available_slots($date));
    }

    /**
     * Check if date is open based on rules (Overrides, Window, Limits).
     *
     * Priority order:
     * 1. Blocked dates - always closed (highest priority)
     * 2. Exempted dates - always open (overrides window and weekly hours)
     * 3. Booking window - must be within window
     * 4. Weekly hours - day must be enabled
     * 5. Booking limits - check capacity/limits
     *
     * @param string $date Date in Y-m-d format
     * @return bool True if date is open/available
     */
    public function is_date_open($date)
    {
        // Priority 1: Check blocked dates first (highest priority - always closed)
        if ($this->is_date_blocked($date)) {
            return false;
        }

        // Priority 2: Check if date is exempted (allowed dates override everything except blocked)
        $is_exempted = $this->is_date_exempted($date);

        // Priority 3: Check booking window (unless exempted)
        if (!$is_exempted && !$this->is_within_booking_window($date)) {
            return false;
        }

        // Priority 4: Check weekly hours status (unless exempted)
        if (!$is_exempted) {
            $rules = AVDP_Settings::get_section('availability_rules');
            $day = AVDP_DateTime::get_day_of_week($date);
            $weekly_hours = $rules['weekly_hours'] ?? array();

            // Check if day is enabled
            $day_enabled = isset($weekly_hours[$day]['enabled']) && $weekly_hours[$day]['enabled'];

            if (!$day_enabled) {
                return false;
            }
        }

        // Priority 5: Check booking limits
        if (!$this->check_limits($date)) {
            return false;
        }

        return true;
    }

    /**
     * Check if a date is manually blocked.
     *
     * @param string $date Date in Y-m-d format.
     * @return bool True if blocked.
     */
    private function is_date_blocked($date)
    {
        $rules = AVDP_Settings::get_section('availability_rules');
        $overrides = $rules['date_overrides'] ?? array();
        $blocked = $overrides['blocked_dates'] ?? array();
        return in_array($date, $blocked);
    }

    /**
     * Check if a date is exempted from logic (Allowed).
     *
     * @param string $date Date in Y-m-d format.
     * @return bool True if exempted.
     */
    private function is_date_exempted($date)
    {
        $rules = AVDP_Settings::get_section('availability_rules');
        $overrides = $rules['date_overrides'] ?? array();
        $allowed = $overrides['allowed_dates'] ?? array();

        // Allowed dates is array of arrays: [['date' => 'Y-m-d', ...], ...]
        foreach ($allowed as $item) {
            if (isset($item['date']) && $item['date'] === $date) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check booking limits (placeholder).
     *
     * @param string $date Date in Y-m-d format.
     * @return bool Always true in Base version.
     */
    private function check_limits($date)
    {
        return true;
    }
}
