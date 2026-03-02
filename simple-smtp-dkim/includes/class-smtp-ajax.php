<?php
/**
 * AJAX request handlers
 *
 * Each public method handles one wp_ajax_ action.
 *
 * @package Simple_SMTP_DKIM
 */

if (!defined('WPINC')) {
    die;
}

/**
 * AJAX request handlers for the plugin admin interface.
 *  *
 *  * Handles test connection, send test email, DKIM validation,
 *  * key generation, log deletion, and email viewing.
 *  *
 *  * @since 1.0.0
 */
class Simple_SMTP_DKIM_Ajax {

    /**
     * Register all AJAX actions
     */
    public static function init() {
        $actions = array(
            'simple_smtp_dkim_test_connection'   => 'test_connection',
            'simple_smtp_dkim_send_test_email'   => 'send_test_email',
            'simple_smtp_dkim_validate_dkim'     => 'validate_dkim',
            'simple_smtp_dkim_delete_all_logs'   => 'delete_all_logs',
            'simple_smtp_dkim_generate_dkim_keys'=> 'generate_dkim_keys',
            'simple_smtp_dkim_view_email'        => 'view_email',
        );
        foreach ($actions as $wp_action => $method) {
            add_action('wp_ajax_' . $wp_action, array(__CLASS__, $method));
        }
    }

    /* ------------------------------------------------------------------
       Helpers
       ------------------------------------------------------------------ */

    private static function require_admin() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'simple-smtp-dkim')));
        }
    }

    /* ------------------------------------------------------------------
       Test SMTP Connection
       ------------------------------------------------------------------ */

    public static function test_connection() {
        self::require_admin();
        check_ajax_referer('simple_smtp_dkim_test_connection', 'nonce');

        $use_saved = isset($_POST['use_saved_password']) && $_POST['use_saved_password'] === 'true';
        $password  = '';

        if ($use_saved) {
            $password = Simple_SMTP_DKIM_Encryption::get_decrypted_option('simple_smtp_dkim_password', '');
            if (empty($password)) {
                wp_send_json_error(array('message' => __('No saved password found. Please enter your password or save settings first.', 'simple-smtp-dkim')));
            }
        } else {
            $password = isset($_POST['password']) ? $_POST['password'] : '';
        }

        $settings = array(
            'host'     => isset($_POST['host']) ? sanitize_text_field($_POST['host']) : '',
            'port'     => isset($_POST['port']) ? intval($_POST['port']) : 587,
            'secure'   => isset($_POST['secure']) ? sanitize_text_field($_POST['secure']) : 'tls',
            'auth'     => isset($_POST['auth']) && $_POST['auth'] === 'true',
            'username' => isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '',
            'password' => $password,
        );

        if (empty($settings['host'])) {
            wp_send_json_error(array('message' => __('SMTP host is required.', 'simple-smtp-dkim')));
        }
        if ($settings['auth'] && (empty($settings['username']) || empty($settings['password']))) {
            wp_send_json_error(array('message' => __('Username and password are required when authentication is enabled.', 'simple-smtp-dkim')));
        }

        $result = Simple_SMTP_DKIM_Mailer::test_connection($settings);

        $data = array(
            'message' => wp_kses_post($result['message']),
            'debug'   => isset($result['debug']) ? esc_html($result['debug']) : '',
        );
        if (isset($result['spf_check'])) {
            $data['spf_check'] = $result['spf_check'];
        }

        $result['success'] ? wp_send_json_success($data) : wp_send_json_error($data);
    }

    /* ------------------------------------------------------------------
       Send Test Email
       ------------------------------------------------------------------ */

    public static function send_test_email() {
        self::require_admin();
        check_ajax_referer('simple_smtp_dkim_send_test_email', 'nonce');

        // Rate limit: 30 s per user
        $key = 'smtp_test_email_' . get_current_user_id();
        if (get_transient($key)) {
            wp_send_json_error(array('message' => __('Please wait at least 30 seconds between test emails.', 'simple-smtp-dkim')));
        }
        set_transient($key, true, 30);

        $to = isset($_POST['to_email']) ? sanitize_email($_POST['to_email']) : '';
        if (empty($to) || !is_email($to)) {
            wp_send_json_error(array('message' => __('A valid recipient email address is required.', 'simple-smtp-dkim')));
        }

        $use_temp = isset($_POST['use_temp_settings']) && $_POST['use_temp_settings'] === 'true';
        $settings = null;

        if ($use_temp) {
            $settings = array(
                'host'     => isset($_POST['host']) ? sanitize_text_field($_POST['host']) : '',
                'port'     => isset($_POST['port']) ? intval($_POST['port']) : 587,
                'secure'   => isset($_POST['secure']) ? sanitize_text_field($_POST['secure']) : 'tls',
                'auth'     => isset($_POST['auth']) ? (bool) $_POST['auth'] : true,
                'username' => isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '',
                'password' => isset($_POST['password']) ? $_POST['password'] : '',
            );
            if (empty($settings['host'])) {
                wp_send_json_error(array('message' => __('SMTP host is required.', 'simple-smtp-dkim')));
            }
        } else {
            if (!get_option('simple_smtp_dkim_enabled', false)) {
                wp_send_json_error(array('message' => __('SMTP is not enabled.', 'simple-smtp-dkim')));
            }
            if (empty(get_option('simple_smtp_dkim_host', ''))) {
                wp_send_json_error(array('message' => __('SMTP host is not configured.', 'simple-smtp-dkim')));
            }
        }

        $result = Simple_SMTP_DKIM_Mailer::send_test_email($to, $settings);

        if ($result['success']) {
            Simple_SMTP_DKIM_Helpers::update_option_no_autoload('simple_smtp_dkim_last_test_success', 1);
            wp_send_json_success(array('message' => $result['message']));
        } else {
            wp_send_json_error(array('message' => $result['message']));
        }
    }

    /* ------------------------------------------------------------------
       Validate DKIM
       ------------------------------------------------------------------ */

    public static function validate_dkim() {
        self::require_admin();
        check_ajax_referer('simple_smtp_dkim_validate_dkim', 'nonce');

        $domain   = isset($_POST['dkim_domain']) ? sanitize_text_field($_POST['dkim_domain']) : '';
        $selector = isset($_POST['dkim_selector']) ? sanitize_text_field($_POST['dkim_selector']) : '';
        $storage  = isset($_POST['storage_method']) ? sanitize_text_field($_POST['storage_method']) : 'database';

        if (empty($domain))   wp_send_json_error(array('message' => __('DKIM domain is required.', 'simple-smtp-dkim')));
        if (empty($selector)) wp_send_json_error(array('message' => __('DKIM selector is required.', 'simple-smtp-dkim')));

        // Retrieve private key
        $private_key = '';
        if ($storage === 'file') {
            $fp = isset($_POST['file_path']) ? sanitize_text_field($_POST['file_path']) : '';
            if (empty($fp)) wp_send_json_error(array('message' => __('File path is required.', 'simple-smtp-dkim')));

            $real = realpath($fp);
            if ($real === false)       wp_send_json_error(array('message' => __('File not found.', 'simple-smtp-dkim')));
            if (!is_readable($real))   wp_send_json_error(array('message' => __('File not readable.', 'simple-smtp-dkim')));

            // Extension check
            $ext = strtolower(pathinfo($real, PATHINFO_EXTENSION));
            if (!in_array($ext, array('pem','private','key'), true)) {
                wp_send_json_error(array('message' => __('Invalid file extension.', 'simple-smtp-dkim')));
            }

            // Prevent reading sensitive WordPress files
            $forbidden = array(ABSPATH, WP_CONTENT_DIR . '/plugins/', WP_CONTENT_DIR . '/themes/', ABSPATH . 'wp-admin/', ABSPATH . 'wp-includes/');
            $allowed   = realpath(SIMPLE_SMTP_DKIM_UPLOAD_DIR);
            foreach ($forbidden as $d) {
                $rd = realpath($d);
                if ($rd !== false && strpos($real, $rd) === 0) {
                    if ($allowed !== false && strpos($real, $allowed) === 0) continue;
                    wp_send_json_error(array('message' => __('File must be outside WordPress directory.', 'simple-smtp-dkim')));
                }
            }

            $private_key = file_get_contents($real);
            if ($private_key === false) wp_send_json_error(array('message' => __('Failed to read file.', 'simple-smtp-dkim')));
        } else {
            $private_key = get_option('simple_smtp_dkim_dkim_private_key', '');
            if (empty($private_key)) wp_send_json_error(array('message' => __('No private key in database. Upload one first.', 'simple-smtp-dkim')));
            if (Simple_SMTP_DKIM_Encryption::is_encryption_available()) {
                $dec = Simple_SMTP_DKIM_Encryption::decrypt($private_key);
                if ($dec !== false) $private_key = $dec;
            }
        }

        $validation = Simple_SMTP_DKIM_Validator::validate_dkim_key($private_key);

        if (!$validation['valid']) {
            $msg = $validation['message'];
            if (isset($validation['debug'])) $msg .= '<br><br><strong>Debug:</strong><br>' . esc_html($validation['debug']);
            wp_send_json_error(array('message' => $msg));
        }

        $dns = Simple_SMTP_DKIM_Validator::check_dkim_dns($domain, $selector);

        $msg = $validation['message'];
        if (isset($validation['debug'])) $msg .= '<br><br><strong>Debug:</strong><br>' . esc_html($validation['debug']);
        if (isset($validation['info']))  $msg .= '<br><br><strong>Key:</strong><br>' . esc_html($validation['info']);
        $msg .= '<br><br><strong>DNS:</strong><br>' . $dns['message'];
        if (!empty($dns['record_content'])) $msg .= '<br><code style="font-size:11px">' . esc_html($dns['record_content']) . '</code>';

        // Persist DNS verification status
        if ($dns['found'] && (!isset($dns['matched']) || $dns['matched'])) {
            Simple_SMTP_DKIM_Helpers::update_option_no_autoload('simple_smtp_dkim_dns_verified', 1);
        }

        wp_send_json_success(array('message' => $msg, 'key_info' => isset($validation['info']) ? $validation['info'] : '', 'dns_check' => $dns));
    }

    /* ------------------------------------------------------------------
       Generate DKIM Keys
       ------------------------------------------------------------------ */

    public static function generate_dkim_keys() {
        self::require_admin();
        check_ajax_referer('simple_smtp_dkim_generate_dkim', 'nonce');

        $domain   = isset($_POST['domain']) ? sanitize_text_field(wp_unslash($_POST['domain'])) : '';
        $selector = isset($_POST['selector']) ? sanitize_text_field(wp_unslash($_POST['selector'])) : '';

        if (empty($domain))   wp_send_json_error(array('message' => __('Domain is required.', 'simple-smtp-dkim')));
        if (empty($selector)) wp_send_json_error(array('message' => __('Selector is required.', 'simple-smtp-dkim')));
        if (!function_exists('openssl_pkey_new')) wp_send_json_error(array('message' => __('OpenSSL unavailable.', 'simple-smtp-dkim')));

        try {
            $res = openssl_pkey_new(array('private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA));
            if ($res === false) throw new Exception('Key generation failed: ' . openssl_error_string());

            openssl_pkey_export($res, $priv);
            $details = openssl_pkey_get_details($res);
            $pub_clean = str_replace(array('-----BEGIN PUBLIC KEY-----','-----END PUBLIC KEY-----',"\r","\n",' '), '', $details['key']);

            $dns_value = 'v=DKIM1; k=rsa; p=' . $pub_clean;

            Simple_SMTP_DKIM_Encryption::save_encrypted_option('simple_smtp_dkim_dkim_private_key', $priv);
            Simple_SMTP_DKIM_Helpers::update_option_no_autoload('simple_smtp_dkim_dkim_public_key', $pub_clean);
            Simple_SMTP_DKIM_Helpers::update_option_no_autoload('simple_smtp_dkim_dkim_storage_method', 'database');
            Simple_SMTP_DKIM_Helpers::update_option_no_autoload('simple_smtp_dkim_dns_verified', 0);

            wp_send_json_success(array(
                'message'          => __('DKIM keys generated!', 'simple-smtp-dkim'),
                'dns_record_name'  => $selector . '._domainkey.' . $domain,
                'dns_record_value' => $dns_value,
                'private_key_saved'=> true,
            ));
        } catch (Exception $e) {
            if (get_option('simple_smtp_dkim_debug_mode', false)) {
                error_log('SMTP Config: DKIM keygen failed — ' . $e->getMessage());
            }
            wp_send_json_error(array('message' => __('Key generation failed: ', 'simple-smtp-dkim') . $e->getMessage()));
        }
    }

    /* ------------------------------------------------------------------
       Delete All Logs
       ------------------------------------------------------------------ */

    public static function delete_all_logs() {
        self::require_admin();
        check_ajax_referer('simple_smtp_dkim_delete_logs', 'nonce');

        $result = Simple_SMTP_DKIM_Logger::delete_all_logs();
        $result !== false
            ? wp_send_json_success(array('message' => __('All logs deleted.', 'simple-smtp-dkim')))
            : wp_send_json_error(array('message' => __('Failed to delete logs.', 'simple-smtp-dkim')));
    }

    /* ------------------------------------------------------------------
       View Email
       ------------------------------------------------------------------ */

    public static function view_email() {
        self::require_admin();
        check_ajax_referer('simple_smtp_dkim_view_email', 'nonce');

        $id = isset($_POST['log_id']) ? intval($_POST['log_id']) : 0;
        if (!$id) wp_send_json_error(array('message' => __('Invalid log ID.', 'simple-smtp-dkim')));

        $log = Simple_SMTP_DKIM_Logger::get_log($id);
        if (!$log) wp_send_json_error(array('message' => __('Log not found.', 'simple-smtp-dkim')));

        // Decrypt body if encrypted
        $body = $log['email_body'];
        if (!empty($body)) {
            $dec = Simple_SMTP_DKIM_Encryption::decrypt($body);
            if ($dec !== false && !empty($dec)) $body = $dec;
        }

        wp_send_json_success(array(
            'to_email'      => esc_html($log['to_email']),
            'from_email'    => esc_html($log['from_email']),
            'subject'       => esc_html($log['subject']),
            'timestamp'     => esc_html($log['timestamp']),
            'email_body'    => wp_kses_post($body),
            'email_headers' => esc_html($log['email_headers']),
        ));
    }
}
