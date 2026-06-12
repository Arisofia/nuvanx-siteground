<?php
/**
 * Admin Settings Views.
 *
 * @package Availability_Datepicker
 * @since 2.4.0.13
 */

class AVDP_Settings_Views
{
    /**
     * Render the settings page.
     *
     * @param array $settings Current settings.
     */
    public static function render($settings)
    {
        // Helper Data for Date Formats
        $date_formats = array(
            __('Dash Separator', 'availability-datepicker') => array(
                'Y-m-d' => date('Y-m-d'),
                'm-d-Y' => date('m-d-Y'),
                'd-m-Y' => date('d-m-Y'),
            ),
            __('Slash Separator', 'availability-datepicker') => array(
                'Y/m/d' => date('Y/m/d'),
                'm/d/Y' => date('m/d/Y'),
                'd/m/Y' => date('d/m/Y'),
            ),
            __('Dot Separator', 'availability-datepicker') => array(
                'Y.m.d' => date('Y.m.d'),
                'm.d.Y' => date('m.d.Y'),
                'd.m.Y' => date('d.m.Y'),
            ),
            __('Space Separator', 'availability-datepicker') => array(
                'Y m d' => date('Y m d'),
                'm d Y' => date('m d Y'),
                'd m Y' => date('d m Y'),
            ),
            __('No Separator', 'availability-datepicker') => array(
                'Ymd' => date('Ymd'),
                'mdY' => date('mdY'),
                'dmY' => date('dmY'),
            ),
            __('Month Abbreviation', 'availability-datepicker') => array(
                'M j, Y' => date('M j, Y'),
                'j M Y' => date('j M Y'),
            ),
            __('Full Month', 'availability-datepicker') => array(
                'F j, Y' => date('F j, Y'),
                'j F Y' => date('j F Y'),
            ),
        );

        // Helper Data for Time Formats (key = format, value = description only)
        $time_formats = array(
            'H:i' => __('(24h)', 'availability-datepicker'),
            'g:i a' => __('(12h am/pm)', 'availability-datepicker'),
            'g:i A' => __('(12h AM/PM)', 'availability-datepicker'),
        );

        // Helper Data for Timezones (Grouped by UTC Offset)
        $tz_grouped = array();
        $now = new DateTime('now', new DateTimeZone('UTC'));

        foreach (timezone_identifiers_list() as $tz) {
            // Filter mainly location-based zones to keep list clean
            if (preg_match('/^(Africa|America|Antarctica|Arctic|Asia|Atlantic|Australia|Europe|Indian|Pacific)\//', $tz)) {
                $zone = new DateTimeZone($tz);
                $offset = $zone->getOffset($now);
                $tz_grouped[$offset][] = $tz;
            } else if ($tz === 'UTC') {
                $tz_grouped[0][] = $tz;
            }
        }

        ksort($tz_grouped);

        ?>
        <div class="wrap avdp-wrap">
            <h1><?php esc_html_e('Settings', 'availability-datepicker'); ?></h1>

            <?php settings_errors('avdp_messages'); ?>

            <form method="post" action="" id="avdp-settings-form" class="avdp-settings-form" data-page="settings">
                <?php wp_nonce_field('avdp_save_settings', 'avdp_settings_nonce'); ?>

                <div class="avdp-card">
                    <h2><?php esc_html_e('General', 'availability-datepicker'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label
                                    for="date_format"><?php esc_html_e('Date Format', 'availability-datepicker'); ?></label></th>
                            <td>
                                <select id="date_format" name="date_format" class="regular-text"
                                    style="width: 320px!important;">
                                    <?php foreach ($date_formats as $group => $formats): ?>
                                        <optgroup label="<?php echo esc_attr($group); ?>">
                                            <?php foreach ($formats as $format => $example): ?>
                                                <option value="<?php echo esc_attr($format); ?>" <?php selected($settings['date_format'], $format); ?>>
                                                    <?php echo esc_html($example . ' (' . $format . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>

                                    <!-- Custom option support -->
                                    <?php
                                    $all_formats = array_keys(array_merge(...array_values($date_formats)));
                                    if (!in_array($settings['date_format'], $all_formats)):
                                        ?>
                                        <option value="<?php echo esc_attr($settings['date_format']); ?>" selected>
                                            <?php echo esc_html(date($settings['date_format']) . ' (Custom: ' . $settings['date_format'] . ')'); ?>
                                        </option>
                                    <?php endif; ?>
                                </select>
                                <p class="description">
                                    <?php esc_html_e('Format for displaying dates (e.g. d/m/Y).', 'availability-datepicker'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label
                                    for="time_format"><?php esc_html_e('Time Format', 'availability-datepicker'); ?></label></th>
                            <td>
                                <select id="time_format" name="time_format" class="regular-text"
                                    style="width: 320px!important;">
                                    <?php foreach ($time_formats as $format => $label): ?>
                                        <option value="<?php echo esc_attr($format); ?>" <?php selected($settings['time_format'], $format); ?>>
                                            <?php echo esc_html(date($format) . ' ' . $label); ?>
                                        </option>
                                    <?php endforeach; ?>

                                    <?php if (!array_key_exists($settings['time_format'], $time_formats)): ?>
                                        <option value="<?php echo esc_attr($settings['time_format']); ?>" selected>
                                            <?php echo esc_html(date($settings['time_format']) . ' (Custom: ' . $settings['time_format'] . ')'); ?>
                                        </option>
                                    <?php endif; ?>
                                </select>
                                <p class="description">
                                    <?php esc_html_e('Format for displaying times (12h/24h).', 'availability-datepicker'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="timezone"><?php esc_html_e('Timezone', 'availability-datepicker'); ?></label>
                            </th>
                            <td>
                                <select id="timezone" name="timezone" class="regular-text" style="width: 320px!important;">
                                    <?php
                                    $current_timezone = $settings['timezone'] ?? wp_timezone_string();

                                    foreach ($tz_grouped as $offset => $timezones) {
                                        // Format UTC string
                                        $hours = intval($offset / 3600);
                                        $minutes = abs(intval($offset % 3600 / 60));
                                        $utc_str = 'UTC' . ($offset >= 0 ? '+' : '') . sprintf('%d:%02d', $hours, $minutes);

                                        // Get representative cities (up to 3)
                                        $cities = array();
                                        foreach ($timezones as $tz) {
                                            if ($tz === 'UTC')
                                                continue;
                                            $parts = explode('/', $tz);
                                            $city = str_replace('_', ' ', end($parts));
                                            $cities[] = $city;
                                        }

                                        $display_cities = array_slice($cities, 0, 3);
                                        $city_str = !empty($display_cities) ? ' (' . implode(', ', $display_cities) . '...)' : '';

                                        $label = $utc_str . $city_str;

                                        $value_offset_str = sprintf('%s%02d:%02d', $offset >= 0 ? '+' : '-', abs($hours), abs($minutes)); // e.g. +05:30
                                        if ($offset == 0)
                                            $value_offset_str = 'UTC';

                                        $is_selected = false;
                                        // Check if current settings match this offset
                                        // Use try-catch for safety
                                        try {
                                            $curr_tz_obj = new DateTimeZone($current_timezone);
                                            $curr_offset = $curr_tz_obj->getOffset($now);
                                            if ($curr_offset === $offset) {
                                                $is_selected = true;
                                            }
                                        } catch (Exception $e) {
                                            // Fallback if timezone is invalid
                                        }

                                        ?>
                                        <option value="<?php echo esc_attr($value_offset_str); ?>" <?php echo $is_selected ? 'selected' : ''; ?>>
                                            <?php echo esc_html($label); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                                <p class="description">
                                    <?php esc_html_e('Set the timezone for your availability.', 'availability-datepicker'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="avdp-card">
                    <h2><?php esc_html_e('Datepicker Library', 'availability-datepicker'); ?></h2>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label
                                    for="datepicker_library"><?php esc_html_e('Library', 'availability-datepicker'); ?></label>
                            </th>
                            <td>
                                <select id="datepicker_library" name="datepicker_library" class="regular-text"
                                    style="width: 320px!important;">
                                    <option value="xdsoft" <?php selected($settings['datepicker_library'] ?? 'xdsoft', 'xdsoft'); ?>>
                                        <?php esc_html_e('XDsoft', 'availability-datepicker'); ?>
                                    </option>

                                    <option value="flatpickr" disabled>
                                        <?php esc_html_e('Flatpickr (PRO)', 'availability-datepicker'); ?>
                                    </option>
                                </select>
                                <p class="description">
                                    <?php esc_html_e('Select the datepicker library (XDSoft recommended).', 'availability-datepicker'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label
                                    for="datepicker_language"><?php esc_html_e('Language', 'availability-datepicker'); ?></label>
                            </th>
                            <td>
                                <?php
                                // XDSoft supported languages
                                $languages = array(
                                    'en' => 'English',
                                    'ar' => 'Arabic (العربية)',
                                    'az' => 'Azerbaijani (Azərbaycan)',
                                    'bg' => 'Bulgarian (Български)',
                                    'bs' => 'Bosnian (Bosanski)',
                                    'ca' => 'Catalan (Català)',
                                    'ch' => 'Chinese Simplified (简体中文)',
                                    'cs' => 'Czech (Čeština)',
                                    'da' => 'Danish (Dansk)',
                                    'de' => 'German (Deutsch)',
                                    'el' => 'Greek (Ελληνικά)',
                                    'en-GB' => 'English (UK)',
                                    'es' => 'Spanish (Español)',
                                    'et' => 'Estonian (Eesti)',
                                    'eu' => 'Basque (Euskara)',
                                    'fa' => 'Persian (فارسی)',
                                    'fi' => 'Finnish (Suomi)',
                                    'fr' => 'French (Français)',
                                    'gl' => 'Galician (Galego)',
                                    'he' => 'Hebrew (עברית)',
                                    'hr' => 'Croatian (Hrvatski)',
                                    'hu' => 'Hungarian (Magyar)',
                                    'hy' => 'Armenian (Հայերեն)',
                                    'id' => 'Indonesian (Bahasa Indonesia)',
                                    'is' => 'Icelandic (Íslenska)',
                                    'it' => 'Italian (Italiano)',
                                    'ja' => 'Japanese (日本語)',
                                    'ka' => 'Georgian (ქართული)',
                                    'kg' => 'Kyrgyz (Кыргызча)',
                                    'km' => 'Khmer (ភាសាខ្មែរ)',
                                    'ko' => 'Korean (한국어)',
                                    'kr' => 'Korean (한국어)',
                                    'lt' => 'Lithuanian (Lietuvių)',
                                    'lv' => 'Latvian (Latviešu)',
                                    'mk' => 'Macedonian (Македонски)',
                                    'mn' => 'Mongolian (Монгол)',
                                    'nl' => 'Dutch (Nederlands)',
                                    'no' => 'Norwegian (Norsk)',
                                    'pl' => 'Polish (Polski)',
                                    'pt' => 'Portuguese (Português)',
                                    'pt-BR' => 'Portuguese Brazil (Português Brasil)',
                                    'rm' => 'Romansh (Rumantsch)',
                                    'ro' => 'Romanian (Română)',
                                    'ru' => 'Russian (Русский)',
                                    'se' => 'Swedish (Svenska)',
                                    'sk' => 'Slovak (Slovenčina)',
                                    'sl' => 'Slovenian (Slovenščina)',
                                    'sq' => 'Albanian (Shqip)',
                                    'sr' => 'Serbian Cyrillic (Српски)',
                                    'sr-YU' => 'Serbian Latin (Srpski)',
                                    'sv' => 'Swedish (Svenska)',
                                    'th' => 'Thai (ไทย)',
                                    'tr' => 'Turkish (Türkçe)',
                                    'ug' => 'Uyghur (ئۇيغۇرچە)',
                                    'uk' => 'Ukrainian (Українська)',
                                    'vi' => 'Vietnamese (Tiếng Việt)',
                                    'zh' => 'Chinese (中文)',
                                    'zh-TW' => 'Chinese Traditional (繁體中文)',
                                );
                                ?>
                                <select id="datepicker_language" name="datepicker_language" class="regular-text"
                                    style="width: 320px!important;">
                                    <?php foreach ($languages as $code => $name): ?>
                                        <option value="<?php echo esc_attr($code); ?>" <?php selected($settings['datepicker_language'] ?? 'en', $code); ?>>
                                            <?php echo esc_html($name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">
                                    <?php esc_html_e('Select the language for the datepicker interface.', 'availability-datepicker'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="datepicker_theme"><?php esc_html_e('Theme', 'availability-datepicker'); ?></label>
                            </th>
                            <td>
                                <select id="datepicker_theme" name="datepicker_theme" class="regular-text"
                                    style="width: 320px!important;">
                                    <option value="light" <?php selected($settings['datepicker_theme'] ?? 'light', 'light'); ?>>
                                        <?php esc_html_e('Light', 'availability-datepicker'); ?>
                                    </option>
                                    <option value="dark" <?php selected($settings['datepicker_theme'] ?? 'light', 'dark'); ?>>
                                        <?php esc_html_e('Dark', 'availability-datepicker'); ?>
                                    </option>
                                    <option value="auto" <?php selected($settings['datepicker_theme'] ?? 'light', 'auto'); ?>>
                                        <?php esc_html_e('Auto (System)', 'availability-datepicker'); ?>
                                    </option>
                                </select>
                                <p class="description">
                                    <?php esc_html_e('Color theme for the datepicker.', 'availability-datepicker'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label
                                    for="datepicker_display_method"><?php esc_html_e('Display Method', 'availability-datepicker'); ?></label>
                            </th>
                            <td>
                                <select id="datepicker_display_method" name="datepicker_display_method" class="regular-text"
                                    style="width: 320px!important;">
                                    <option value="dropdown" <?php selected($settings['datepicker_display_method'] ?? 'dropdown', 'dropdown'); ?>>
                                        <?php esc_html_e('Dropdown (Default)', 'availability-datepicker'); ?>
                                    </option>
                                    <option value="inline" <?php selected($settings['datepicker_display_method'] ?? 'dropdown', 'inline'); ?>>
                                        <?php esc_html_e('Inline (Always Visible)', 'availability-datepicker'); ?>
                                    </option>
                                </select>
                                <p class="description">
                                    <?php esc_html_e('Show as a dropdown or always visible.', 'availability-datepicker'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>

                <p class="submit avdp-submit-row">
                    <button type="submit" name="avdp_save_settings"
                        class="button button-primary"><?php esc_html_e('Save Settings', 'availability-datepicker'); ?></button>
                    <button type="button" class="button avdp-btn-ghost avdp-restore-defaults">
                        <?php esc_html_e('Reset to default', 'availability-datepicker'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }
}
