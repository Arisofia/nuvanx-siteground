<?php
/**
 * Admin AJAX Handlers Trait.
 *
 * Handles admin AJAX requests.
 *
 * @package Availability_Datepicker
 * @since 2.4.0
 */

trait AVDP_Admin_Ajax
{

    /**
     * AJAX Handler: Get Week Preview
     *
     * Returns full availability + actual slot arrays for a set of dates,
     * using the same AVDP_Availability engine as the public datepicker.
     *
     * @since 2.4.0.13
     */
    public function handle_get_week_preview()
    {
        check_ajax_referer('avdp_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $raw_dates = isset($_POST['dates']) ? (array) $_POST['dates'] : array();
        if (empty($raw_dates)) {
            wp_send_json_error('No dates provided');
        }

        $availability = new AVDP_Availability();
        $booking_type = $availability->get_booking_type();
        $is_slot_mode = in_array($booking_type, array('fixed', 'flexible'), true);

        $data = array();
        foreach ($raw_dates as $raw) {
            $date_str = sanitize_text_field($raw);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_str)) {
                continue;
            }

            $available = $availability->is_date_available($date_str);
            $slots     = array();

            if ($available && $is_slot_mode) {
                $slots = $availability->get_available_slots($date_str);
            }

            $data[$date_str] = array(
                'available' => $available,
                'slots'     => $slots,
            );
        }

        wp_send_json_success(array(
            'booking_type' => $booking_type,
            'data'         => $data,
        ));
    }
}
