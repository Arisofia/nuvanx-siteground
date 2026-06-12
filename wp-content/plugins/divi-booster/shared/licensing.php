<?php 
namespace DiviBooster\PLUGIN\Licensing;

$licensing = new Licensing(
    $config['plugin_file'], 
    $config['plugin_slug'],
    $config['plugin_name'],
    $config['plugin_url'],
    $config['edd_store_url'],
    $config['edd_item_id']
);
$licensing->init();

class Licensing {

    private $plugin_name;
    private $settings_page;
    private $license_key_option;
    private $license_status_option;
    private $settings_fields;
    private $plugin_file;
    private $settings_nonce;
    private $plugin_url;
    private $store_url; // URL of site with EDD store
    private $store_item_id; // id of the plugin in EDD
    private $plugin_slug;

    public function __construct($plugin_file, $plugin_slug, $plugin_name, $plugin_url, $store_url, $store_item_id) {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = $plugin_slug;
        $this->plugin_name = $plugin_name;
        $this->plugin_url = $plugin_url;
        $this->store_url = $store_url;
        $this->store_item_id = $store_item_id;

        $this->settings_page = $this->plugin_slug.'-settings';
        $this->settings_fields = $this->plugin_slug.'-license';
        $this->license_key_option = $this->plugin_slug.'-license_key';
        $this->license_status_option = $this->plugin_slug.'-license_status';
        $this->settings_nonce = $this->plugin_slug.'-nonce';
    }

    public function init() {
        add_action('admin_menu', array($this, 'dbal_license_menu'));
        add_action('admin_init', array($this, 'dbal_register_option'));
        add_action('admin_init', array($this, 'dbal_deactivate_license'));
        add_action('admin_init', array($this, 'dbal_activate_license'), 11);
        add_action('admin_notices', array($this, 'dbal_admin_notices'));
        add_action( 'after_plugin_row_'.plugin_basename($this->plugin_file), array($this, 'dbal_after_plugin_row'), 20, 3);
        add_action('admin_head', array($this, 'dbal_plugins_php_license_required_css'));
    }

    function dbal_license_menu() {
        add_options_page($this->plugin_name, $this->plugin_name, 'manage_options', $this->settings_page, array($this, 'dbal_license_page'));
    }
    
    function dbal_license_page() {
        $license = get_option($this->license_key_option);
        $status  = get_option($this->license_status_option, false );
        ?>
        <div class="wrap">
            <h2><?php esc_html_e($this->plugin_name); ?></h2>
            <form method="post" action="options.php">
    
                <?php settings_fields($this->settings_fields); ?>
                <?php wp_nonce_field($this->settings_nonce, $this->settings_nonce); ?>
    
                <table class="form-table">
                    <tbody>
                        <tr valign="top">
                            <th scope="row" valign="top">
                                <?php _e('License Key'); ?>
                            </th>
                            <td>
                                <input id="<?php esc_attr_e($this->license_key_option); ?>" name="<?php esc_attr_e($this->license_key_option); ?>" type="password" class="regular-text" value="<?php esc_attr_e( $license ); ?>" />
                                <?php if( $status !== false && $status == 'valid' ) { ?>
                                    <input type="submit" class="button-secondary" name="edd_license_deactivate" value="<?php _e('Deactivate'); ?>"/>
                                    <span style="color:green;line-height:28px;margin-right:1em;margin-left: 1em;"><?php _e('Active'); ?></span>
                                <?php } else { ?>
                                    <span style="color:red;line-height:28px;margin-right:1em;margin-left: 1em;"><?php _e('Inactive'); ?></span>
                                    <p>Enter your license key to enable updates.</p><p>You can get your license key from your <a href="https://divibooster.com/your-account/" target="_blank">Divi Booster account</a>, or <a href="<?php esc_attr_e($this->plugin_url); ?>" target="_blank">purchase a license</a>.</p>
                                <?php } ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <?php submit_button(); ?>
    
            </form>
        <?php
    }
    
    // === Register the license key option ===
    
    function dbal_register_option() {
        // creates our settings in the options table
        register_setting($this->settings_fields, $this->license_key_option, array($this, 'dbal_sanitize_license') );
    }
    
    function dbal_sanitize_license( $new ) {
        $old = get_option($this->license_key_option);
        if( $old && $old != $new ) {
            delete_option($this->license_status_option); // new license has been entered, so must reactivate
        }
        return $new;
    }
    
    
    // === License activation ===
    
    
    function dbal_activate_license() {
        
        if( isset( $_POST['option_page'] ) && $_POST['option_page'] === $this->settings_fields ) {
            // run a quick security check
             if( ! check_admin_referer($this->settings_nonce, $this->settings_nonce) )
                return; // get out if we didn't click the Activate button
            $license = trim( $_POST[$this->license_key_option]);
            update_option($this->license_key_option, $license );
    
            $response = $this->dbal_submit_license('activate', $license);
            $message = $this->activation_error($response);

            // Check if anything passed on a message constituting a failure
            if ( ! empty( $message ) ) {
                $base_url = admin_url('options-general.php?page='.$this->settings_page);
                $redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );
                wp_redirect( $redirect );
                exit();
            }
            // $license_data->license will be either "valid" or "invalid"
            $license_data = json_decode( wp_remote_retrieve_body( $response ) );
            $validity = $license_data->license;

            // If license server not responding correctly, allow license to validate locally instead of throwing an error
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) !== 200) {
                $validity = 'valid';
            }
            
            update_option($this->license_status_option, $validity );
            wp_redirect( admin_url( 'options-general.php?page=' . $this->settings_page) );
            exit();
        }
    }

    // Return the activation error, if any, from the given response / WP_Error object
    public function activation_error($response) {
        $message = '';
        if (is_wp_error( $response )) {
            $message =  ( $response->get_error_message() !== '' ) ? $response->get_error_message() : __( 'An error occurred while connecting to the license server, please try again.' );
            $message .= ($response->get_error_code() !== '')?' (Error code '.$response->get_error_code().')':'';
        } elseif (wp_remote_retrieve_response_code($response) !== 200) {
            $message = ''; // If license server not responding correctly, allow license to validate locally instead of throwing an error
            //$message = sprintf('An error (HTTP code %d) occurred while connecting to the license server, please try again.', wp_remote_retrieve_response_code($response));
        } else {
            $license_data = json_decode( wp_remote_retrieve_body( $response ) );
            if ( false === $license_data->success ) {
                switch( $license_data->error ) {
                    case 'expired' :
                        $message = sprintf(
                            __( 'Your license key expired on %s.' ),
                            date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
                        );
                        break;
                    case 'revoked' :
                        $message = __( 'Your license key has been disabled.' );
                        break;
                    case 'missing' :
                        $message = __( 'Invalid license.' );
                        break;
                    case 'invalid_item_id' :
                        $message = __( 'Invalid / empty license key.' );
                        break;
                    case 'invalid' :
                    case 'site_inactive' :
                        $message = __( 'Your license is not active for this URL.' );
                        break;
                    case 'item_name_mismatch' :
                        $message = sprintf( __( 'This appears to be an invalid license key for %s.' ), $this->plugin_name);
                        break;
                    case 'no_activations_left':
                        $message = __( 'Your license key has reached its activation limit.' );
                        break;
                    default :
                        $message = __( 'An error occurred, please try again. (error code: '.esc_html($license_data->error).')' );
                        break;
                }
            }
        }
        return $message;
    }
    
    
    // === License deactivation ===
    
    
    function dbal_deactivate_license() {
        if( isset( $_POST['option_page'] ) && $_POST['option_page'] !== $this->settings_fields ) {
            return; 
        }

        // listen for our activate button to be clicked
        if( isset( $_POST['edd_license_deactivate'] ) ) {
            
    
            // run a quick security check
             if( ! check_admin_referer($this->settings_nonce, $this->settings_nonce) )
                return; // get out if we didn't click the Activate button
                
            // retrieve the license from the database
            $license = trim( get_option($this->license_key_option) );
    
            if (!$this->dbal_license_deactivated_in_edd($license)) {
                
                $response = $this->dbal_submit_license('deactivate', $license);
                   
                // Always deactivate locally, regardless of server deactivation success
                /* 
                // make sure the response came back okay
                if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
                    $message =  ( is_wp_error( $response ) && $response->get_error_message() !== '' ) ? $response->get_error_message() : __( 'An error occurred while connecting to the license server, please try again.' );
                    
                } else {
                    
                    $license_data = json_decode( wp_remote_retrieve_body( $response ) );
                    if ( false === $license_data->success ) {
                        $message = __( 'An error occurred while deactivating the license, please try again.' );
                    }
                }
                // Check if anything passed on a message constituting a failure
                if ( ! empty( $message ) ) {
                    $base_url = admin_url('options-general.php?page='.$this->settings_page);
                    $redirect = add_query_arg( array( 'sl_deactivation' => 'false', 'message' => urlencode( $message ) ), $base_url );
                    wp_redirect( $redirect );
                    exit();
                }
                */
            }
            
            update_option($this->license_status_option, 'deactivated');
            wp_redirect( admin_url( 'options-general.php?page=' . $this->settings_page) );
            exit();
        }
    }
    
    function dbal_license_deactivated_in_edd($license) {
        $deactivated = false;
        $response = $this->dbal_submit_license('check', $license);
        if (!is_wp_error($response)) {
            $license_data = json_decode(wp_remote_retrieve_body($response));
            if ($license_data->license === 'inactive') {
                $deactivated = true;			
            }
        } 
        return $deactivated;
    }
    
    function dbal_submit_license($action, $license) {
        $result = wp_remote_post(
            $this->store_url, 
            array(
                'timeout' => 30, 
                'body' => array(
                    'edd_action' => $action.'_license',
                    'license'    => $license,
                    'item_id'    => $this->store_item_id,
                    'url'        => home_url()
                )
            )
        );
        return $result;
    }
    
    // === Display license activation / deactivation errors to user ===
    
    
    function dbal_admin_notices() {
        if (empty($_GET['page']) || $_GET['page'] !== $this->settings_page) {
            return;
        }
        if ( isset( $_GET['sl_activation'] ) && ! empty( $_GET['message'] ) ) {
            switch( $_GET['sl_activation'] ) {
                case 'false':
                    $message = urldecode( $_GET['message'] );
                    ?>
                    <div class="error">
                        <p><?php esc_html_e($message); ?></p>
                    </div>
                    <?php
                    break;
                case 'true':
                default:
                    // Can put a custom success message here for when activation is successful if they want.
                    break;
            }
        }
        if ( isset( $_GET['sl_deactivation'] ) && ! empty( $_GET['message'] ) ) {
            switch( $_GET['sl_deactivation'] ) {
                case 'false':
                    $message = urldecode( $_GET['message'] );
                    ?>
                    <div class="error">
                        <p>Error: <?php esc_html_e($message); ?></p>
                    </div>
                    <?php
                    break;
                case 'true':
                default:
                    // Can put a custom success message here for when activation is successful if they want.
                    break;
            }
        }
    }
    
    // === Notify admin via plugins.php that license required to activate plugin ===
    
    
    function dbal_after_plugin_row($plugin_file, $plugin_data, $status) {
        
        // Don't display message if the plugin is correctly licensed
        if (get_option($this->license_status_option) === 'valid') { 
            return;
        }
        
        // Display license message
        $settings_url = admin_url('options-general.php?page='.$this->settings_page);
        ?>
        <tr class="plugin-update-tr active"><td colspan="4" class="plugin-update colspanchange"><div class="update-message notice inline notice-warning notice-alt"><p>Enter your license key on the <a href="<?php esc_attr_e($settings_url); ?>">settings page</a> to enable updates. You can get your license from your <a href="https://divibooster.com/your-account/purchase-history/" target="_blank">Divi Booster account</a>, or <a href="<?php esc_attr_e($this->plugin_url); ?>" target="_blank">purchase a license</a>.</p></div></td>
        </tr>
        <?php
    }
    
    
    function dbal_plugins_php_license_required_css() {
        
        // Make sure we're on plugins.php
        global $pagenow;
        if ($pagenow !== 'plugins.php') { return; }
        
        // Don't add CSS if the plugin is correctly licensed
        if (get_option($this->license_status_option) === 'valid') { 
            return;
        }
        
        // Remove border bottom after shortcode enabler plugin entry
        ?>
        <style>
        .plugins tr[data-slug="<?php esc_attr_e($this->plugin_slug); ?>"].active td, 
        .plugins tr[data-slug="<?php esc_attr_e($this->plugin_slug); ?>"].active th {
            box-shadow: none !important;
        }
        </style>
        <?php
    }

}


