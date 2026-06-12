<?php
/**
 * Admin UI Components Helper
 *
 * Reusable UI components for consistent admin interface
 *
 * @package Availability_Datepicker
 * @since 1.0.0
 */

class AVDP_Admin_Components
{
    /**
     * Render page header
     *
     * @param string $title Page title
     * @param array $actions Array of action buttons ['label' => 'url', ...]
     * @param array $primary_actions Array of primary action buttons
     */
    public static function page_header($title, $actions = array(), $primary_actions = array())
    {
        ?>
        <div class="avdp-page-header">
            <h1><?php echo esc_html($title); ?></h1>
            <?php if (!empty($actions) || !empty($primary_actions)): ?>
                <div class="avdp-page-actions">
                    <?php foreach ($primary_actions as $label => $url): ?>
                        <?php
                        $is_js = strpos($url, 'javascript:') === 0;
                        $href = $is_js ? $url : esc_url($url);
                        ?>
                        <a href="<?php echo esc_attr($href); ?>" class="button button-primary">
                            <?php echo esc_html($label); ?>
                        </a>
                    <?php endforeach; ?>
                    <?php foreach ($actions as $label => $url): ?>
                        <?php
                        $is_js = strpos($url, 'javascript:') === 0;
                        $href = $is_js ? $url : esc_url($url);
                        ?>
                        <a href="<?php echo esc_attr($href); ?>" class="button">
                            <?php echo esc_html($label); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php

        // Output notices automatically below header
        self::display_notices();
    }

    /**
     * Display admin notices
     * 
     * Handles displaying success/error messages in a standard format.
     * Uses WordPress settings_errors() but wraps it in our custom container for styling.
     */
    public static function display_notices()
    {
        // Only run if there are errors/messages to show
        $settings_errors = get_settings_errors('avdp_messages');
        if (empty($settings_errors)) {
            return;
        }

        foreach ($settings_errors as $key => $details) {
            $type = $details['type'];
            $message = $details['message'];
            $alert_class = 'avdp-alert-info';
            if ($type === 'error') {
                $alert_class = 'avdp-alert-error';
            } elseif ($type === 'updated' || $type === 'success') {
                $alert_class = 'avdp-alert-success';
            }
            ?>
            <div class="avdp-alert <?php echo esc_attr($alert_class); ?>">
                <div class="avdp-alert-content">
                    <?php echo wp_kses_post($message); ?>
                </div>
                <button type="button" class="avdp-alert-dismiss" aria-label="Dismiss">×</button>
            </div>
            <?php
        }
    }

    /**
     * Render section header
     *
     * @param string $title Section title
     * @param string $description Optional description
     */
    public static function section_header($title, $description = '')
    {
        ?>
        <div class="avdp-section-header">
            <h2><?php echo esc_html($title); ?></h2>
            <?php if ($description): ?>
                <p><?php echo esc_html($description); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render status badge
     *
     * @param string $status Status type (active, inactive, pending, confirmed)
     * @param string $label Optional custom label
     */
    public static function status_badge($status, $label = '')
    {
        $label = $label ?: ucfirst($status);
        $class = 'avdp-status avdp-status-' . esc_attr($status);
        ?>
        <span class="<?php echo esc_attr($class); ?>">
            <?php echo esc_html($label); ?>
        </span>
        <?php
    }

    /**
     * Render empty state
     *
     * @param string $title Empty state title
     * @param string $message Empty state message
     * @param string $action_label Optional action button label
     * @param string $action_url Optional action button URL
     * @param string $icon Optional icon (emoji or dashicon class)
     */
    public static function empty_state($title, $message, $action_label = '', $action_url = '', $icon = '📋')
    {
        ?>
        <div class="avdp-empty-state">
            <div class="avdp-empty-state-icon"><?php echo esc_html($icon); ?></div>
            <h3><?php echo esc_html($title); ?></h3>
            <p><?php echo esc_html($message); ?></p>
            <?php if ($action_label && $action_url): ?>
                <a href="<?php echo esc_url($action_url); ?>" class="button button-primary">
                    <?php echo esc_html($action_label); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render row actions
     *
     * @param array $edit_actions   Actions usually for editing
     * @param array $delete_actions Actions usually for deleting
     */
    public static function row_actions($edit_actions = array(), $delete_actions = array())
    {
        $actions = array();

        foreach ($edit_actions as $label => $url) {
            $actions[] = sprintf('<a href="%s">%s</a>', esc_url($url), esc_html($label));
        }

        foreach ($delete_actions as $label => $url) {
            $actions[] = sprintf('<a href="%s" class="delete" onclick="return confirm(\'%s\');">%s</a>', esc_url($url), esc_js(__('Are you sure?', 'availability-datepicker')), esc_html($label));
        }

        if (!empty($actions)) {
            echo '<div class="row-actions">';
            echo implode(' | ', $actions);
            echo '</div>';
        }
    }

    /**
     * Render start of a card
     * 
     * @param string $title Optional card title
     */
    public static function card_start($title = '')
    {
        ?>
        <div class="avdp-card">
            <?php if ($title): ?>
                <div class="avdp-card-header">
                    <h2><?php echo esc_html($title); ?></h2>
                </div>
            <?php endif; ?>
            <div class="avdp-card-body">
                <?php
    }

    /**
     * Render end of a card
     */
    public static function card_end()
    {
        ?>
            </div>
        </div>
        <?php
    }
    /**
     * Render the top header bar.
     *
     * @since 2.4.0.13
     * @param string $variant Pass 'pro' on the Support page for the expanded PRO-upgrade header.
     */
    public static function header_bar( $variant = '' )
    {
        $is_pro = ( $variant === 'pro' );
        $bar_class = 'avdp-header-bar' . ( $is_pro ? ' avdp-header-bar--pro' : '' );
        ?>
        <div class="<?php echo esc_attr( $bar_class ); ?>">
            <div class="avdp-header-logo">
                <svg width="32" height="32" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M16.4848 9.69697C12.736 9.69697 9.69697 12.736 9.69697 16.4848V47.5151C9.69697 51.264 12.736 54.303 16.4848 54.303H19.0897C19.8613 54.303 20.6012 53.9965 21.1468 53.451L38.2686 36.3292L45.1254 43.186L28.0036 60.3078C25.6395 62.6719 22.4331 64 19.0897 64H16.4848C7.38052 64 0 56.6195 0 47.5151V16.4848C0 7.38052 7.38052 0 16.4848 0V9.69697ZM35.8788 9.69697H28.1212V0H35.8788V9.69697ZM47.5151 0C56.6195 0 64 7.38052 64 16.4848V24.2424H54.303V16.4848C54.303 12.736 51.264 9.69697 47.5151 9.69697V0Z"
                        fill="#E74C3C" />
                    <path opacity="0.4" fill-rule="evenodd" clip-rule="evenodd"
                        d="M62.5799 43.186L43.186 62.5799C41.2925 64.4734 38.2226 64.4734 36.3292 62.5799L32.8867 59.1375L39.7435 52.2807L39.7576 52.2947L55.7231 36.3292L62.5799 43.186Z"
                        fill="#E74C3C" />
                    <rect x="16.4848" y="9.69697" width="11.6364" height="9.69697" fill="#E74C3C" />
                    <rect x="35.8789" y="9.69697" width="11.6364" height="9.69697" fill="#E74C3C" />
                </svg>
                <h2 style="margin: 0;"><?php _e('Availability Datepicker', 'availability-datepicker'); ?></h2>
            </div>
            <div class="avdp-header-right">
                <div class="avdp-header-version">
                    <span>v<?php echo esc_html(AVDP_VERSION); ?></span>
                </div>
                <a href="https://www.inputwp.com" class="button avdp-upgrade-btn" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e('Upgrade for $49', 'availability-datepicker'); ?>
                </a>
            </div>
            <?php if ( $is_pro ) : ?>
            <div class="avdp-header-pro-strip">
                <span class="avdp-header-pro-label"><?php esc_html_e( 'PRO unlocks:', 'availability-datepicker' ); ?></span>
                <ul class="avdp-header-pro-features">
                    <li><?php esc_html_e( 'Bookings (capture, manage &amp; block slots)', 'availability-datepicker' ); ?></li>
                    <li><?php esc_html_e( 'Multiple Resources (custom availability rules)', 'availability-datepicker' ); ?></li>
                    <li><?php esc_html_e( 'Branding &amp; dynamic styling', 'availability-datepicker' ); ?></li>
                    <li><?php esc_html_e( 'Import from .ics (Google, Outlook)', 'availability-datepicker' ); ?></li>
                    <li><?php esc_html_e( 'Divi &amp; WooCommerce integration', 'availability-datepicker' ); ?></li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
}

