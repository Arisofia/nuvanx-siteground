<?php
/**
 * Admin Form Handlers Trait.
 *
 * Handles admin POST form submissions and saves.
 *
 * @package Availability_Datepicker
 * @since 1.0.0
 */

trait AVDP_Admin_Form_Handlers
{

    /**
     * Handle admin actions.
     *
     * @since 1.0.0
     */
    public function handle_admin_actions()
    {
        if (!isset($_GET['page'])) {
            return;
        }

        $page = sanitize_text_field(wp_unslash($_GET['page']));

        switch ($page) {
            case 'avdp-availability':
                $this->handle_save_availability();
                break;
            case 'avdp-css-selectors':
                $this->handle_save_css_selectors();
                break;
            case 'avdp-settings':
                $this->handle_save_settings();
                break;
        }
    }

    /**
     * Handle availability form submission.
     *
     * Validates and saves weekly hours and availability rules.
     */
    private function handle_save_availability()
    {
        if (isset($_POST['avdp_save_availability']) && check_admin_referer('avdp_save_availability', 'avdp_availability_nonce')) {
            if (!current_user_can('manage_options')) {
                add_settings_error('avdp_messages', 'avdp_permission_denied', __('You do not have permission to save these settings.', 'availability-datepicker'), 'error');
                return;
            }

            $has_errors = false;
            $errors = array();

            $current_rules = AVDP_Settings::get_section('availability_rules');

            // Method (Booking Type)
            $method = isset($_POST['method']) ? sanitize_text_field($_POST['method']) : 'fixed';
            if (!in_array($method, array('fixed', 'daily', 'flexible'))) {
                $method = 'fixed';
            }

            // Time Settings
            $time_settings_raw = isset($_POST['time_settings']) ? $_POST['time_settings'] : array();

            // Validate slot interval
            $slot_interval = isset($time_settings_raw['slot_interval']) ? absint($time_settings_raw['slot_interval']) : 30;
            if ($slot_interval < 1 || $slot_interval > 1440) {
                $errors[] = __('Slot interval must be between 1 and 1440 minutes.', 'availability-datepicker');
                $has_errors = true;
                $slot_interval = 30; // Reset to default
            }

            // Validate min/max days
            $min_days = isset($time_settings_raw['min_days']) ? absint($time_settings_raw['min_days']) : 1;
            $max_days = isset($time_settings_raw['max_days']) ? absint($time_settings_raw['max_days']) : 14;
            if ($min_days > $max_days && $max_days > 0) {
                $errors[] = __('Minimum days cannot be greater than maximum days.', 'availability-datepicker');
                $has_errors = true;
            }

            // Validate min/max duration
            $min_duration = isset($time_settings_raw['min_duration']) ? floatval($time_settings_raw['min_duration']) : 1;
            $max_duration = isset($time_settings_raw['max_duration']) ? floatval($time_settings_raw['max_duration']) : 24;
            if ($min_duration > $max_duration && $max_duration > 0) {
                $errors[] = __('Minimum duration cannot be greater than maximum duration.', 'availability-datepicker');
                $has_errors = true;
            }

            $time_settings = array(
                'slot_interval' => $slot_interval,
                'minimum_notice' => isset($time_settings_raw['minimum_notice']) ? absint($time_settings_raw['minimum_notice']) : 0,
                'buffer_before' => isset($time_settings_raw['buffer_before']) ? absint($time_settings_raw['buffer_before']) : 0,
                'buffer_after' => isset($time_settings_raw['buffer_after']) ? absint($time_settings_raw['buffer_after']) : 0,
                'min_days' => $min_days,
                'max_days' => $max_days,
                'min_duration' => $min_duration,
                'max_duration' => $max_duration,
            );

            // Booking Window
            $bw_raw = isset($_POST['booking_window']) ? $_POST['booking_window'] : array();
            $from_type = isset($bw_raw['from_type']) ? sanitize_text_field($bw_raw['from_type']) : 'dynamic';
            $to_type = isset($bw_raw['to_type']) ? sanitize_text_field($bw_raw['to_type']) : 'dynamic';

            $from_value = ($from_type === 'dynamic')
                ? (isset($bw_raw['from_value_dynamic']) ? absint($bw_raw['from_value_dynamic']) : 0)
                : (isset($bw_raw['from_value_date']) ? sanitize_text_field($bw_raw['from_value_date']) : '');

            $to_value = ($to_type === 'dynamic')
                ? (isset($bw_raw['to_value_dynamic']) ? absint($bw_raw['to_value_dynamic']) : 30)
                : (isset($bw_raw['to_value_date']) ? sanitize_text_field($bw_raw['to_value_date']) : '');

            // Calculate days_from and days_future for the availability engine
            // These are used in is_within_booking_window() and get_calendar_config()
            $days_from = ($from_type === 'dynamic') ? (int) $from_value : 0;
            $days_future = ($to_type === 'dynamic') ? (int) $to_value : 30;

            // If using predefined dates, calculate days from today
            if ($from_type === 'predefined' && !empty($from_value)) {
                $from_timestamp = strtotime($from_value);
                $today_timestamp = strtotime(gmdate('Y-m-d'));
                $days_from = max(0, floor(($from_timestamp - $today_timestamp) / 86400));
            }

            if ($to_type === 'predefined' && !empty($to_value)) {
                $to_timestamp = strtotime($to_value);
                $today_timestamp = strtotime(gmdate('Y-m-d'));
                $days_future = max(1, floor(($to_timestamp - $today_timestamp) / 86400));
            }

            $booking_window = array(
                'from_type' => $from_type,
                'from_value' => $from_value,
                'to_type' => $to_type,
                'to_value' => $to_value,
                // Computed values for availability engine
                'days_from' => $days_from,
                'days_future' => $days_future,
            );

            // Date Overrides
            $do_raw = isset($_POST['date_overrides']) ? $_POST['date_overrides'] : array();

            // Blocked Dates
            $blocked_dates = array();
            if (isset($do_raw['blocked_dates']) && is_array($do_raw['blocked_dates'])) {
                foreach ($do_raw['blocked_dates'] as $date) {
                    $d = sanitize_text_field($date);
                    if (!empty($d)) {
                        $blocked_dates[] = $d;
                    }
                }
            }

            // Allowed Dates
            $allowed_dates = array();
            if (isset($do_raw['allowed_dates']) && is_array($do_raw['allowed_dates'])) {
                foreach ($do_raw['allowed_dates'] as $item) {
                    $d = isset($item['date']) ? sanitize_text_field($item['date']) : '';
                    if (!empty($d)) {
                        $start = isset($item['start']) ? sanitize_text_field($item['start']) : '09:00';
                        $end = isset($item['end']) ? sanitize_text_field($item['end']) : '17:00';

                        // Regex validation for HH:MM format
                        if (!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $start)) {
                            $start = '09:00';
                        }
                        if (!preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $end)) {
                            $end = '17:00';
                        }

                        $allowed_dates[] = array(
                            'date' => $d,
                            'start' => $start,
                            'end' => $end
                        );
                    }
                }
            }

            $date_overrides = array(
                'blocked_dates' => $blocked_dates,
                'allowed_dates' => $allowed_dates
            );

            // Weekly Hours - Strict Sanitization & Validation
            // Always iterate all 7 days regardless of what is in POST.
            // Unchecked checkboxes and disabled <input> elements are not submitted
            // by browsers, so absent days must be treated as disabled (enabled=false).
            $valid_days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
            $weekly_hours = array();
            $post_weekly_hours = isset($_POST['weekly_hours']) && is_array($_POST['weekly_hours']) ? $_POST['weekly_hours'] : array();

            foreach ($valid_days as $day) {
                $data = isset($post_weekly_hours[$day]) ? $post_weekly_hours[$day] : array();

                $clean_day = array();
                $clean_day['enabled'] = isset($data['enabled']) ? (bool) $data['enabled'] : false;
                $clean_day['slots'] = array();

                if (isset($data['slots']) && is_array($data['slots'])) {
                    foreach ($data['slots'] as $slot) {
                        $start = isset($slot['start']) ? sanitize_text_field($slot['start']) : '';
                        $end = isset($slot['end']) ? sanitize_text_field($slot['end']) : '';

                        // Validate Time Format (HH:MM)
                        if (
                            preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $start) &&
                            preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $end)
                        ) {
                            $clean_day['slots'][] = array(
                                'start' => $start,
                                'end' => $end
                            );
                        }
                    }
                }
                $weekly_hours[$day] = $clean_day;
            }
            $current_rules['weekly_hours'] = $weekly_hours;

            // Construct new rules array
            $new_rules = array(
                'method' => $method,
                'weekly_hours' => $current_rules['weekly_hours'],
                'time_settings' => $time_settings,
                'date_overrides' => $date_overrides,
                'booking_window' => $booking_window,
            );

            // Save settings if no errors
            if (!$has_errors) {
                $result = AVDP_Settings::update_section('availability_rules', $new_rules);
                if ($result) {
                    add_settings_error('avdp_messages', 'avdp_availability_saved', __('Availability settings saved successfully.', 'availability-datepicker'), 'success');
                } else {
                    add_settings_error('avdp_messages', 'avdp_save_failed', __('Failed to save availability settings. Please try again.', 'availability-datepicker'), 'error');
                }
            } else {
                // Display validation errors
                foreach ($errors as $error) {
                    add_settings_error('avdp_messages', 'avdp_validation_error', $error, 'error');
                }
            }
        }
    }

    /**
     * Handle CSS Selector form submission.
     *
     * Saves user-defined CSS selectors for frontend integration.
     */
    private function handle_save_css_selectors()
    {
        if (isset($_POST['avdp_save_selectors']) && check_admin_referer('avdp_save_selectors', 'avdp_selectors_nonce')) {
            if (!current_user_can('manage_options')) {
                add_settings_error('avdp_messages', 'avdp_permission_denied', __('You do not have permission to save these settings.', 'availability-datepicker'), 'error');
                return;
            }

            $selectors = array(
                'single_field' => isset($_POST['selector_single']) ? sanitize_text_field(wp_unslash($_POST['selector_single'])) : '',
                'start_date' => isset($_POST['selector_start_date']) ? sanitize_text_field(wp_unslash($_POST['selector_start_date'])) : '',
                'start_time' => isset($_POST['selector_start_time']) ? sanitize_text_field(wp_unslash($_POST['selector_start_time'])) : '',
                'end_date' => isset($_POST['selector_end_date']) ? sanitize_text_field(wp_unslash($_POST['selector_end_date'])) : '',
                'end_time' => isset($_POST['selector_end_time']) ? sanitize_text_field(wp_unslash($_POST['selector_end_time'])) : '',
                'start_datetime' => isset($_POST['selector_start_datetime']) ? sanitize_text_field(wp_unslash($_POST['selector_start_datetime'])) : '',
                'end_datetime' => isset($_POST['selector_end_datetime']) ? sanitize_text_field(wp_unslash($_POST['selector_end_datetime'])) : '',
            );

            $has_errors = false;
            foreach ($selectors as $key => $selector) {
                if (!empty($selector) && preg_match('/[<>"\']/', $selector)) {
                    add_settings_error(
                        'avdp_messages',
                        'avdp_invalid_selector_' . $key,
                        sprintf(
                            __('Invalid characters detected in selector "%s". Characters < > " \' are not allowed in CSS selectors.', 'availability-datepicker'),
                            $key
                        ),
                        'error'
                    );
                    $has_errors = true;
                }
            }

            if ($has_errors) {
                return;
            }

            $result = AVDP_Settings::update_section('css_selectors', $selectors);
            if ($result) {
                add_settings_error('avdp_messages', 'avdp_selectors_saved', __('CSS selectors saved successfully.', 'availability-datepicker'), 'success');
            } else {
                add_settings_error('avdp_messages', 'avdp_save_failed', __('Failed to save CSS selectors. Please try again.', 'availability-datepicker'), 'error');
            }
        }
    }

    /**
     * Handle General Settings form submission.
     *
     * Saves date and time format preferences.
     */
    private function handle_save_settings()
    {
        if (isset($_POST['avdp_save_settings']) && check_admin_referer('avdp_save_settings', 'avdp_settings_nonce')) {
            if (!current_user_can('manage_options')) {
                add_settings_error('avdp_messages', 'avdp_permission_denied', __('You do not have permission to save these settings.', 'availability-datepicker'), 'error');
                return;
            }

            // Validate date format
            $date_format = isset($_POST['date_format']) ? sanitize_text_field(wp_unslash($_POST['date_format'])) : 'Y-m-d';
            $valid_date_formats = array('Y-m-d', 'd-m-Y', 'm-d-Y', 'd/m/Y', 'm/d/Y', 'Y/m/d', 'd.m.Y', 'j F Y', 'F j, Y');
            if (!in_array($date_format, $valid_date_formats)) {
                add_settings_error('avdp_messages', 'avdp_invalid_date_format', __('Invalid date format selected.', 'availability-datepicker'), 'warning');
                $date_format = 'Y-m-d'; // Reset to default
            }

            // Validate time format
            $time_format = isset($_POST['time_format']) ? sanitize_text_field(wp_unslash($_POST['time_format'])) : 'H:i';
            $valid_time_formats = array('H:i', 'g:i a', 'g:i A');
            if (!in_array($time_format, $valid_time_formats)) {
                add_settings_error('avdp_messages', 'avdp_invalid_time_format', __('Invalid time format selected.', 'availability-datepicker'), 'warning');
                $time_format = 'H:i'; // Reset to default
            }

            // Validate timezone
            $timezone = isset($_POST['timezone']) ? sanitize_text_field(wp_unslash($_POST['timezone'])) : '';
            if (!empty($timezone) && $timezone !== 'UTC') {
                // Validate timezone format (should be +HH:MM or -HH:MM or UTC)
                if (!preg_match('/^(UTC|[+-]\d{2}:\d{2})$/', $timezone)) {
                    add_settings_error('avdp_messages', 'avdp_invalid_timezone', __('Invalid timezone format. Please use UTC or offset format (e.g., +05:00).', 'availability-datepicker'), 'warning');
                    $timezone = 'UTC';
                }
            }

            $settings = array(
                'date_format' => $date_format,
                'time_format' => $time_format,
                'timezone' => $timezone,
                'datepicker_library' => isset($_POST['datepicker_library']) ? sanitize_text_field(wp_unslash($_POST['datepicker_library'])) : 'xdsoft',
                'datepicker_language' => isset($_POST['datepicker_language']) ? sanitize_text_field(wp_unslash($_POST['datepicker_language'])) : 'en',
                'datepicker_theme' => isset($_POST['datepicker_theme']) ? sanitize_text_field(wp_unslash($_POST['datepicker_theme'])) : 'light',
                'datepicker_display_method' => isset($_POST['datepicker_display_method']) ? sanitize_text_field(wp_unslash($_POST['datepicker_display_method'])) : 'dropdown',
            );

            $result = AVDP_Settings::update_section('general_settings', $settings);
            if ($result) {
                add_settings_error('avdp_messages', 'avdp_settings_saved', __('Settings saved successfully.', 'availability-datepicker'), 'success');
            } else {
                add_settings_error('avdp_messages', 'avdp_save_failed', __('Failed to save settings. Please try again.', 'availability-datepicker'), 'error');
            }
        }
    }
}
