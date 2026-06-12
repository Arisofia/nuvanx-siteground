<?php
/**
 * Date/Time Utility Class
 *
 * Handles all date/time operations using UTC best practices.
 * All storage and calculations are done in UTC.
 * Display conversions apply user timezone settings.
 *
 * @package Availability_Datepicker
 * @since 2.4.0.13
 */

class AVDP_DateTime
{
    /**
     * @var int|null
     */
    private static $test_time = null;

    /**
     * @param int|null $timestamp UTC Unix timestamp, or null to reset.
     */
    public static function set_test_time( $timestamp )
    {
        self::$test_time = $timestamp;
    }

    /**
     * Get current UTC timestamp.
     *
     * @return int UTC timestamp
     */
    public static function now_utc()
    {
        return self::$test_time !== null ? self::$test_time : time();
    }

    /**
     * Get current date/time in UTC.
     *
     * @param string $format Date format (default: 'Y-m-d H:i:s')
     * @return string Formatted UTC date/time
     */
    public static function now_utc_formatted($format = 'Y-m-d H:i:s')
    {
        return gmdate($format, self::now_utc());
    }

    /**
     * Convert UTC timestamp to user's timezone.
     *
     * @param int $utc_timestamp UTC timestamp
     * @param string $format Output format
     * @return string Formatted date/time in user's timezone
     */
    public static function utc_to_local($utc_timestamp, $format = 'Y-m-d H:i:s')
    {
        $settings = AVDP_Settings::get_section('general_settings');
        $timezone_offset = isset($settings['timezone']) ? $settings['timezone'] : '+00:00';

        if (function_exists('wp_date')) {
            return wp_date($format, $utc_timestamp, new DateTimeZone(self::offset_to_timezone($timezone_offset)));
        }

        try {
            $dt = new DateTime('@' . $utc_timestamp, new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone(self::offset_to_timezone($timezone_offset)));
            return $dt->format($format);
        } catch (Exception $e) {
            return gmdate($format, $utc_timestamp);
        }
    }

    /**
     * Convert local time (user timezone) to UTC timestamp.
     *
     * @param string $date_string Date string in user's timezone
     * @return int UTC timestamp
     */
    public static function local_to_utc($date_string)
    {
        $settings = AVDP_Settings::get_section('general_settings');
        $timezone_offset = isset($settings['timezone']) ? $settings['timezone'] : '+00:00';

        try {
            $dt = new DateTime($date_string, new DateTimeZone(self::offset_to_timezone($timezone_offset)));
            return $dt->getTimestamp();
        } catch (Exception $e) {
            return strtotime($date_string);
        }
    }

    /**
     * Parse date string to UTC timestamp.
     *
     * @param string $date_string Date string (assumed UTC unless timezone specified)
     * @return int UTC timestamp
     */
    public static function parse_utc($date_string)
    {
        try {
            $dt = new DateTime($date_string, new DateTimeZone('UTC'));
            return $dt->getTimestamp();
        } catch (Exception $e) {
            return strtotime($date_string);
        }
    }

    /**
     * Get day of week for a UTC date.
     *
     * @param string $date Date in Y-m-d format (UTC)
     * @return string Day name (lowercase, e.g., 'monday')
     */
    public static function get_day_of_week($date)
    {
        return strtolower(gmdate('l', self::parse_utc($date)));
    }

    /**
     * Add interval to UTC date.
     *
     * @param string $date Base date (Y-m-d format, UTC)
     * @param int $amount Amount to add
     * @param string $unit Unit: 'days', 'hours', 'minutes', 'seconds'
     * @return string New date in Y-m-d format (UTC)
     */
    public static function add_interval($date, $amount, $unit = 'days')
    {
        $timestamp = self::parse_utc($date);

        switch ($unit) {
            case 'days':
                $timestamp += $amount * 86400;
                break;
            case 'hours':
                $timestamp += $amount * 3600;
                break;
            case 'minutes':
                $timestamp += $amount * 60;
                break;
            case 'seconds':
                $timestamp += $amount;
                break;
        }

        return gmdate('Y-m-d', $timestamp);
    }

    /**
     * Add interval and return timestamp.
     *
     * @param int $timestamp Base timestamp (UTC)
     * @param int $amount Amount to add
     * @param string $unit Unit: 'days', 'hours', 'minutes', 'seconds'
     * @return int New timestamp (UTC)
     */
    public static function add_interval_timestamp($timestamp, $amount, $unit = 'days')
    {
        switch ($unit) {
            case 'days':
                return $timestamp + ($amount * 86400);
            case 'hours':
                return $timestamp + ($amount * 3600);
            case 'minutes':
                return $timestamp + ($amount * 60);
            case 'seconds':
                return $timestamp + $amount;
            default:
                return $timestamp;
        }
    }

    /**
     * Convert timezone offset to a PHP-valid DateTimeZone string.
     *
     * @param string $offset Offset string (e.g., '+05:30', '-08:00', 'UTC')
     * @return string Valid DateTimeZone identifier
     */
    private static function offset_to_timezone($offset)
    {
        if (in_array($offset, timezone_identifiers_list())) {
            return $offset;
        }

        if (preg_match('/^([+-])(\d{2}):(\d{2})$/', $offset, $matches)) {
            return $offset;
        }

        return 'UTC';
    }

    /**
     * Get start of day timestamp (UTC).
     *
     * @param string $date Date string (Y-m-d format, UTC)
     * @return int Start of day timestamp (UTC)
     */
    public static function start_of_day($date)
    {
        return self::parse_utc($date . ' 00:00:00');
    }

    /**
     * Get end of day timestamp (UTC).
     *
     * @param string $date Date string (Y-m-d format, UTC)
     * @return int End of day timestamp (UTC)
     */
    public static function end_of_day($date)
    {
        return self::parse_utc($date . ' 23:59:59');
    }

    /**
     * Format timestamp for display (applies user timezone).
     *
     * @param int $utc_timestamp UTC timestamp
     * @param string $format_type 'date', 'time', or 'datetime'
     * @return string Formatted string in user's timezone
     */
    public static function format_for_display($utc_timestamp, $format_type = 'datetime')
    {
        $settings = AVDP_Settings::get_section('general_settings');

        $formats = array(
            'date' => $settings['date_format'] ?? 'Y-m-d',
            'time' => $settings['time_format'] ?? 'H:i',
            'datetime' => ($settings['date_format'] ?? 'Y-m-d') . ' ' . ($settings['time_format'] ?? 'H:i')
        );

        $format = isset($formats[$format_type]) ? $formats[$format_type] : $formats['datetime'];

        return self::utc_to_local($utc_timestamp, $format);
    }
}
