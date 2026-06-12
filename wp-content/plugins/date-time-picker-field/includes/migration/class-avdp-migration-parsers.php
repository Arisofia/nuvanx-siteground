<?php
/**
 * Migration Parsers Trait.
 *
 * Handles parsing and conversion utility methods for legacy data migration.
 *
 * @package Availability_Datepicker
 * @since 3.0
 */

trait AVDP_Migration_Parsers
{

    /**
     * Parse disabled dates string to Y-m-d array.
     *
     * @param string $dates_string Comma-separated dates.
     * @param string $v2_date_format Legacy date format.
     * @return array Y-m-d formatted dates.
     */
    private function parse_disabled_dates($dates_string, $v2_date_format)
    {
        if (empty($dates_string)) {
            return array();
        }

        $dates = $this->parse_times_string($dates_string);
        if (empty($dates)) {
            return array();
        }

        $php_format = $this->convert_date_format($v2_date_format);
        $converted = array();

        foreach ($dates as $date_str) {
            $date_str = trim($date_str);
            if (empty($date_str)) {
                continue;
            }
            $ymd = $this->convert_date_to_ymd($date_str, $php_format);
            if ($ymd) {
                $converted[] = $ymd;
            }
        }

        $converted = array_unique($converted);
        sort($converted);
        return array_values($converted);
    }

    /**
     * Convert date string to Y-m-d.
     *
     * @param string $date_str Date string.
     * @param string $php_format PHP date format.
     * @return string|false Y-m-d or false.
     */
    private function convert_date_to_ymd($date_str, $php_format)
    {
        $obj = DateTime::createFromFormat($php_format, $date_str);
        if ($obj) {
            return $obj->format('Y-m-d');
        }
        $ts = strtotime($date_str);
        if ($ts !== false) {
            return date('Y-m-d', $ts);
        }
        foreach (array('d-m-Y', 'd/m/Y', 'd.m.Y', 'Y-m-d', 'm/d/Y', 'm-d-Y') as $fmt) {
            $obj = DateTime::createFromFormat($fmt, $date_str);
            if ($obj) {
                return $obj->format('Y-m-d');
            }
        }
        return false;
    }

    /**
     * Parse date for booking window (relative, absolute, or number).
     *
     * @param string $date_string Date string.
     * @param bool   $is_min_date Whether this is min date.
     * @return array With 'type' and parsed data.
     */
    private function parse_date_for_booking_window($date_string, $is_min_date = false)
    {
        $date_string = trim($date_string);
        if (empty($date_string)) {
            return array('type' => 'invalid');
        }

        if (!$is_min_date && preg_match('/^\d+$/', $date_string)) {
            return array('type' => 'number', 'days' => (int) $date_string);
        }

        if (preg_match('/^\+(\d+)\s*(day|days|week|weeks|month|months|year|years?)\s*$/i', $date_string, $m)) {
            $num = (int) $m[1];
            $unit = strtolower($m[2]);
            $days = $num;
            if (strpos($unit, 'week') === 0) {
                $days = $num * 7;
            } elseif (strpos($unit, 'month') === 0) {
                $days = $num * 30;
            } elseif (strpos($unit, 'year') === 0) {
                $days = $num * 365;
            }
            return array('type' => 'relative', 'days' => max(0, $days));
        }

        $ts = strtotime($date_string);
        if ($ts !== false) {
            $today = new DateTime('now', new DateTimeZone('UTC'));
            $parsed = new DateTime('@' . $ts);
            $diff = $today->diff($parsed);
            $days_diff = (int) $diff->days;
            if ($days_diff <= 365 && preg_match('/^\+/', $date_string)) {
                return array('type' => 'relative', 'days' => max(0, $days_diff));
            }
            return array('type' => 'absolute', 'date' => $parsed->format('Y-m-d'));
        }

        foreach (array('Y-m-d', 'd-m-Y', 'm-d-Y', 'd/m/Y', 'm/d/Y', 'd.m.Y', 'm.d.Y') as $fmt) {
            $obj = DateTime::createFromFormat($fmt, $date_string);
            if ($obj) {
                return array('type' => 'absolute', 'date' => $obj->format('Y-m-d'));
            }
        }

        return array('type' => 'invalid');
    }

    /**
     * Convert legacy date format to PHP format.
     *
     * @param string $v2_format Legacy format.
     * @return string PHP format.
     */
    private function convert_date_format($v2_format)
    {
        $map = array(
            'YYYY-MM-DD' => 'Y-m-d', 'DD-MM-YYYY' => 'd-m-Y', 'MM-DD-YYYY' => 'm-d-Y',
            'MMM-DD-YYYY' => 'M-d-Y', 'DD-MMM-YYYY' => 'd-M-Y', 'YYYY/MM/DD' => 'Y/m/d',
            'DD/MM/YYYY' => 'd/m/Y', 'MM/DD/YYYY' => 'm/d/Y', 'MMM/DD/YYYY' => 'M/d/Y',
            'DD/MMM/YYYY' => 'd/M/Y', 'DD.MM.YYYY' => 'd.m.Y', 'MM.DD.YYYY' => 'm.d.Y',
            'YYYY.MM.DD' => 'Y.m.d', 'MMM.DD.YYYY' => 'M.d.Y', 'DD.MMM.YYYY' => 'd.M.Y',
            'YYYYMMDD' => 'Ymd',
        );
        return isset($map[$v2_format]) ? $map[$v2_format] : 'Y-m-d';
    }

    /**
     * Convert legacy time format to PHP format.
     *
     * @param string $v2_format Legacy format.
     * @return string PHP format.
     */
    private function convert_time_format($v2_format)
    {
        $map = array('HH:mm' => 'H:i', 'hh:mm A' => 'g:i A');
        return isset($map[$v2_format]) ? $map[$v2_format] : 'H:i';
    }

    /**
     * Convert a v2.0 locale string to a v3.0-compatible language code.
     *
     * Most locale codes are identical between v2.0 (xdSoft) and v3.0, so we pass
     * them through directly. Only a small set of aliases need normalising.
     *
     * @param string $v2_locale Legacy locale code.
     * @return string Language code.
     */
    private function convert_locale($v2_locale)
    {
        if (empty($v2_locale) || $v2_locale === 'auto') {
            return 'en';
        }

        $aliases = array(
            'en-GB' => 'en',
            'zh-CN' => 'zh',
        );

        if (isset($aliases[$v2_locale])) {
            return $aliases[$v2_locale];
        }

        // Pass all other codes through — v2.0 and v3.0 share the same xdSoft locale set.
        return $v2_locale;
    }

    /**
     * Parse comma-separated string to array.
     *
     * @param string $str Comma-separated string.
     * @return array Trimmed non-empty values.
     */
    private function parse_times_string($str)
    {
        if (empty($str)) {
            return array();
        }
        $arr = array_map('trim', explode(',', $str));
        return array_values(array_filter($arr));
    }

    /**
     * Convert a comma-separated time string to a single {start, end} slot
     * by taking the minimum and maximum times in the list.
     *
     * @param string $times_string Comma-separated time values.
     * @return array|null Array with 'start' and 'end' in H:i, or null if fewer than 2 valid times.
     */
    private function times_string_to_range_slot($times_string)
    {
        $times = $this->parse_times_string($times_string);
        if (empty($times)) {
            return null;
        }

        $normalized = array();
        foreach ($times as $t) {
            $n = $this->normalize_time_for_slots($t);
            if ($n) {
                $normalized[] = $n;
            }
        }

        if (count($normalized) < 2) {
            return null;
        }

        sort($normalized); // H:i sorts chronologically.
        $start = reset($normalized);
        $end   = end($normalized);

        if ($start === $end) {
            return null;
        }

        return array('start' => $start, 'end' => $end);
    }

    /**
     * Normalize time string to H:i format.
     *
     * @param string $time Time string.
     * @return string|false H:i or false.
     */
    private function normalize_time_for_slots($time)
    {
        if (empty($time)) {
            return false;
        }
        $time = trim($time);

        $formats = array('H:i', 'H:i:s', 'g:i A', 'g:i a', 'g:i:s A', 'g:i:s a', 'G:i A');
        foreach ($formats as $fmt) {
            $obj = DateTime::createFromFormat($fmt, $time);
            if ($obj) {
                return $obj->format('H:i');
            }
        }
        return false;
    }

    /**
     * Extract first selector from comma-separated string.
     *
     * @param string $str Selector string.
     * @return string|null First selector or null.
     */
    private function extract_first_selector($str)
    {
        if (empty($str)) {
            return null;
        }
        $parts = explode(',', $str);
        $first = trim($parts[0]);
        return $first !== '' ? $first : null;
    }

    /**
     * Extract selectors from integration data.
     *
     * @param mixed $integration_data Serialized integration data.
     * @return array Selector strings.
     */
    private function extract_integration_selectors($integration_data)
    {
        $selectors = array();
        if ($integration_data === false || empty($integration_data)) {
            return $selectors;
        }

        $integrations = maybe_unserialize($integration_data);
        if (!is_array($integrations)) {
            return $selectors;
        }

        foreach ($integrations as $integration) {
            if (is_array($integration) && isset($integration['selector']) && !empty($integration['selector'])) {
                $sel = $this->extract_first_selector($integration['selector']);
                if ($sel) {
                    $selectors[] = $sel;
                }
            }
        }
        return $selectors;
    }

}
