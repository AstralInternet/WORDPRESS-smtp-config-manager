<?php
/**
 * Fired during plugin activation and deactivation
 *
 * @package Simple_SMTP_DKIM_Manager
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Handle plugin activation, deactivation, and default option setup.
 *  *
 *  * Creates the log table, generates encryption keys, sets up secure
 *  * directories, and registers default options on first activation.
 *  *
 *  * @since 1.0.0
 */
class Simple_SMTP_DKIM_Activator {
    
    /**
     * Plugin activation
     */
    public static function activate() {
        // Generate encryption key if it doesn't exist
        if (!get_option('simple_smtp_dkim_encryption_key')) {
            $encryption_key = self::generate_encryption_key();
            add_option('simple_smtp_dkim_encryption_key', $encryption_key, '', 'no');
        }
        
        // Create secure upload directory
        self::create_secure_directory();
        
        // Create database table for email logs
        self::create_log_table();
        
        // Set default options
        self::set_default_options();
    }
    
    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        // Note: We don't delete data on deactivation, only on uninstall
    }
    
    /**
     * Generate a secure encryption key
     */
    private static function generate_encryption_key() {
        if (function_exists('random_bytes')) {
            return base64_encode(random_bytes(32));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            return base64_encode(openssl_random_pseudo_bytes(32));
        } else {
            // Fallback (less secure)
            return base64_encode(wp_generate_password(32, true, true));
        }
    }
    
    /**
     * Create secure directory for DKIM keys
     */
    private static function create_secure_directory() {
        $upload_dir = SIMPLE_SMTP_DKIM_UPLOAD_DIR;
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            wp_mkdir_p($upload_dir);
        }
        
        // Create .htaccess to prevent direct access
        $htaccess_file = $upload_dir . '.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "# Deny all direct access to files in this directory\n";
            $htaccess_content .= "Order deny,allow\n";
            $htaccess_content .= "Deny from all\n";
            $htaccess_content .= "<Files ~ \"^.*$\">\n";
            $htaccess_content .= "    Deny from all\n";
            $htaccess_content .= "</Files>\n";
            
            file_put_contents($htaccess_file, $htaccess_content);
        }
        
        // Create index.php to prevent directory listing
        $index_file = $upload_dir . 'index.php';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, '<?php // Silence is golden');
        }
    }
    
    /**
     * Create database table for email logs
     */
    private static function create_log_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'smtp_email_logs';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            to_email varchar(255) NOT NULL,
            from_email varchar(255) NOT NULL,
            subject text NOT NULL,
            email_body longtext,
            email_headers text,
            status varchar(50) NOT NULL,
            error_message text,
            dkim_signed tinyint(1) DEFAULT 0,
            PRIMARY KEY  (id),
            KEY timestamp (timestamp),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Store the database version
        add_option('simple_smtp_dkim_db_version', SIMPLE_SMTP_DKIM_VERSION, '', 'no');
    }
    
    /**
     * Set default options
     */
    private static function set_default_options() {
        $defaults = array(
            'simple_smtp_dkim_enabled' => false,
            'simple_smtp_dkim_mailer_type' => 'smtp',
            'simple_smtp_dkim_host' => '',
            'simple_smtp_dkim_port' => '587',
            'simple_smtp_dkim_secure' => 'tls',
            'simple_smtp_dkim_auth' => true,
            'simple_smtp_dkim_username' => '',
            'simple_smtp_dkim_password' => '',
            'simple_smtp_dkim_from_email' => get_option('admin_email'),
            'simple_smtp_dkim_from_name' => get_option('blogname'),
            'simple_smtp_dkim_force_from' => false,
            'simple_smtp_dkim_dkim_enabled' => false,
            'simple_smtp_dkim_dkim_domain' => '',
            'simple_smtp_dkim_dkim_selector' => '',
            'simple_smtp_dkim_dkim_passphrase' => '',
            'simple_smtp_dkim_dkim_private_key' => '',
            'simple_smtp_dkim_dkim_storage_method' => 'database', // 'database' or 'file'
            'simple_smtp_dkim_dkim_file_path' => '',
            'simple_smtp_dkim_logging_enabled' => false,
            'simple_smtp_dkim_log_retention_days' => 30,
            'simple_smtp_dkim_log_email_body' => false,
            'simple_smtp_dkim_debug_mode' => false,
            'simple_smtp_dkim_last_test_success' => false,
            'simple_smtp_dkim_dns_verified' => false,
            'simple_smtp_dkim_delete_on_uninstall' => false,
            // OAuth2
            'simple_smtp_dkim_oauth_provider' => '',
            'simple_smtp_dkim_oauth_grant_type' => 'authorization_code',
            'simple_smtp_dkim_oauth_auth_method' => 'secret',
            'simple_smtp_dkim_oauth_client_id' => '',
            'simple_smtp_dkim_oauth_client_secret' => '',
            'simple_smtp_dkim_oauth_refresh_token' => '',
            'simple_smtp_dkim_oauth_cert_private_key' => '',
            'simple_smtp_dkim_oauth_cert_thumbprint' => '',
            'simple_smtp_dkim_oauth_smtp_address' => '',
            'simple_smtp_dkim_oauth_tenant' => '',
            'simple_smtp_dkim_oauth_hosted_domain' => '',
            'simple_smtp_dkim_oauth_project_id' => '',
            'simple_smtp_dkim_oauth_service_account' => '',
            'simple_smtp_dkim_oauth_impersonate' => '',
        );
        
        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value, '', 'no');
            }
        }
    }
}
