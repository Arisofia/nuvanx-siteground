<?php
/**
 * Admin Integration Views.
 *
 * @package Availability_Datepicker
 * @since 2.4.0.13
 */

class AVDP_Integration_Views
{
    /**
     * Render the CSS Selectors page.
     *
     * @param array $selectors Current selectors.
     */
    public static function render($selectors)
    {
        ?>
        <div class="wrap avdp-wrap">
            <h1><?php esc_html_e('Integration', 'availability-datepicker'); ?></h1>

            <?php settings_errors('avdp_messages'); ?>

            <div class="avdp-card">
                <h2><?php esc_html_e('How to Use', 'availability-datepicker'); ?></h2>
                <ol>
                    <li><?php esc_html_e('Copy the Class Name below.', 'availability-datepicker'); ?></li>
                    <li><?php esc_html_e('Go to your form builder (Contact Form 7, WPForms, Elementor, etc.).', 'availability-datepicker'); ?>
                    </li>
                    <li><?php esc_html_e('Add the class name to the "CSS Class" or "ID/Class" setting of your text input field.', 'availability-datepicker'); ?>
                    </li>
                    <li><?php esc_html_e('Save your form.', 'availability-datepicker'); ?></li>
                </ol>
            </div>

            <form method="post" action="" class="avdp-settings-form" data-page="integration">
                <?php wp_nonce_field('avdp_save_selectors', 'avdp_selectors_nonce'); ?>

                <?php
                $availability_rules = AVDP_Settings::get_section('availability_rules');
                $method = $availability_rules['method'] ?? 'slots';
                ?>

                <?php if ($method === 'flexible'): ?>
                    <div class="avdp-tabs-wrapper">
                        <div class="avdp-tabs">
                            <div class="avdp-tab active" data-tab="single"><?php _e('Single Field', 'availability-datepicker'); ?>
                            </div>
                            <div class="avdp-tab" data-tab="separate"><?php _e('Separate Fields', 'availability-datepicker'); ?>
                            </div>
                        </div>

                        <div class="avdp-tab-content active" data-tab="single">
                            <div class="avdp-card">
                                <h2><?php esc_html_e('Single Field', 'availability-datepicker'); ?></h2>
                                <table class="form-table">
                                    <tr>
                                        <th><label><?php esc_html_e('Start Date and Time', 'availability-datepicker'); ?></label></th>
                                        <td>
                                            <input type="text" name="selector_start_datetime" id="selector_start_datetime"
                                                value="<?php echo esc_attr($selectors['start_datetime'] ?? '.avdp-start-datetime'); ?>"
                                                class="regular-text code" style="width: 320px!important;">
                                            <button type="button" class="button avdp-copy-btn avdp-copy-selector"
                                                data-target="selector_start_datetime"><?php esc_html_e('Copy', 'availability-datepicker'); ?></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label><?php esc_html_e('End Date and Time', 'availability-datepicker'); ?></label></th>
                                        <td>
                                            <input type="text" name="selector_end_datetime" id="selector_end_datetime"
                                                value="<?php echo esc_attr($selectors['end_datetime'] ?? '.avdp-end-datetime'); ?>"
                                                class="regular-text code" style="width: 320px!important;">
                                            <button type="button" class="button avdp-copy-btn avdp-copy-selector"
                                                data-target="selector_end_datetime"><?php esc_html_e('Copy', 'availability-datepicker'); ?></button>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="avdp-tab-content" data-tab="separate">
                            <div class="avdp-card">
                                <h2><?php esc_html_e('Separate Fields', 'availability-datepicker'); ?></h2>
                                <table class="form-table">
                                    <tr>
                                        <th><label><?php esc_html_e('Start Date', 'availability-datepicker'); ?></label></th>
                                        <td>
                                            <input type="text" name="selector_start_date" id="selector_start_date"
                                                value="<?php echo esc_attr($selectors['start_date'] ?? '.avdp-start-date'); ?>"
                                                class="regular-text code" style="width: 320px!important;">
                                            <button type="button" class="button avdp-copy-btn avdp-copy-selector"
                                                data-target="selector_start_date"><?php esc_html_e('Copy', 'availability-datepicker'); ?></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label><?php esc_html_e('Start Time', 'availability-datepicker'); ?></label></th>
                                        <td>
                                            <input type="text" name="selector_start_time" id="selector_start_time"
                                                value="<?php echo esc_attr($selectors['start_time'] ?? '.avdp-start-time'); ?>"
                                                class="regular-text code" style="width: 320px!important;">
                                            <button type="button" class="button avdp-copy-btn avdp-copy-selector"
                                                data-target="selector_start_time"><?php esc_html_e('Copy', 'availability-datepicker'); ?></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label><?php esc_html_e('End Date', 'availability-datepicker'); ?></label></th>
                                        <td>
                                            <input type="text" name="selector_end_date" id="selector_end_date"
                                                value="<?php echo esc_attr($selectors['end_date'] ?? '.avdp-end-date'); ?>"
                                                class="regular-text code" style="width: 320px!important;">
                                            <button type="button" class="button avdp-copy-btn avdp-copy-selector"
                                                data-target="selector_end_date"><?php esc_html_e('Copy', 'availability-datepicker'); ?></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label><?php esc_html_e('End Time', 'availability-datepicker'); ?></label></th>
                                        <td>
                                            <input type="text" name="selector_end_time" id="selector_end_time"
                                                value="<?php echo esc_attr($selectors['end_time'] ?? '.avdp-end-time'); ?>"
                                                class="regular-text code" style="width: 320px!important;">
                                            <button type="button" class="button avdp-copy-btn avdp-copy-selector"
                                                data-target="selector_end_time"><?php esc_html_e('Copy', 'availability-datepicker'); ?></button>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                <?php elseif ($method === 'daily'): ?>
                    <div class="avdp-card">
                        <h2><?php _e('Separate Fields', 'availability-datepicker'); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th><label><?php _e('Start Date', 'availability-datepicker'); ?></label></th>
                                <td>
                                    <input type="text" name="selector_start_date" id="selector_start_date"
                                        value="<?php echo esc_attr($selectors['start_date'] ?? '.avdp-start-date'); ?>"
                                        class="regular-text code" style="width: 320px!important;">
                                    <button type="button" class="button avdp-copy-btn avdp-copy-selector"
                                        data-target="selector_start_date"><?php _e('Copy', 'availability-datepicker'); ?></button>
                                </td>
                            </tr>
                            <tr>
                                <th><label><?php _e('End Date', 'availability-datepicker'); ?></label></th>
                                <td>
                                    <input type="text" name="selector_end_date" id="selector_end_date"
                                        value="<?php echo esc_attr($selectors['end_date'] ?? '.avdp-end-date'); ?>"
                                        class="regular-text code" style="width: 320px!important;">
                                    <button type="button" class="button avdp-copy-btn avdp-copy-selector"
                                        data-target="selector_end_date"><?php _e('Copy', 'availability-datepicker'); ?></button>
                                </td>
                            </tr>
                        </table>
                    </div>

                <?php else: // Fixed method ?>
                    <div class="avdp-tabs-wrapper">
                        <div class="avdp-tabs">
                            <div class="avdp-tab active" data-tab="single"><?php _e('Single Field', 'availability-datepicker'); ?>
                            </div>
                            <div class="avdp-tab" data-tab="separate"><?php _e('Separate Fields', 'availability-datepicker'); ?>
                            </div>
                        </div>

                        <div class="avdp-tab-content active" data-tab="single">
                            <div class="avdp-card">
                                <h2><?php esc_html_e('Single Field', 'availability-datepicker'); ?></h2>
                                <table class="form-table">
                                    <tr>
                                        <th><label><?php esc_html_e('Date and Time', 'availability-datepicker'); ?></label></th>
                                        <td>
                                            <input type="text" name="selector_single" id="selector_single"
                                                value="<?php echo esc_attr($selectors['single_field'] ?? '.avdp-datepicker'); ?>"
                                                class="regular-text code" style="width: 320px!important;">
                                            <button type="button" class="button avdp-copy-btn avdp-copy-selector"
                                                data-target="selector_single"><?php esc_html_e('Copy', 'availability-datepicker'); ?></button>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="avdp-tab-content" data-tab="separate">
                            <div class="avdp-card">
                                <h2><?php esc_html_e('Separate Fields', 'availability-datepicker'); ?></h2>
                                <table class="form-table">
                                    <tr>
                                        <th><label><?php esc_html_e('Date', 'availability-datepicker'); ?></label></th>
                                        <td>
                                            <input type="text" name="selector_start_date" id="selector_start_date"
                                                value="<?php echo esc_attr($selectors['start_date'] ?? '.avdp-date'); ?>"
                                                class="regular-text code" style="width: 320px!important;">
                                            <button type="button" class="button avdp-copy-btn avdp-copy-selector"
                                                data-target="selector_start_date"><?php esc_html_e('Copy', 'availability-datepicker'); ?></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label><?php esc_html_e('Time', 'availability-datepicker'); ?></label></th>
                                        <td>
                                            <input type="text" name="selector_start_time" id="selector_start_time"
                                                value="<?php echo esc_attr($selectors['start_time'] ?? '.avdp-time'); ?>"
                                                class="regular-text code" style="width: 320px!important;">
                                            <button type="button" class="button avdp-copy-btn avdp-copy-selector"
                                                data-target="selector_start_time"><?php esc_html_e('Copy', 'availability-datepicker'); ?></button>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <p class="submit avdp-submit-row">
                    <button type="submit" name="avdp_save_selectors"
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
