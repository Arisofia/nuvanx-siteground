<?php
/**
 * Availability Engine.
 *
 * Calculates available time slots based on settings.
 *
 * @package Availability_Datepicker
 * @since 2.4.0.13
 */

class AVDP_Availability
{
    use AVDP_Availability_Slots;
    use AVDP_Availability_Date_Checks;
    use AVDP_Availability_Booking_Window;
    use AVDP_Availability_Calendar_Config;

    /**
     * Get the current booking type.
     *
     * @return string 'fixed', 'daily', or 'flexible'
     */
    public function get_booking_type()
    {
        $rules = AVDP_Settings::get_section('availability_rules');
        $type = isset($rules['method']) ? $rules['method'] : 'fixed';
        return in_array($type, array('fixed', 'daily', 'flexible')) ? $type : 'fixed';
    }
}
