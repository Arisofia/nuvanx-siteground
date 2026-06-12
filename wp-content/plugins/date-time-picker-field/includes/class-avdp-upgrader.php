<?php
/**
 * Plugin Upgrader.
 *
 * Tracks installed version, runs upgrade routines, and shows breaking-change
 * notices both in the plugin-list update row (via the mu-plugin) and as an
 * admin banner after a successful upgrade.
 *
 * @package Availability_Datepicker
 * @since 3.0
 */

class AVDP_Upgrader
{
    /**
     * WP option that stores the last installed version of this plugin.
     */
    const VERSION_OPTION = 'avdp_installed_version';

    /**
     * Transient key for the post-upgrade admin banner.
     */
    const POST_UPGRADE_TRANSIENT = 'avdp_post_upgrade_notice';

    /**
     * Register all upgrader hooks. Called once from AVDP_Main.
     */
    public static function register()
    {
        add_action('admin_init',   array('AVDP_Upgrader', 'maybe_run_upgrade'));
        add_action('admin_notices', array('AVDP_Upgrader', 'maybe_show_post_upgrade_notice'));
        add_action('admin_init',   array('AVDP_Upgrader', 'maybe_dismiss_notice'));
    }

    /**
     * Record version on fresh activation (no previous version stored).
     * Called from the plugin activation hook.
     */
    public static function on_activate()
    {
        if (get_option(self::VERSION_OPTION, null) === null) {
            update_option(self::VERSION_OPTION, AVDP_VERSION);
        }
    }

    /**
     * Return the previously installed version, or null for a fresh install.
     *
     * @return string|null
     */
    public static function get_installed_version()
    {
        return get_option(self::VERSION_OPTION, null);
    }

    /**
     * True when the stored version is older than 3.0 (legacy upgrade path).
     *
     * @return bool
     */
    public static function needs_upgrade()
    {
        $previous = self::get_installed_version();
        return $previous !== null && version_compare($previous, '3.0', '<');
    }

    /**
     * Run upgrade routines and update the stored version.
     *
     * @return bool True on success.
     */
    public static function run()
    {
        $previous = self::get_installed_version();

        // Always reset so re-upgrades pick up the latest v2.0 data.
        delete_option(AVDP_Legacy_Migrator::MIGRATION_COMPLETED_OPTION);

        // Run legacy settings migration (dtpicker / dtpicker_advanced → avdp_settings).
        AVDP_Legacy_Migrator::maybe_migrate();

        set_transient(self::POST_UPGRADE_TRANSIENT, $previous, DAY_IN_SECONDS);

        update_option(self::VERSION_OPTION, AVDP_VERSION);

        return true;
    }

    /**
     * Called on admin_init: auto-run upgrade if needed, or record version for
     * a fresh install.
     */
    public static function maybe_run_upgrade()
    {
        if (self::needs_upgrade()) {
            self::run();
            return;
        }

        // Fresh install — record current version so future upgrades are detectable.
        if (self::get_installed_version() === null) {
            update_option(self::VERSION_OPTION, AVDP_VERSION);
        }
    }

    /**
     * Returns the breaking-change warning HTML injected into the plugin-list
     * update row. Also used by the mu-plugin before v3.0 is loaded.
     *
     * @return string
     */
    public static function get_upgrade_notice_html()
    {
        return
            '<br><br>' .
            '<strong style="color:#d63638;">Breaking Changes in v3.0</strong> &mdash; ' .
            'This update introduces a new unified settings structure. ' .
            'Your existing settings will be migrated automatically, but ' .
            '<strong>we strongly recommend creating a full site backup before upgrading.</strong>';
    }

    /**
     * Show the post-upgrade admin banner (appears after the user upgrades).
     */
    public static function maybe_show_post_upgrade_notice()
    {
        $previous = get_transient(self::POST_UPGRADE_TRANSIENT);
        if (!$previous) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $dismiss_url = wp_nonce_url(
            add_query_arg('avdp_dismiss_upgrade_notice', '1'),
            'avdp_dismiss_upgrade_notice'
        );
        ?>
        <div class="notice notice-warning" style="border-left-color:#d63638; padding:12px 16px;">
            <p>
                <strong>&#9888; Availability Datepicker upgraded to v3.0</strong><br>
                Upgraded from <strong>v<?php echo esc_html($previous); ?></strong>.
                Your settings have been migrated to the new format automatically.
                Please verify your availability configuration looks correct before going live.
                <a href="<?php echo esc_url($dismiss_url); ?>" style="margin-left:12px;">Dismiss</a>
            </p>
        </div>
        <?php
    }

    /**
     * Handle the dismiss link for the post-upgrade notice.
     */
    public static function maybe_dismiss_notice()
    {
        if (empty($_GET['avdp_dismiss_upgrade_notice'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'avdp_dismiss_upgrade_notice')) {
            return;
        }

        delete_transient(self::POST_UPGRADE_TRANSIENT);
        wp_safe_redirect(remove_query_arg(array('avdp_dismiss_upgrade_notice', '_wpnonce')));
        exit;
    }
}
