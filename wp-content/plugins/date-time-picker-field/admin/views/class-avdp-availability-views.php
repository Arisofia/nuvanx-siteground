<?php
/**
 * Admin Availability Views.
 *
 * @package Availability_Datepicker
 * @since 2.4.0.13
 */

class AVDP_Availability_Views
{
    /**
     * Render the availability settings page.
     *
     * @param array $rules Current rules.
     */
    public static function render($rules)
    {
        ?>
        <div class="wrap avdp-wrap">
            <h1><?php esc_html_e('Availability', 'availability-datepicker'); ?></h1>

            <?php settings_errors('avdp_messages'); ?>

            <form method="post" action="" class="avdp-settings-form" data-page="availability">
                <?php wp_nonce_field('avdp_save_availability', 'avdp_availability_nonce'); ?>

                <div class="avdp-card avdp-preview-card" id="avdp-availability-preview">
                    <!-- Populated by JavaScript -->
                </div>

                <div class="avdp-card avdp-preset-panel" id="avdp-preset-panel">
                    <button type="button" class="avdp-preset-panel-toggle" aria-expanded="false" aria-controls="avdp-preset-body">
                        <span class="avdp-preset-panel-title">
                            <?php esc_html_e('Quick Setup — Presets', 'availability-datepicker'); ?>
                        </span>
                        <span class="avdp-preset-panel-sub"><?php esc_html_e('Pre-fill all settings for a common booking scenario', 'availability-datepicker'); ?></span>
                        <span class="avdp-preset-panel-chevron" aria-hidden="true"></span>
                    </button>
                    <div class="avdp-preset-panel-body" id="avdp-preset-body" hidden>
                        <p class="description avdp-preset-panel-desc"><?php esc_html_e('Click a preset to load its settings. All availability fields will be pre-filled — you can adjust them afterwards. Nothing is saved until you click Save Changes.', 'availability-datepicker'); ?></p>
                        <div class="avdp-preset-grid" id="avdp-preset-grid">
                            <!-- Cards injected by JavaScript -->
                        </div>
                    </div>
                </div>

                <div class="avdp-card">
                    <h2><?php esc_html_e('Configuration', 'availability-datepicker'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="method"><?php esc_html_e('Booking Type', 'availability-datepicker'); ?></label></th>
                            <td>
                                <select id="method" name="method" class="regular-text" style="width: 320px!important;">
                                    <option value="fixed" <?php selected($rules['method'] ?? 'fixed', 'fixed'); ?>><?php esc_html_e('Fixed Time Slots', 'availability-datepicker'); ?></option>
                                    <option value="daily" <?php selected($rules['method'] ?? 'fixed', 'daily'); ?>><?php esc_html_e('Day Based', 'availability-datepicker'); ?></option>
                                    <option value="flexible" <?php selected($rules['method'] ?? 'fixed', 'flexible'); ?>><?php esc_html_e('Flexible Range', 'availability-datepicker'); ?></option>
                                </select>
                                <p class="description">
                                    <strong><?php esc_html_e('Fixed Time Slots:', 'availability-datepicker'); ?></strong> <?php esc_html_e('Appointments, consultations, classes (e.g., 9:00 AM - 10:00 AM)', 'availability-datepicker'); ?><br>
                                    <strong><?php esc_html_e('Day Based:', 'availability-datepicker'); ?></strong> <?php esc_html_e('Hotels, vacation rentals (e.g., Check-in Dec 15 → Check-out Dec 18)', 'availability-datepicker'); ?><br>
                                    <strong><?php esc_html_e('Flexible Range:', 'availability-datepicker'); ?></strong> <?php esc_html_e('Equipment/car rentals (e.g., Dec 15 at 10:00 AM → Dec 18 at 2:00 PM)', 'availability-datepicker'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="avdp-card">
                    <h2><?php esc_html_e('Availability Window', 'availability-datepicker'); ?></h2>
                    <?php
                        $window = $rules['booking_window'] ?? array();
                        $from_type = $window['from_type'] ?? 'dynamic';
                        $from_value = $window['from_value'] ?? '0';
                        $to_type = $window['to_type'] ?? 'dynamic';
                        $to_value = $window['to_value'] ?? '30';
                    ?>
                    <table class="form-table avdp-date-range-settings">
                        <tr>
                            <th scope="row">
                                <label><?php esc_html_e('From', 'availability-datepicker'); ?></label>
                            </th>
                            <td>
                                <div class="avdp-input-group-300 avdp-range-input-group">
                                    <select name="booking_window[from_type]" class="range-type-select regular-text">
                                        <option value="dynamic" <?php selected($from_type, 'dynamic'); ?>><?php esc_html_e('Dynamic', 'availability-datepicker'); ?></option>
                                        <option value="predefined" <?php selected($from_type, 'predefined'); ?>><?php esc_html_e('Predefined Date', 'availability-datepicker'); ?></option>
                                    </select>
                                    <input type="number" name="booking_window[from_value_dynamic]" value="<?php echo esc_attr($from_type === 'dynamic' ? $from_value : '0'); ?>" class="small-text range-dynamic" placeholder="0">
                                    <input type="date" name="booking_window[from_value_date]" value="<?php echo esc_attr($from_type === 'predefined' ? $from_value : ''); ?>" class="range-date regular-text" style="display:none;">
                                </div>
                                <p class="description"><?php esc_html_e('Set how many days from today availability starts (e.g. 0 for today).', 'availability-datepicker'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label><?php esc_html_e('Until', 'availability-datepicker'); ?></label>
                            </th>
                            <td>
                                <div class="avdp-input-group-300 avdp-range-input-group">
                                    <select name="booking_window[to_type]" class="range-type-select regular-text">
                                        <option value="dynamic" <?php selected($to_type, 'dynamic'); ?>><?php esc_html_e('Dynamic', 'availability-datepicker'); ?></option>
                                        <option value="predefined" <?php selected($to_type, 'predefined'); ?>><?php esc_html_e('Predefined Date', 'availability-datepicker'); ?></option>
                                    </select>
                                    <input type="number" name="booking_window[to_value_dynamic]" value="<?php echo esc_attr($to_type === 'dynamic' ? $to_value : '30'); ?>" class="small-text range-dynamic" placeholder="30">
                                    <input type="date" name="booking_window[to_value_date]" value="<?php echo esc_attr($to_type === 'predefined' ? $to_value : ''); ?>" class="range-date regular-text" style="display:none;">
                                </div>
                                <p class="description"><?php esc_html_e('Set how many days into the future availability ends (e.g. 30 for 30 days).', 'availability-datepicker'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="avdp-card">
                    <h2><?php esc_html_e('Business Hours', 'availability-datepicker'); ?></h2>
                    <table class="form-table" id="avdp_weekly_hours_table">
                        <tbody>
                            <?php
                            $days = array(
                                'monday' => __('Monday', 'availability-datepicker'),
                                'tuesday' => __('Tuesday', 'availability-datepicker'),
                                'wednesday' => __('Wednesday', 'availability-datepicker'),
                                'thursday' => __('Thursday', 'availability-datepicker'),
                                'friday' => __('Friday', 'availability-datepicker'),
                                'saturday' => __('Saturday', 'availability-datepicker'),
                                'sunday' => __('Sunday', 'availability-datepicker')
                            );
                            $weekly = isset($rules['weekly_hours']) ? $rules['weekly_hours'] : array();

                            foreach ($days as $key => $label):
                                // Default Mon-Fri to enabled, Sat-Sun to disabled
                                $default_enabled = in_array($key, array('monday', 'tuesday', 'wednesday', 'thursday', 'friday'));
                                $enabled = isset($weekly[$key]['enabled']) ? (bool)$weekly[$key]['enabled'] : $default_enabled;
                                
                                // Get slots - if empty or not set, provide default slot so time inputs appear
                                $slots = isset($weekly[$key]['slots']) && !empty($weekly[$key]['slots']) 
                                    ? $weekly[$key]['slots'] 
                                    : array(array('start' => '09:00', 'end' => '17:00'));
                                ?>
                                <tr>
                                    <th scope="row">
                                        <label>
                                            <input type="checkbox" name="weekly_hours[<?php echo $key; ?>][enabled]" value="1" class="avdp-day-toggle" <?php checked($enabled); ?>>
                                            <?php echo $label; ?>
                                        </label>
                                    </th>
                                    <td>
                                        <div class="avdp-slots-container">
                                            <?php foreach ($slots as $index => $slot): ?>
                                                <div class="avdp-slot-row">
                                                    <input type="time"
                                                        name="weekly_hours[<?php echo $key; ?>][slots][<?php echo $index; ?>][start]"
                                                        value="<?php echo esc_attr($slot['start']); ?>"
                                                        <?php echo $enabled ? '' : 'disabled'; ?>>
                                                    <span><?php esc_html_e('to', 'availability-datepicker'); ?></span>
                                                    <input type="time"
                                                        name="weekly_hours[<?php echo $key; ?>][slots][<?php echo $index; ?>][end]"
                                                        value="<?php echo esc_attr($slot['end']); ?>"
                                                        <?php echo $enabled ? '' : 'disabled'; ?>>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p class="description avdp-p-md"><?php esc_html_e('Configure the start and end times for each day of the week. Uncheck the box to mark a day as closed.', 'availability-datepicker'); ?></p>
                </div>

                <div class="avdp-card">
                    <h2><?php esc_html_e('Scheduling', 'availability-datepicker'); ?></h2>
                    <table class="form-table">
                        <tr id="row-slot-interval">
                            <th scope="row"><label for="slot_interval"><?php esc_html_e('Slot interval (min)', 'availability-datepicker'); ?></label></th>
                            <td>
                                <input type="number" name="time_settings[slot_interval]" id="slot_interval"
                                    value="<?php echo esc_attr($rules['time_settings']['slot_interval'] ?? 30); ?>" min="1" class="regular-text" style="width: 320px!important;">
                                <p class="description"><?php esc_html_e('The duration of each time slot in minutes (e.g. 60).', 'availability-datepicker'); ?></p>
                            </td>
                        </tr>
                        <tr id="row-minimum-notice">
                            <th scope="row"><label for="minimum_notice"><?php esc_html_e('Notice (min)', 'availability-datepicker'); ?></label></th>
                            <td>
                                <input type="number" name="time_settings[minimum_notice]" id="minimum_notice"
                                    value="<?php echo esc_attr($rules['time_settings']['minimum_notice'] ?? 0); ?>" min="0" class="regular-text" style="width: 320px!important;">
                                <p class="description"><?php esc_html_e('Minimum minutes required before a booking can be made (e.g. 24h = 1440 min).', 'availability-datepicker'); ?></p>
                            </td>
                        </tr>
                        <tr id="row-buffer-before">
                            <th scope="row"><label for="buffer_before"><?php esc_html_e('Buffer before (min)', 'availability-datepicker'); ?></label></th>
                            <td>
                                <input type="number" name="time_settings[buffer_before]" id="buffer_before"
                                    value="<?php echo esc_attr($rules['time_settings']['buffer_before'] ?? 0); ?>" min="0" class="regular-text" style="width: 320px!important;">
                                <p class="description"><?php esc_html_e('Rest time to reserve before each appointment.', 'availability-datepicker'); ?></p>
                            </td>
                        </tr>
                        <tr id="row-buffer-after">
                            <th scope="row"><label for="buffer_after"><?php esc_html_e('Buffer after (min)', 'availability-datepicker'); ?></label></th>
                            <td>
                                <input type="number" name="time_settings[buffer_after]" id="buffer_after"
                                    value="<?php echo esc_attr($rules['time_settings']['buffer_after'] ?? 0); ?>" min="0" class="regular-text" style="width: 320px!important;">
                                <p class="description"><?php esc_html_e('Rest time to reserve after each appointment.', 'availability-datepicker'); ?></p>
                            </td>
                        </tr>
                        <tr id="row-min-days">
                            <th scope="row"><label for="min_days"><?php esc_html_e('Min Days', 'availability-datepicker'); ?></label></th>
                            <td>
                                <input type="number" name="time_settings[min_days]" id="min_days"
                                    value="<?php echo esc_attr($rules['time_settings']['min_days'] ?? 1); ?>" min="1" class="regular-text" style="width: 320px!important;">
                                <p class="description"><?php esc_html_e('Minimum days per booking.', 'availability-datepicker'); ?></p>
                            </td>
                        </tr>
                        <tr id="row-max-days">
                            <th scope="row"><label for="max_days"><?php esc_html_e('Max Days', 'availability-datepicker'); ?></label></th>
                            <td>
                                <input type="number" name="time_settings[max_days]" id="max_days"
                                    value="<?php echo esc_attr($rules['time_settings']['max_days'] ?? 14); ?>" min="1" class="regular-text" style="width: 320px!important;">
                                <p class="description"><?php esc_html_e('Maximum days per booking.', 'availability-datepicker'); ?></p>
                            </td>
                        </tr>
                        <tr id="row-min-duration">
                            <th scope="row"><label for="min_duration"><?php esc_html_e('Min Duration (hrs)', 'availability-datepicker'); ?></label></th>
                            <td>
                                <input type="number" name="time_settings[min_duration]" id="min_duration"
                                    value="<?php echo esc_attr($rules['time_settings']['min_duration'] ?? 1); ?>" min="0" step="0.5" class="regular-text" style="width: 320px!important;">
                                <p class="description"><?php esc_html_e('Minimum duration in hours.', 'availability-datepicker'); ?></p>
                            </td>
                        </tr>
                        <tr id="row-max-duration">
                            <th scope="row"><label for="max_duration"><?php esc_html_e('Max Duration (hrs)', 'availability-datepicker'); ?></label></th>
                            <td>
                                <input type="number" name="time_settings[max_duration]" id="max_duration"
                                    value="<?php echo esc_attr($rules['time_settings']['max_duration'] ?? 24); ?>" min="0" step="0.5" class="regular-text" style="width: 320px!important;">
                                <p class="description"><?php esc_html_e('Maximum duration in hours.', 'availability-datepicker'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="avdp-card">
                    <h2><?php esc_html_e('Custom availability', 'availability-datepicker'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label><?php esc_html_e('Unavailable Dates', 'availability-datepicker'); ?></label></th>
                            <td>
                                <div id="blocked-dates-list">
                                    <?php 
                                    $blocked = isset($rules['date_overrides']['blocked_dates']) ? $rules['date_overrides']['blocked_dates'] : array();
                                    if (!empty($blocked)): 
                                    ?>
                                        <?php foreach ($blocked as $index => $date): ?>
                                            <div class="date-override-row">
                                                <input type="date" name="date_overrides[blocked_dates][]" value="<?php echo esc_attr($date); ?>" required>
                                                <button type="button" class="button remove-date-override"><?php esc_html_e('Remove', 'availability-datepicker'); ?></button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="button add-blocked-date" data-target="blocked-dates-list">
                                    <?php esc_html_e('Add new', 'availability-datepicker'); ?>
                                </button>
                                <button type="button" class="button avdp-btn-ghost avdp-add-range add-date-range" data-target="blocked-dates-list" data-type="blocked">
                                    <?php esc_html_e('Add range', 'availability-datepicker'); ?>
                                </button>
                                <p class="description"><?php esc_html_e('Select specific dates to block (e.g. holidays).', 'availability-datepicker'); ?></p>
                            </td>
                        </tr>
                        <?php 
                        // Determine if time inputs should be shown based on booking method
                        $method = $rules['method'] ?? 'fixed';
                        $show_time_inputs = ($method !== 'daily');
                        ?>
                        <tr id="row-allowed-dates">
                            <th scope="row"><label><?php esc_html_e('Available Dates', 'availability-datepicker'); ?></label></th>
                            <td>
                                <div id="allowed-dates-list" data-show-times="<?php echo $show_time_inputs ? '1' : '0'; ?>">
                                    <?php 
                                    $allowed = isset($rules['date_overrides']['allowed_dates']) ? $rules['date_overrides']['allowed_dates'] : array();
                                    if (!empty($allowed)): 
                                    ?>
                                        <?php foreach ($allowed as $index => $date_data): ?>
                                            <div class="date-override-row">
                                                <input type="date" name="date_overrides[allowed_dates][<?php echo esc_attr($index); ?>][date]"
                                                    value="<?php echo esc_attr($date_data['date'] ?? ''); ?>" required>
                                                <?php if ($show_time_inputs): ?>
                                                <input type="time" name="date_overrides[allowed_dates][<?php echo esc_attr($index); ?>][start]"
                                                    value="<?php echo esc_attr($date_data['start'] ?? '09:00'); ?>" class="allowed-date-time-inputs" required>
                                                <span class="allowed-date-time-inputs"><?php esc_html_e('to', 'availability-datepicker'); ?></span>
                                                <input type="time" name="date_overrides[allowed_dates][<?php echo esc_attr($index); ?>][end]"
                                                    value="<?php echo esc_attr($date_data['end'] ?? '17:00'); ?>" class="allowed-date-time-inputs" required>
                                                <?php endif; ?>
                                                <button type="button" class="button remove-date-override"><?php esc_html_e('Remove', 'availability-datepicker'); ?></button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="button add-allowed-date" data-target="allowed-dates-list">
                                    <?php esc_html_e('Add new', 'availability-datepicker'); ?>
                                </button>
                                <button type="button" class="button avdp-btn-ghost avdp-add-range add-date-range" data-target="allowed-dates-list" data-type="allowed">
                                    <?php esc_html_e('Add range', 'availability-datepicker'); ?>
                                </button>
                                <p class="description"><?php esc_html_e('Add extra availability for specific dates (e.g. overtime).', 'availability-datepicker'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <p class="submit avdp-submit-row">
                    <button type="submit" name="avdp_save_availability"
                        class="button button-primary"><?php esc_html_e('Save Changes', 'availability-datepicker'); ?></button>
                    <button type="button" class="button avdp-btn-ghost avdp-restore-defaults">
                        <?php esc_html_e('Reset to default', 'availability-datepicker'); ?>
                    </button>
                </p>
            </form>

        </div>
        <?php
    }
}
