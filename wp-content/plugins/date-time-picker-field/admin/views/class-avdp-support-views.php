<?php
/**
 * Admin Support Views.
 *
 * @package Availability_Datepicker
 * @since 2.4.0.13
 */

class AVDP_Support_Views
{
    /**
     * Render the support page.
     */
    public static function render()
    {
        $info = self::get_system_info_data();

        // Prepare Raw Data for Copy (Markdown Table Format)
        $raw_text = "### System Info ###\n\n";

        $raw_text .= "| System Environment | |\n| --- | --- |\n";
        foreach ( $info['system_info'] as $key => $value ) {
            $raw_text .= "| $key | $value |\n";
        }
        // Browser / OS placeholders replaced by JS at copy time
        $raw_text .= "| Browser | [Detecting...] |\n";
        $raw_text .= "| Operating System | [Detecting...] |\n";

        $raw_text .= "\n| Theme Info | |\n| --- | --- |\n";
        foreach ( $info['theme_info'] as $key => $value ) {
            $raw_text .= "| $key | $value |\n";
        }

        $raw_text .= "\n| Active Plugins | |\n| --- | --- |\n";
        foreach ( $info['active_plugins'] as $i => $plugin ) {
            $raw_text .= "| " . ( $i + 1 ) . " | $plugin |\n";
        }

        // Config warnings in raw text
        $warnings = self::get_config_warnings( $info['settings'] );
        if ( ! empty( $warnings ) ) {
            $raw_text .= "\n| Configuration Warnings | |\n| --- | --- |\n";
            foreach ( $warnings as $w ) {
                $raw_text .= "| " . strtoupper( $w['type'] ) . " | " . $w['msg'] . " |\n";
            }
        }

        // Availability Rules — formatted
        $raw_text .= "\n| Availability Rules | |\n| --- | --- |\n";
        $raw_text .= self::format_availability_rules_raw( $info['settings']['availability_rules'] ?? [] );

        // CSS Selectors
        $raw_text .= "\n| CSS Selectors | |\n| --- | --- |\n";
        foreach ( $info['settings']['css_selectors'] as $key => $value ) {
            $raw_text .= "| " . ucwords( str_replace( '_', ' ', $key ) ) . " | $value |\n";
        }

        // General Settings
        $raw_text .= "\n| General Settings | |\n| --- | --- |\n";
        foreach ( $info['settings']['general_settings'] as $key => $value ) {
            $raw_text .= "| " . ucwords( str_replace( '_', ' ', $key ) ) . " | $value |\n";
        }

        $diag_has_errors   = ! empty( array_filter( $warnings, fn( $w ) => $w['type'] === 'error' ) );
        $diag_has_warnings = ! empty( array_filter( $warnings, fn( $w ) => $w['type'] === 'warning' ) );
        $diag_badge_class  = $diag_has_errors ? 'avdp-tab-badge--error' : 'avdp-tab-badge--warning';
        ?>
        <div class="wrap avdp-wrap">
            <h1><?php esc_html_e( 'Support', 'availability-datepicker' ); ?></h1>

            <div class="avdp-tabs-wrapper">

                <!-- Tab navigation -->
                <div class="avdp-tabs">
                    <div class="avdp-tab active" data-tab="get-help">
                        <?php esc_html_e( 'Get Help', 'availability-datepicker' ); ?>
                    </div>
                    <div class="avdp-tab" data-tab="diagnostics">
                        <?php esc_html_e( 'Diagnostics', 'availability-datepicker' ); ?>
                        <?php if ( ! empty( $warnings ) ) : ?>
                            <span class="avdp-tab-badge <?php echo esc_attr( $diag_badge_class ); ?>"><?php echo count( $warnings ); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="avdp-tab" data-tab="system-info">
                        <?php esc_html_e( 'System Info', 'availability-datepicker' ); ?>
                    </div>
                    <div class="avdp-tab" data-tab="custom-dev">
                        <?php esc_html_e( 'Custom Feature', 'availability-datepicker' ); ?>
                    </div>
                </div>

                <!-- Tab: Get Help -->
                <div class="avdp-tab-content active" data-tab="get-help">

                    <div class="avdp-card">
                        <h2><?php esc_html_e( 'Help & Support', 'availability-datepicker' ); ?></h2>
                        <p><?php esc_html_e( 'Check our documentation for guides or visit our support forum for assistance.', 'availability-datepicker' ); ?></p>
                        <p class="avdp-support-links">
                            <a href="https://www.inputwp.com/documentation" target="_blank" class="button"><?php esc_html_e( 'View Documentation', 'availability-datepicker' ); ?></a>
                            <a href="https://wordpress.org/support/plugin/date-time-picker-field/" target="_blank"><?php esc_html_e( 'Get Support', 'availability-datepicker' ); ?></a>
                        </p>
                    </div>

                    <?php self::render_report_guide(); ?>

                </div><!-- /.avdp-tab-content[get-help] -->

                <!-- Tab: Diagnostics -->
                <div class="avdp-tab-content" data-tab="diagnostics">
                    <?php self::render_diagnostics_card( $warnings ); ?>
                </div><!-- /.avdp-tab-content[diagnostics] -->

                <!-- Tab: System Info -->
                <div class="avdp-tab-content" data-tab="system-info">

                    <div class="avdp-card">
                        <div class="avdp-support-card-header">
                            <h2><?php esc_html_e( 'System Info', 'availability-datepicker' ); ?></h2>
                            <button type="button" class="button avdp-copy-btn" data-target="avdp-system-info-raw">
                                <?php esc_html_e( 'Copy System Info', 'availability-datepicker' ); ?>
                            </button>
                        </div>

                        <textarea id="avdp-system-info-raw" class="avdp-system-info-raw" readonly><?php echo esc_textarea( $raw_text ); ?></textarea>

                        <table class="avdp-system-info-table">
                            <tbody>

                                <!-- System Environment -->
                                <tr>
                                    <th colspan="2" class="avdp-sysinfo-section">
                                        <?php esc_html_e( 'System Environment', 'availability-datepicker' ); ?>
                                    </th>
                                </tr>
                                <?php foreach ( $info['system_info'] as $key => $value ) : ?>
                                    <tr>
                                        <td class="avdp-sysinfo-label"><?php echo esc_html( $key ); ?></td>
                                        <td class="avdp-sysinfo-value"><?php echo esc_html( $value ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <!-- Browser / OS (populated by JS) -->
                                <tr>
                                    <td class="avdp-sysinfo-label"><?php esc_html_e( 'Browser', 'availability-datepicker' ); ?></td>
                                    <td class="avdp-sysinfo-value"><span id="avdp-browser-info-cell"><?php esc_html_e( 'Detecting...', 'availability-datepicker' ); ?></span></td>
                                </tr>
                                <tr>
                                    <td class="avdp-sysinfo-label"><?php esc_html_e( 'Operating System', 'availability-datepicker' ); ?></td>
                                    <td class="avdp-sysinfo-value"><span id="avdp-os-info-cell"><?php esc_html_e( 'Detecting...', 'availability-datepicker' ); ?></span></td>
                                </tr>

                                <!-- Theme Info -->
                                <tr>
                                    <th colspan="2" class="avdp-sysinfo-section">
                                        <?php esc_html_e( 'Theme Info', 'availability-datepicker' ); ?>
                                    </th>
                                </tr>
                                <?php foreach ( $info['theme_info'] as $key => $value ) : ?>
                                    <tr>
                                        <td class="avdp-sysinfo-label"><?php echo esc_html( $key ); ?></td>
                                        <td class="avdp-sysinfo-value"><?php echo esc_html( $value ); ?></td>
                                    </tr>
                                <?php endforeach; ?>

                                <!-- Active Plugins -->
                                <tr>
                                    <th colspan="2" class="avdp-sysinfo-section">
                                        <?php esc_html_e( 'Active Plugins', 'availability-datepicker' ); ?>
                                    </th>
                                </tr>
                                <?php foreach ( $info['active_plugins'] as $plugin ) : ?>
                                    <tr>
                                        <td colspan="2" class="avdp-sysinfo-value"><?php echo esc_html( $plugin ); ?></td>
                                    </tr>
                                <?php endforeach; ?>

                                <!-- Availability Rules — custom renderer -->
                                <tr>
                                    <th colspan="2" class="avdp-sysinfo-section">
                                        <?php esc_html_e( 'Availability Rules', 'availability-datepicker' ); ?>
                                    </th>
                                </tr>
                                <?php self::render_availability_rules_html( $info['settings']['availability_rules'] ?? [] ); ?>

                                <!-- CSS Selectors -->
                                <tr>
                                    <th colspan="2" class="avdp-sysinfo-section">
                                        <?php esc_html_e( 'CSS Selectors', 'availability-datepicker' ); ?>
                                    </th>
                                </tr>
                                <?php
                                $flattened_selectors = self::flatten_array( $info['settings']['css_selectors'], 'css_selectors' );
                                foreach ( $flattened_selectors as $key => $value ) :
                                    $formatted = self::format_setting_label( $key, $value );
                                    ?>
                                    <tr>
                                        <td class="avdp-sysinfo-label"><?php echo esc_html( $formatted['label'] ); ?></td>
                                        <td class="avdp-sysinfo-value"><?php echo esc_html( $formatted['value'] ); ?></td>
                                    </tr>
                                <?php endforeach; ?>

                                <!-- General Settings -->
                                <tr>
                                    <th colspan="2" class="avdp-sysinfo-section">
                                        <?php esc_html_e( 'General Settings', 'availability-datepicker' ); ?>
                                    </th>
                                </tr>
                                <?php
                                $flattened_general = self::flatten_array( $info['settings']['general_settings'], 'general_settings' );
                                foreach ( $flattened_general as $key => $value ) :
                                    $formatted = self::format_setting_label( $key, $value );
                                    ?>
                                    <tr>
                                        <td class="avdp-sysinfo-label"><?php echo esc_html( $formatted['label'] ); ?></td>
                                        <td class="avdp-sysinfo-value"><?php echo esc_html( $formatted['value'] ); ?></td>
                                    </tr>
                                <?php endforeach; ?>

                            </tbody>
                        </table>
                    </div>

                </div><!-- /.avdp-tab-content[system-info] -->

                <!-- Tab: Custom Feature -->
                <div class="avdp-tab-content" data-tab="custom-dev">

                    <div class="avdp-card">
                        <h2><?php esc_html_e( 'Need a Custom Feature?', 'availability-datepicker' ); ?></h2>
                        <p><?php esc_html_e( 'Do you have a specific requirement that the plugin doesn\'t cover out of the box? We offer paid custom development services tailored to your exact needs.', 'availability-datepicker' ); ?></p>
                        <p>
                            <a href="https://www.inputwp.com/custom" target="_blank" class="button button-primary"><?php esc_html_e( 'Request Custom Feature', 'availability-datepicker' ); ?></a>
                        </p>
                    </div>

                </div><!-- /.avdp-tab-content[custom-dev] -->

            </div><!-- /.avdp-tabs-wrapper -->

        </div><!-- /.wrap -->
        <?php
    }

    /**
     * Render HTML table rows for the Availability Rules section.
     *
     * @param array $rules availability_rules settings section.
     */
    private static function render_availability_rules_html( $rules )
    {
        $method         = isset( $rules['method'] ) ? $rules['method'] : 'fixed';
        $weekly_hours   = isset( $rules['weekly_hours'] ) && is_array( $rules['weekly_hours'] ) ? $rules['weekly_hours'] : [];
        $time_settings  = isset( $rules['time_settings'] ) && is_array( $rules['time_settings'] ) ? $rules['time_settings'] : [];
        $date_overrides = isset( $rules['date_overrides'] ) && is_array( $rules['date_overrides'] ) ? $rules['date_overrides'] : [];
        $booking_window = isset( $rules['booking_window'] ) && is_array( $rules['booking_window'] ) ? $rules['booking_window'] : [];

        $days_order = [ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ];
        ?>

        <!-- Method -->
        <tr>
            <td class="avdp-sysinfo-label"><?php esc_html_e( 'Method', 'availability-datepicker' ); ?></td>
            <td class="avdp-sysinfo-value"><?php echo esc_html( self::ar_method_label( $method ) ); ?></td>
        </tr>

        <!-- Weekly Hours -->
        <tr>
            <td colspan="2" class="avdp-sysinfo-subsection"><?php esc_html_e( 'Weekly Hours', 'availability-datepicker' ); ?></td>
        </tr>
        <?php foreach ( $days_order as $day ) :
            $day_data = isset( $weekly_hours[ $day ] ) && is_array( $weekly_hours[ $day ] )
                ? $weekly_hours[ $day ]
                : [ 'enabled' => false, 'slots' => [] ];
            ?>
            <tr>
                <td class="avdp-sysinfo-label"><?php echo esc_html( ucfirst( $day ) ); ?></td>
                <td class="avdp-sysinfo-value"><?php echo esc_html( self::ar_day_schedule( $day_data ) ); ?></td>
            </tr>
        <?php endforeach; ?>

        <!-- Time Settings -->
        <tr>
            <td colspan="2" class="avdp-sysinfo-subsection"><?php esc_html_e( 'Time Settings', 'availability-datepicker' ); ?></td>
        </tr>
        <?php if ( $method !== 'daily' ) : ?>
            <tr>
                <td class="avdp-sysinfo-label"><?php esc_html_e( 'Slot Interval', 'availability-datepicker' ); ?></td>
                <td class="avdp-sysinfo-value"><?php echo esc_html( self::ar_minutes( $time_settings['slot_interval'] ?? 30, '—' ) ); ?></td>
            </tr>
            <tr>
                <td class="avdp-sysinfo-label"><?php esc_html_e( 'Minimum Notice', 'availability-datepicker' ); ?></td>
                <td class="avdp-sysinfo-value"><?php echo esc_html( self::ar_minutes( $time_settings['minimum_notice'] ?? 0 ) ); ?></td>
            </tr>
            <tr>
                <td class="avdp-sysinfo-label"><?php esc_html_e( 'Buffer Before Slot', 'availability-datepicker' ); ?></td>
                <td class="avdp-sysinfo-value"><?php echo esc_html( self::ar_minutes( $time_settings['buffer_before'] ?? 0 ) ); ?></td>
            </tr>
            <tr>
                <td class="avdp-sysinfo-label"><?php esc_html_e( 'Buffer After Slot', 'availability-datepicker' ); ?></td>
                <td class="avdp-sysinfo-value"><?php echo esc_html( self::ar_minutes( $time_settings['buffer_after'] ?? 0 ) ); ?></td>
            </tr>
        <?php endif; ?>
        <?php if ( $method === 'daily' ) : ?>
            <tr>
                <td class="avdp-sysinfo-label"><?php esc_html_e( 'Min Bookable Days', 'availability-datepicker' ); ?></td>
                <td class="avdp-sysinfo-value"><?php echo esc_html( self::ar_days( $time_settings['min_days'] ?? 1 ) ); ?></td>
            </tr>
            <tr>
                <td class="avdp-sysinfo-label"><?php esc_html_e( 'Max Bookable Days', 'availability-datepicker' ); ?></td>
                <td class="avdp-sysinfo-value"><?php echo esc_html( self::ar_days( $time_settings['max_days'] ?? 14 ) ); ?></td>
            </tr>
        <?php endif; ?>
        <?php if ( $method === 'flexible' ) : ?>
            <tr>
                <td class="avdp-sysinfo-label"><?php esc_html_e( 'Min Duration', 'availability-datepicker' ); ?></td>
                <td class="avdp-sysinfo-value"><?php echo esc_html( self::ar_hours( $time_settings['min_duration'] ?? 1 ) ); ?></td>
            </tr>
            <tr>
                <td class="avdp-sysinfo-label"><?php esc_html_e( 'Max Duration', 'availability-datepicker' ); ?></td>
                <td class="avdp-sysinfo-value"><?php echo esc_html( self::ar_hours( $time_settings['max_duration'] ?? 24 ) ); ?></td>
            </tr>
        <?php endif; ?>

        <!-- Booking Window -->
        <tr>
            <td colspan="2" class="avdp-sysinfo-subsection"><?php esc_html_e( 'Booking Window', 'availability-datepicker' ); ?></td>
        </tr>
        <tr>
            <td class="avdp-sysinfo-label"><?php esc_html_e( 'Opens', 'availability-datepicker' ); ?></td>
            <td class="avdp-sysinfo-value"><?php echo esc_html( self::ar_booking_end( $booking_window['from_type'] ?? 'dynamic', $booking_window['from_value'] ?? '0' ) ); ?></td>
        </tr>
        <tr>
            <td class="avdp-sysinfo-label"><?php esc_html_e( 'Closes', 'availability-datepicker' ); ?></td>
            <td class="avdp-sysinfo-value"><?php echo esc_html( self::ar_booking_end( $booking_window['to_type'] ?? 'dynamic', $booking_window['to_value'] ?? '30' ) ); ?></td>
        </tr>

        <!-- Date Overrides -->
        <tr>
            <td colspan="2" class="avdp-sysinfo-subsection"><?php esc_html_e( 'Date Overrides', 'availability-datepicker' ); ?></td>
        </tr>
        <tr>
            <td class="avdp-sysinfo-label"><?php esc_html_e( 'Blocked Dates', 'availability-datepicker' ); ?></td>
            <td class="avdp-sysinfo-value"><?php echo esc_html( self::ar_date_overrides( $date_overrides['blocked_dates'] ?? [], 'blocked' ) ); ?></td>
        </tr>
        <tr>
            <td class="avdp-sysinfo-label"><?php esc_html_e( 'Allowed Dates', 'availability-datepicker' ); ?></td>
            <td class="avdp-sysinfo-value"><?php echo esc_html( self::ar_date_overrides( $date_overrides['allowed_dates'] ?? [], 'allowed' ) ); ?></td>
        </tr>
        <?php
    }

    /**
     * Return a formatted Availability Rules string for the copy-paste raw text.
     *
     * @param array $rules availability_rules settings section.
     * @return string Markdown table rows.
     */
    private static function format_availability_rules_raw( $rules )
    {
        $method         = isset( $rules['method'] ) ? $rules['method'] : 'fixed';
        $weekly_hours   = isset( $rules['weekly_hours'] ) && is_array( $rules['weekly_hours'] ) ? $rules['weekly_hours'] : [];
        $time_settings  = isset( $rules['time_settings'] ) && is_array( $rules['time_settings'] ) ? $rules['time_settings'] : [];
        $date_overrides = isset( $rules['date_overrides'] ) && is_array( $rules['date_overrides'] ) ? $rules['date_overrides'] : [];
        $booking_window = isset( $rules['booking_window'] ) && is_array( $rules['booking_window'] ) ? $rules['booking_window'] : [];

        $days_order = [ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ];
        $t          = '';

        $t .= "| Method | " . self::ar_method_label( $method ) . " |\n";

        // Weekly Hours
        foreach ( $days_order as $day ) {
            $day_data = isset( $weekly_hours[ $day ] ) && is_array( $weekly_hours[ $day ] )
                ? $weekly_hours[ $day ]
                : [ 'enabled' => false, 'slots' => [] ];
            $t .= "| " . ucfirst( $day ) . " | " . self::ar_day_schedule( $day_data ) . " |\n";
        }

        // Time Settings
        if ( $method !== 'daily' ) {
            $t .= "| Slot Interval | " . self::ar_minutes( $time_settings['slot_interval'] ?? 30, '—' ) . " |\n";
            $t .= "| Minimum Notice | " . self::ar_minutes( $time_settings['minimum_notice'] ?? 0 ) . " |\n";
            $t .= "| Buffer Before Slot | " . self::ar_minutes( $time_settings['buffer_before'] ?? 0 ) . " |\n";
            $t .= "| Buffer After Slot | " . self::ar_minutes( $time_settings['buffer_after'] ?? 0 ) . " |\n";
        }
        if ( $method === 'daily' ) {
            $t .= "| Min Bookable Days | " . self::ar_days( $time_settings['min_days'] ?? 1 ) . " |\n";
            $t .= "| Max Bookable Days | " . self::ar_days( $time_settings['max_days'] ?? 14 ) . " |\n";
        }
        if ( $method === 'flexible' ) {
            $t .= "| Min Duration | " . self::ar_hours( $time_settings['min_duration'] ?? 1 ) . " |\n";
            $t .= "| Max Duration | " . self::ar_hours( $time_settings['max_duration'] ?? 24 ) . " |\n";
        }

        // Booking Window
        $t .= "| Booking Window Opens | " . self::ar_booking_end( $booking_window['from_type'] ?? 'dynamic', $booking_window['from_value'] ?? '0' ) . " |\n";
        $t .= "| Booking Window Closes | " . self::ar_booking_end( $booking_window['to_type'] ?? 'dynamic', $booking_window['to_value'] ?? '30' ) . " |\n";

        // Date Overrides
        $t .= "| Blocked Dates | " . self::ar_date_overrides( $date_overrides['blocked_dates'] ?? [], 'blocked' ) . " |\n";
        $t .= "| Allowed Dates | " . self::ar_date_overrides( $date_overrides['allowed_dates'] ?? [], 'allowed' ) . " |\n";

        return $t;
    }

    // -------------------------------------------------------------------------
    // Availability Rules formatting helpers
    // -------------------------------------------------------------------------

    /** Human-readable method name. */
    private static function ar_method_label( $method )
    {
        $map = [
            'fixed'    => 'Fixed Slots',
            'daily'    => 'Day Based',
            'flexible' => 'Flexible Duration',
        ];
        return isset( $map[ $method ] ) ? $map[ $method ] : ucfirst( (string) $method );
    }

    /** One-line schedule string for a single day. */
    private static function ar_day_schedule( $day_data )
    {
        $enabled = ! empty( $day_data['enabled'] );
        if ( ! $enabled ) {
            return 'Closed';
        }
        $slots = isset( $day_data['slots'] ) && is_array( $day_data['slots'] ) ? $day_data['slots'] : [];
        if ( empty( $slots ) ) {
            return 'Open (no slots defined)';
        }
        $parts = [];
        foreach ( $slots as $slot ) {
            $start = isset( $slot['start'] ) ? $slot['start'] : '';
            $end   = isset( $slot['end'] ) ? $slot['end'] : '';
            if ( $start && $end ) {
                $parts[] = $start . ' \u2013 ' . $end;
            }
        }
        return $parts ? implode( ', ', $parts ) : 'Open';
    }

    /** Format a minutes value with units. Zero returns $zero_label. */
    private static function ar_minutes( $minutes, $zero_label = 'None' )
    {
        $minutes = intval( $minutes );
        if ( $minutes === 0 ) {
            return $zero_label;
        }
        if ( $minutes % 60 === 0 ) {
            $h = $minutes / 60;
            return $h . ' ' . ( $h === 1 ? 'hour' : 'hours' );
        }
        return $minutes . ' ' . ( $minutes === 1 ? 'minute' : 'minutes' );
    }

    /** Format an hours value with units. Zero returns $zero_label. */
    private static function ar_hours( $hours, $zero_label = 'None' )
    {
        $hours = intval( $hours );
        if ( $hours === 0 ) {
            return $zero_label;
        }
        return $hours . ' ' . ( $hours === 1 ? 'hour' : 'hours' );
    }

    /** Format a days value with units. Zero returns $zero_label. */
    private static function ar_days( $days, $zero_label = 'None' )
    {
        $days = intval( $days );
        if ( $days === 0 ) {
            return $zero_label;
        }
        return $days . ' ' . ( $days === 1 ? 'day' : 'days' );
    }

    /** Human-readable booking window boundary. */
    private static function ar_booking_end( $type, $value )
    {
        if ( $type === 'dynamic' ) {
            $days = intval( $value );
            if ( $days === 0 ) {
                return 'Today';
            }
            return 'Today + ' . $days . ' ' . ( $days === 1 ? 'day' : 'days' );
        }
        return ( $value && $value !== '' ) ? (string) $value : 'Not set';
    }

    /**
     * Compact summary of a date overrides list.
     *
     * @param array  $dates Array of dates (blocked: strings, allowed: arrays).
     * @param string $type  'blocked' or 'allowed'.
     * @return string
     */
    private static function ar_date_overrides( $dates, $type = 'blocked' )
    {
        if ( empty( $dates ) || ! is_array( $dates ) ) {
            return 'None';
        }

        $count   = count( $dates );
        $preview = [];
        $i       = 0;

        foreach ( $dates as $entry ) {
            if ( $i >= 3 ) { break; }
            if ( $type === 'blocked' ) {
                $preview[] = (string) $entry;
            } else {
                $date_str = isset( $entry['date'] ) ? $entry['date'] : '';
                if ( $date_str ) { $preview[] = $date_str; }
            }
            $i++;
        }

        $str = implode( ', ', $preview );
        if ( $count > 3 ) {
            $str .= ' (+' . ( $count - 3 ) . ' more)';
        }

        return $count . ': ' . $str;
    }

    // -------------------------------------------------------------------------
    // Config validator
    // -------------------------------------------------------------------------

    /**
     * Validate plugin settings for logical conflicts.
     *
     * @param array $settings Full settings array from AVDP_Settings::get_all().
     * @return array Array of ['type' => 'error'|'warning', 'msg' => string].
     */
    private static function get_config_warnings( $settings )
    {
        $warnings = [];

        $rules   = isset( $settings['availability_rules'] ) && is_array( $settings['availability_rules'] ) ? $settings['availability_rules'] : [];
        $method  = isset( $rules['method'] ) ? $rules['method'] : 'fixed';
        $wh      = isset( $rules['weekly_hours'] ) && is_array( $rules['weekly_hours'] ) ? $rules['weekly_hours'] : [];
        $ts      = isset( $rules['time_settings'] ) && is_array( $rules['time_settings'] ) ? $rules['time_settings'] : [];
        $bw      = isset( $rules['booking_window'] ) && is_array( $rules['booking_window'] ) ? $rules['booking_window'] : [];

        // Check: all 7 days disabled
        $all_disabled = true;
        foreach ( [ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ] as $day ) {
            if ( ! empty( $wh[ $day ]['enabled'] ) ) {
                $all_disabled = false;
                break;
            }
        }
        if ( $all_disabled ) {
            $warnings[] = [
                'type' => 'error',
                'msg'  => 'All days of the week are disabled in Weekly Hours — no dates will ever be available.',
            ];
        }

        // Check: booking window opens after it closes (both dynamic)
        $from_type  = isset( $bw['from_type'] ) ? $bw['from_type'] : 'dynamic';
        $to_type    = isset( $bw['to_type'] ) ? $bw['to_type'] : 'dynamic';
        $from_value = intval( $bw['from_value'] ?? 0 );
        $to_value   = intval( $bw['to_value'] ?? 30 );

        if ( $from_type === 'dynamic' && $to_type === 'dynamic' && $from_value > $to_value ) {
            $warnings[] = [
                'type' => 'error',
                'msg'  => "Booking window opens in {$from_value} days but closes in {$to_value} days — the booking window is empty.",
            ];
        }

        // Check: minimum_notice converts to days ≥ booking window size
        $notice_min  = intval( $ts['minimum_notice'] ?? 0 );
        if ( $notice_min > 0 && $from_type === 'dynamic' && $to_type === 'dynamic' ) {
            $notice_days  = $notice_min / 1440; // 1440 min = 1 day
            $window_days  = $to_value - $from_value;
            if ( $notice_days >= $window_days ) {
                $notice_display = $notice_min >= 1440
                    ? round( $notice_min / 1440, 1 ) . ' days'
                    : $notice_min . ' minutes';
                $warnings[] = [
                    'type' => 'warning',
                    'msg'  => "Minimum notice ({$notice_display}) meets or exceeds the booking window ({$window_days} days) — all or most dates may appear blocked.",
                ];
            }
        }

        // Check: min_days > max_days (Day Based)
        if ( $method === 'daily' ) {
            $min_days = intval( $ts['min_days'] ?? 1 );
            $max_days = intval( $ts['max_days'] ?? 14 );
            if ( $min_days > $max_days ) {
                $warnings[] = [
                    'type' => 'error',
                    'msg'  => "Min Bookable Days ({$min_days}) is greater than Max Bookable Days ({$max_days}).",
                ];
            }
        }

        // Check: min_duration > max_duration (Flexible)
        if ( $method === 'flexible' ) {
            $min_dur = floatval( $ts['min_duration'] ?? 1 );
            $max_dur = floatval( $ts['max_duration'] ?? 24 );
            if ( $min_dur > $max_dur ) {
                $warnings[] = [
                    'type' => 'error',
                    'msg'  => "Min Duration ({$min_dur}h) is greater than Max Duration ({$max_dur}h).",
                ];
            }
        }

        // Check: calendar config generates without a PHP error
        try {
            $av = new AVDP_Availability();
            $av->get_calendar_config();
        } catch ( \Throwable $e ) {
            $warnings[] = [
                'type' => 'error',
                'msg'  => 'PHP error while generating the calendar config: ' . $e->getMessage(),
            ];
        }

        return $warnings;
    }

    // -------------------------------------------------------------------------
    // Diagnostics card
    // -------------------------------------------------------------------------

    /**
     * Render the Diagnostics card.
     *
     * @param array $warnings Output of get_config_warnings().
     */
    private static function render_diagnostics_card( $warnings )
    {
        $has_errors   = ! empty( array_filter( $warnings, fn( $w ) => $w['type'] === 'error' ) );
        $has_warnings = ! empty( array_filter( $warnings, fn( $w ) => $w['type'] === 'warning' ) );

        if ( $has_errors ) {
            $status_class = 'avdp-diag-status--error';
            $status_label = esc_html__( 'Issues found', 'availability-datepicker' );
        } elseif ( $has_warnings ) {
            $status_class = 'avdp-diag-status--warning';
            $status_label = esc_html__( 'Warnings', 'availability-datepicker' );
        } else {
            $status_class = 'avdp-diag-status--ok';
            $status_label = esc_html__( 'All checks passed', 'availability-datepicker' );
        }
        ?>
        <div class="avdp-card avdp-diagnostics-card">
            <div class="avdp-support-card-header">
                <h2><?php esc_html_e( 'Diagnostics', 'availability-datepicker' ); ?></h2>
                <span class="avdp-diag-status <?php echo esc_attr( $status_class ); ?>"><?php echo $status_label; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
            </div>

            <!-- PHP Config Checks -->
            <h3 class="avdp-diag-section-title"><?php esc_html_e( 'Configuration', 'availability-datepicker' ); ?></h3>
            <ul class="avdp-diag-list">
                <?php if ( empty( $warnings ) ) : ?>
                    <li class="avdp-diag-row avdp-diag-ok">
                        <?php esc_html_e( 'All configuration checks passed.', 'availability-datepicker' ); ?>
                    </li>
                <?php else : ?>
                    <?php foreach ( $warnings as $w ) : ?>
                        <li class="avdp-diag-row avdp-diag-<?php echo esc_attr( $w['type'] ); ?>">
                            <?php echo esc_html( $w['msg'] ); ?>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>

            <!-- JS Diagnostics (populated by initSupportPage() from localStorage) -->
            <h3 class="avdp-diag-section-title"><?php esc_html_e( 'Last Frontend Session', 'availability-datepicker' ); ?></h3>
            <div id="avdp-js-diagnostics">
                <p class="avdp-diag-pending"><?php esc_html_e( 'Checking...', 'availability-datepicker' ); ?></p>
            </div>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // Report Guide
    // -------------------------------------------------------------------------

    /**
     * Render the "How to Report an Issue" guide card.
     */
    private static function render_report_guide()
    {
        ?>
        <div class="avdp-card avdp-report-guide">
            <h2><?php esc_html_e( 'How to Report an Issue', 'availability-datepicker' ); ?></h2>
            <p><?php esc_html_e( 'Select your issue type below to see exactly what to include in your support ticket.', 'availability-datepicker' ); ?></p>

            <details class="avdp-issue-type">
                <summary><?php esc_html_e( 'Datepicker not appearing on the page', 'availability-datepicker' ); ?></summary>
                <ol>
                    <li><?php esc_html_e( 'The exact URL of the page where the datepicker is missing.', 'availability-datepicker' ); ?></li>
                    <li><?php esc_html_e( 'Open browser DevTools (F12 → Console tab) and copy any red error messages. Paste the full text, not a screenshot.', 'availability-datepicker' ); ?></li>
                    <li><?php esc_html_e( 'In DevTools → Network tab, reload the page and check whether avdp-public.js loads without a 404 error.', 'availability-datepicker' ); ?></li>
                    <li><?php esc_html_e( 'The form plugin or page builder you are using (e.g. Contact Form 7, WPForms, Elementor).', 'availability-datepicker' ); ?></li>
                    <li><?php esc_html_e( 'The exact CSS selector configured in Availability Datepicker → Integration (a screenshot is helpful).', 'availability-datepicker' ); ?></li>
                    <li><?php esc_html_e( 'Whether temporarily deactivating all other plugins resolves the issue.', 'availability-datepicker' ); ?></li>
                </ol>
            </details>

            <details class="avdp-issue-type">
                <summary><?php esc_html_e( 'Wrong dates or times shown as available / unavailable', 'availability-datepicker' ); ?></summary>
                <ol>
                    <li><?php esc_html_e( 'The exact dates that behave unexpectedly, and whether they should be available or blocked.', 'availability-datepicker' ); ?></li>
                    <li><?php esc_html_e( 'The day of the week those dates fall on.', 'availability-datepicker' ); ?></li>
                    <li><?php esc_html_e( 'Your browser\'s timezone (check your device clock settings or visit whatismytimezone.com).', 'availability-datepicker' ); ?></li>
                    <li><?php esc_html_e( 'The availability method in use: Fixed / Day Based / Flexible (visible in Availability → Method).', 'availability-datepicker' ); ?></li>
                    <li><?php esc_html_e( 'Your Minimum Notice setting — this often causes upcoming dates to appear blocked unexpectedly.', 'availability-datepicker' ); ?></li>
                    <li><?php esc_html_e( 'Screenshots of the datepicker showing the issue.', 'availability-datepicker' ); ?></li>
                </ol>
            </details>

            <details class="avdp-issue-type">
                <summary><?php esc_html_e( 'JavaScript / console error', 'availability-datepicker' ); ?></summary>
                <ol>
                    <li><?php esc_html_e( 'The full error text from the browser console (F12 → Console). Copy and paste the complete message including file name and line number — not a screenshot.', 'availability-datepicker' ); ?></li>
                    <li><?php esc_html_e( 'The exact URL where the error occurs.', 'availability-datepicker' ); ?></li>
                    <li><?php esc_html_e( 'Your browser name and version (e.g. Chrome 121, Firefox 122, Safari 17).', 'availability-datepicker' ); ?></li>
                    <li><?php esc_html_e( 'Whether the error persists after temporarily deactivating all other plugins.', 'availability-datepicker' ); ?></li>
                    <li><?php esc_html_e( 'Whether the error persists after switching to a default WordPress theme (e.g. Twenty Twenty-Four).', 'availability-datepicker' ); ?></li>
                </ol>
            </details>

            <details class="avdp-issue-type">
                <summary><?php esc_html_e( 'Styling or display issue', 'availability-datepicker' ); ?></summary>
                <ol>
                    <li><?php esc_html_e( 'A screenshot clearly showing the issue.', 'availability-datepicker' ); ?></li>
                    <li><?php esc_html_e( 'Your browser name and version, and whether you are on a mobile or desktop device.', 'availability-datepicker' ); ?></li>
                    <li><?php esc_html_e( 'Whether the issue appears in all browsers or only specific ones.', 'availability-datepicker' ); ?></li>
                    <li><?php esc_html_e( 'Whether the datepicker is hidden behind other elements (z-index conflict) or is completely absent.', 'availability-datepicker' ); ?></li>
                    <li><?php esc_html_e( 'Whether temporarily switching to a default WordPress theme resolves the issue.', 'availability-datepicker' ); ?></li>
                </ol>
            </details>

            <details class="avdp-issue-type">
                <summary><?php esc_html_e( 'Form submission issue (value not sent or wrong format)', 'availability-datepicker' ); ?></summary>
                <ol>
                    <li><?php esc_html_e( 'The form plugin you are using and its version (e.g. Contact Form 7 5.9, WPForms 1.8).', 'availability-datepicker' ); ?></li>
                    <li><?php esc_html_e( 'How you added the CSS class to the form field — a screenshot of the field settings is very helpful.', 'availability-datepicker' ); ?></li>
                    <li><?php esc_html_e( 'What value (if any) is actually being submitted — check the form confirmation email, admin notification, or entry log.', 'availability-datepicker' ); ?></li>
                    <li><?php esc_html_e( 'Whether the datepicker shows the correct date before the form is submitted.', 'availability-datepicker' ); ?></li>
                    <li><?php esc_html_e( 'Whether any form validation script on your page might be clearing or blocking the field value.', 'availability-datepicker' ); ?></li>
                </ol>
            </details>

            <details class="avdp-issue-type">
                <summary><?php esc_html_e( 'Something broke after a plugin or theme update', 'availability-datepicker' ); ?></summary>
                <ol>
                    <li><?php esc_html_e( 'The version you updated from (check your hosting backup log or the plugin changelog on WordPress.org).', 'availability-datepicker' ); ?></li>
                    <li><?php esc_html_e( 'Which plugin or theme was updated — not necessarily this one.', 'availability-datepicker' ); ?></li>
                    <li><?php esc_html_e( 'Whether clearing your site\'s cache (caching plugin + browser cache) resolved the issue.', 'availability-datepicker' ); ?></li>
                    <li><?php esc_html_e( 'Whether temporarily deactivating recently updated plugins restores the expected behaviour.', 'availability-datepicker' ); ?></li>
                    <li><?php esc_html_e( 'Any error messages from the browser console (F12 → Console).', 'availability-datepicker' ); ?></li>
                </ol>
            </details>

            <p class="avdp-report-guide-footer">
                <?php esc_html_e( 'Always include the System Info below in your support ticket — use the Copy System Info button to grab it in one click.', 'availability-datepicker' ); ?>
            </p>
        </div>
        <?php
    }

    // -------------------------------------------------------------------------
    // System Info data
    // -------------------------------------------------------------------------

    /**
     * Get system information data.
     *
     * @return array
     */
    private static function get_system_info_data()
    {
        global $wpdb;

        // Timezone
        $wp_timezone_string = get_option( 'timezone_string' );
        $wp_gmt_offset      = get_option( 'gmt_offset' );
        if ( $wp_timezone_string ) {
            $wp_tz_display = $wp_timezone_string;
        } else {
            $offset        = (float) $wp_gmt_offset;
            $wp_tz_display = 'UTC' . ( $offset >= 0 ? '+' : '' ) . $offset;
        }

        $plugin_settings = AVDP_Settings::get_all();
        $plugin_timezone = isset( $plugin_settings['general_settings']['timezone'] ) ? $plugin_settings['general_settings']['timezone'] : 'UTC';
        $date_format     = isset( $plugin_settings['general_settings']['date_format'] ) ? $plugin_settings['general_settings']['date_format'] : 'Y-m-d';

        // Caching / CDN plugin slugs to flag
        $caching_slugs = [
            'w3-total-cache', 'wp-super-cache', 'wp-rocket', 'litespeed-cache',
            'sg-cachepress', 'breeze', 'comet-cache', 'swift-performance-lite',
            'wp-fastest-cache', 'autoptimize', 'hummingbird-performance',
            'cache-enabler', 'cloudflare',
        ];

        // Active plugins
        $active_plugin_files = get_option( 'active_plugins', array() );
        $plugins_list        = array();
        $detected_caching    = array();

        foreach ( $active_plugin_files as $plugin_file ) {
            $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file, false, false );
            $slug        = explode( '/', $plugin_file )[0];
            $name        = ! empty( $plugin_data['Name'] ) ? $plugin_data['Name'] : $plugin_file;
            $version     = ! empty( $plugin_data['Version'] ) ? $plugin_data['Version'] : '?';
            $plugins_list[] = $name . ' (' . $version . ')';

            if ( in_array( $slug, $caching_slugs, true ) ) {
                $detected_caching[] = $name;
            }
        }

        // Core system info
        $info = array(
            'system_info' => array(
                'Plugin Version'               => AVDP_VERSION,
                'WordPress Version'            => get_bloginfo( 'version' ),
                'Multisite'                    => is_multisite() ? 'Yes' : 'No',
                'PHP Version'                  => phpversion(),
                'MySQL Version'                => $wpdb->db_version(),
                'Server Software'              => isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '',
                'WP_DEBUG'                     => ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'Enabled' : 'Disabled',
                'WP_DEBUG_LOG'                 => ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) ? 'Enabled' : 'Disabled',
                'Language'                     => get_locale(),
                'WP Timezone'                  => $wp_tz_display,
                'Plugin Timezone'              => $plugin_timezone,
                "Today's Date (Plugin Format)" => date( $date_format ), // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
                'Caching / CDN Plugins'        => ! empty( $detected_caching ) ? implode( ', ', $detected_caching ) : 'None detected',
            ),
        );

        // Theme info
        $theme               = wp_get_theme();
        $info['theme_info']  = array(
            'Name'         => $theme->get( 'Name' ),
            'Version'      => $theme->get( 'Version' ),
            'Author'       => $theme->get( 'Author' ),
            'Parent Theme' => $theme->parent() ? $theme->parent()->get( 'Name' ) : 'None',
        );

        // Active plugins
        $info['active_plugins'] = $plugins_list;

        // Plugin settings
        $info['settings'] = $plugin_settings;

        return $info;
    }

    // -------------------------------------------------------------------------
    // Generic settings helpers (used for css_selectors and general_settings)
    // -------------------------------------------------------------------------

    /**
     * Flatten a multi-dimensional array into a single level array with dot notation keys.
     *
     * @param array  $array  The array to flatten.
     * @param string $prefix The prefix for the keys.
     * @return array
     */
    private static function flatten_array( $array, $prefix = '' )
    {
        $result = array();
        foreach ( $array as $key => $value ) {
            $new_key = $prefix . ( empty( $prefix ) ? '' : '.' ) . $key;
            if ( is_array( $value ) ) {
                $result = array_merge( $result, self::flatten_array( $value, $new_key ) );
            } else {
                $result[ $new_key ] = $value;
            }
        }
        return $result;
    }

    /**
     * Format setting label and value for display.
     *
     * @param string $key   Flattened key.
     * @param mixed  $value Raw value.
     * @return array ['label' => string, 'value' => string]
     */
    private static function format_setting_label( $key, $value )
    {
        // Strip top-level section prefixes for cleaner labels
        $prefixes_to_strip = [
            'availability_rules.weekly_hours.',
            'availability_rules.',
            'general_settings.',
            'css_selectors.',
        ];

        $clean_key = $key;
        foreach ( $prefixes_to_strip as $prefix ) {
            if ( strpos( $clean_key, $prefix ) === 0 ) {
                $clean_key = substr( $clean_key, strlen( $prefix ) );
                break;
            }
        }

        // "Monday.enabled" → Label: "Monday", Value: "Enabled"/"Disabled"
        if ( preg_match( '/^([a-z]+)\.enabled$/', $clean_key, $matches ) ) {
            $day    = ucfirst( $matches[1] );
            $status = ( $value === true || $value === 'true' || $value === 1 )
                ? __( 'Enabled', 'availability-datepicker' )
                : __( 'Disabled', 'availability-datepicker' );
            return [ 'label' => $day, 'value' => $status ];
        }

        // "Monday.slots.0.start"
        if ( preg_match( '/^([a-z]+)\.slots\.([0-9]+)\.(start|end)$/', $clean_key, $matches ) ) {
            $day      = ucfirst( $matches[1] );
            $slot_num = intval( $matches[2] ) + 1;
            $type     = ucfirst( $matches[3] );
            return [ 'label' => "$day (Slot $slot_num $type)", 'value' => $value ];
        }

        // Default: replace dots and underscores with spaces, capitalize words
        $label = ucwords( str_replace( [ '.', '_' ], ' ', $clean_key ) );

        if ( is_bool( $value ) ) {
            $display_value = $value ? 'true' : 'false';
        } elseif ( is_null( $value ) ) {
            $display_value = 'null';
        } elseif ( is_array( $value ) ) {
            $display_value = 'Array';
        } else {
            $display_value = (string) $value;
        }

        return [ 'label' => $label, 'value' => $display_value ];
    }
}
