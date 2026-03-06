<?php
/**
 * SMTP Mailer configuration
 *
 * This class hooks into WordPress PHPMailer and applies SMTP and DKIM settings.
 *
 * @package SMTP_Config_Manager
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Core mail transport layer.
 *  *
 *  * Hooks into wp_mail() to configure PHPMailer with SMTP settings,
 *  * DKIM signing, From address overrides, and email logging.
 *  * Provides test connection and test email functionality.
 *  *
 *  * @since 1.0.0
 */
class Simple_SMTP_DKIM_Mailer {
    
    /**
     * Store current email data for logging
     */
    private static $current_email_data = null;

    /**
     * Temporary SMTP settings used by send_test_email().
     *
     * When non-null, configure_phpmailer() uses these values instead of the
     * database, so no options are ever written during a test-send.
     *
     * @var array|null
     */
    private static $test_settings = null;
    
    /**
     * Initialize the mailer
     */
    public static function init() {
        // Hook into PHPMailer
        add_action('phpmailer_init', array(__CLASS__, 'configure_phpmailer'), 10, 1);
        
        // Capture wp_mail data before sending
        add_filter('wp_mail', array(__CLASS__, 'capture_email_data'), 1);
        
        // Hook for logging email results
        add_action('wp_mail_succeeded', array(__CLASS__, 'log_email_success'), 10, 1);
        add_action('wp_mail_failed', array(__CLASS__, 'log_email_failure'), 10, 1);
        
        // Fallback logging using PHPMailer action
        add_action('phpmailer_init', array(__CLASS__, 'log_from_phpmailer'), 999, 1);
        
        // From email/name filters — priority determined at send time, not page load
        // These only fire during wp_mail(), so no perf impact on regular page loads
        add_filter('wp_mail_from', array(__CLASS__, 'filter_from_email'), 1);
        add_filter('wp_mail_from_name', array(__CLASS__, 'filter_from_name'), 1);
    }
    
    /**
     * Capture email data from wp_mail filter
     *
     * @param array $args wp_mail arguments
     * @return array Unchanged arguments
     */
    public static function capture_email_data($args) {
        self::$current_email_data = $args;
        return $args;
    }
    
    /**
     * Configure PHPMailer with SMTP settings
     *
     * @param PHPMailer $phpmailer The PHPMailer instance
     */
    public static function configure_phpmailer($phpmailer) {
        // When a test-send is in progress, use the supplied in-memory settings
        // instead of reading from the database.  This avoids any DB writes.
        if (self::$test_settings !== null) {
            $s = self::$test_settings;
            try {
                $phpmailer->isSMTP();
                $phpmailer->Host       = $s['host'];
                $phpmailer->Port       = $s['port'];
                $phpmailer->SMTPSecure = $s['secure'];

                if (!empty($s['auth'])) {
                    $phpmailer->SMTPAuth = true;
                    $phpmailer->Username = $s['username'];
                    $phpmailer->Password = $s['password'];
                } else {
                    $phpmailer->SMTPAuth = false;
                }

                $phpmailer->Timeout = 30;
                $phpmailer->CharSet = 'UTF-8';

                if (get_option('simple_smtp_dkim_dkim_enabled', false) && get_option('simple_smtp_dkim_dns_verified', false)) {
                    self::configure_dkim($phpmailer);
                }
            } catch (Exception $e) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('SMTP Config Manager: PHPMailer test-configuration error - ' . $e->getMessage());
            }
            return;
        }

        // Check if SMTP is enabled
        if (!get_option('simple_smtp_dkim_enabled', false)) {
            return;
        }
        
        try {
            // Set mailer to use SMTP
            $phpmailer->isSMTP();
            
            // SMTP configuration
            $phpmailer->Host = get_option('simple_smtp_dkim_host', '');
            $phpmailer->Port = get_option('simple_smtp_dkim_port', 587);
            $phpmailer->SMTPSecure = get_option('simple_smtp_dkim_secure', 'tls');
            
            // SMTP authentication
            if (get_option('simple_smtp_dkim_auth', true)) {
                $phpmailer->SMTPAuth = true;
                $phpmailer->Username = get_option('simple_smtp_dkim_username', '');
                $phpmailer->Password = Simple_SMTP_DKIM_Encryption::get_decrypted_option('simple_smtp_dkim_password', '');
            } else {
                $phpmailer->SMTPAuth = false;
            }
            
            // Debug mode
            if (get_option('simple_smtp_dkim_debug_mode', false)) {
                $phpmailer->SMTPDebug = 2;
                $phpmailer->Debugoutput = function($str, $level) {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                    error_log("SMTP Debug [$level]: $str");
                };
            }
            
            // Set From email and name (only if not already set or if force is enabled)
            $from_email = get_option('simple_smtp_dkim_from_email', '');
            $from_name = get_option('simple_smtp_dkim_from_name', '');
            $force_from = get_option('simple_smtp_dkim_force_from', false);
            
            if ($force_from || empty($phpmailer->From)) {
                if (!empty($from_email)) {
                    $phpmailer->From = $from_email;
                }
            }
            
            if ($force_from || empty($phpmailer->FromName)) {
                if (!empty($from_name)) {
                    $phpmailer->FromName = $from_name;
                }
            }
            
            // Configure DKIM if enabled
            if (get_option('simple_smtp_dkim_dkim_enabled', false) && get_option('simple_smtp_dkim_dns_verified', false)) {
                self::configure_dkim($phpmailer);
            }
            
            // Set timeout
            $phpmailer->Timeout = 30;
            
            // Set encoding
            $phpmailer->CharSet = 'UTF-8';
            
        } catch (Exception $e) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log('SMTP Config Manager: PHPMailer configuration error - ' . $e->getMessage());
        }
    }
    
    /**
     * Configure DKIM settings
     *
     * @param PHPMailer $phpmailer The PHPMailer instance
     */
    private static function configure_dkim($phpmailer) {
        try {
            // Get DKIM configuration
            $dkim_domain = get_option('simple_smtp_dkim_dkim_domain', '');
            $dkim_selector = get_option('simple_smtp_dkim_dkim_selector', '');
            $dkim_passphrase = Simple_SMTP_DKIM_Encryption::get_decrypted_option('simple_smtp_dkim_dkim_passphrase', '');
            $storage_method = get_option('simple_smtp_dkim_dkim_storage_method', 'database');
            
            // Validate required fields
            if (empty($dkim_domain) || empty($dkim_selector)) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('SMTP Config Manager: DKIM domain or selector not configured');
                return;
            }
            
            // Get private key based on storage method
            $private_key = '';
            
            if ($storage_method === 'file') {
                // Load from file path
                $file_path = get_option('simple_smtp_dkim_dkim_file_path', '');
                
                if (empty($file_path)) {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                    error_log('SMTP Config Manager: DKIM private key file path not configured');
                    return;
                }
                
                // Resolve real path to prevent path traversal
                $real_path = realpath($file_path);
                if ($real_path === false || !is_readable($real_path)) {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                    error_log('SMTP Config Manager: DKIM private key file not found or not readable');
                    return;
                }
                
                // Validate file extension
                $ext = strtolower(pathinfo($real_path, PATHINFO_EXTENSION));
                if (!in_array($ext, array('pem', 'private', 'key'), true)) {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                    error_log('SMTP Config Manager: DKIM private key file has invalid extension');
                    return;
                }
                
                $private_key = file_get_contents($real_path);
                
                if ($private_key === false) {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                    error_log('SMTP Config Manager: Failed to read DKIM private key file');
                    return;
                }
                
            } else {
                // Load from database (encrypted)
                $private_key = Simple_SMTP_DKIM_Encryption::get_decrypted_option('simple_smtp_dkim_dkim_private_key', '');
                
                if (empty($private_key)) {
                    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                    error_log('SMTP Config Manager: DKIM private key not found in database');
                    return;
                }
            }
            
            // Apply DKIM configuration to PHPMailer
            $phpmailer->DKIM_domain = $dkim_domain;
            $phpmailer->DKIM_selector = $dkim_selector;
            $phpmailer->DKIM_passphrase = $dkim_passphrase;
            $phpmailer->DKIM_private = $private_key;
            $phpmailer->DKIM_identity = $phpmailer->From;
            
            // Optional: Set additional DKIM parameters
            // $phpmailer->DKIM_copyHeaderFields = false;
            
        } catch (Exception $e) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log('SMTP Config Manager: DKIM configuration error - ' . $e->getMessage());
        }
    }
    
    /**
     * Log email from PHPMailer (fallback logging method)
     *
     * @param PHPMailer $phpmailer The PHPMailer instance
     */
    public static function log_from_phpmailer($phpmailer) {
        // Only log if logging is enabled
        if (!get_option('simple_smtp_dkim_logging_enabled', false)) {
            return;
        }
        
        // Skip if wp_mail_succeeded hook exists (WP 5.9+)
        if (has_action('wp_mail_succeeded', array(__CLASS__, 'log_email_success'))) {
            return;
        }
        
        // Get recipients
        $to_emails = array();
        foreach ($phpmailer->getToAddresses() as $address) {
            $to_emails[] = $address[0];
        }
        
        // Get email body
        $email_body = !empty($phpmailer->Body) ? $phpmailer->Body : $phpmailer->AltBody;
        
        // Prepare log data
        $log_data = array(
            'to_email' => implode(', ', $to_emails),
            'from_email' => $phpmailer->From,
            'subject' => $phpmailer->Subject,
            'email_body' => $email_body,
            'email_headers' => '', // Headers not easily accessible from PHPMailer
            'status' => 'success', // Assume success if phpmailer_init ran
            'error_message' => null,
            'dkim_signed' => (get_option('simple_smtp_dkim_dkim_enabled', false) && get_option('simple_smtp_dkim_dns_verified', false)),
        );
        
        // Log on shutdown to ensure email was actually sent
        add_action('shutdown', function() use ($log_data) {
            static $logged = false;
            if (!$logged) {
                Simple_SMTP_DKIM_Logger::log_email($log_data);
                $logged = true;
            }
        }, 999);
    }
    
    /**
     * Filter From email address
     *
     * @param string $from_email Original from email
     * @return string Filtered from email
     */
    /**
     * Cached from-address settings (loaded once per request, only during wp_mail)
     */
    private static $from_settings = null;

    /**
     * Load and cache the From email, name, and force settings.
     *  *
     *  * Uses a static cache to avoid repeated get_option() calls
     *  * within the same request.
     *  *
     *  * @since 1.0.0
     *  *
     *  * @return array Associative array with from_email, from_name, force_from keys.
     */
    private static function get_from_settings() {
        if (self::$from_settings === null) {
            self::$from_settings = array(
                'email'      => get_option('simple_smtp_dkim_from_email', ''),
                'name'       => get_option('simple_smtp_dkim_from_name', ''),
                'force_from' => (bool) get_option('simple_smtp_dkim_force_from', false),
            );
        }
        return self::$from_settings;
    }

    /**
     * Filter the From email address for outgoing emails.
     *  *
     *  * @since 1.0.0
     *  *
     *  * @param string $from_email The current From email.
     *  * @return string The filtered From email.
     */
    public static function filter_from_email($from_email) {
        $s = self::get_from_settings();
        
        if ($s['force_from'] && !empty($s['email'])) {
            return $s['email'];
        }
        
        if (empty($from_email) || $from_email === 'wordpress@' . (isset($_SERVER['SERVER_NAME']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_NAME'])) : 'localhost')) {
            if (!empty($s['email'])) {
                return $s['email'];
            }
        }
        
        return $from_email;
    }
    
    /**
     * Filter From name
     *
     * @param string $from_name Original from name
     * @return string Filtered from name
     */
    public static function filter_from_name($from_name) {
        $s = self::get_from_settings();
        
        if ($s['force_from'] && !empty($s['name'])) {
            return $s['name'];
        }
        
        if (empty($from_name) || $from_name === 'WordPress') {
            if (!empty($s['name'])) {
                return $s['name'];
            }
        }
        
        return $from_name;
    }
    
    /**
     * Log successful email
     *
     * @param array $mail_data Email data from wp_mail
     */
    public static function log_email_success($mail_data) {
        if (!get_option('simple_smtp_dkim_logging_enabled', false)) {
            return;
        }
        
        $to_email = '';
        if (is_array($mail_data['to'])) {
            $to_email = implode(', ', $mail_data['to']);
        } else {
            $to_email = $mail_data['to'];
        }
        
        $log_data = array(
            'to_email' => $to_email,
            'from_email' => get_option('simple_smtp_dkim_from_email', ''),
            'subject' => isset($mail_data['subject']) ? $mail_data['subject'] : '',
            'email_body' => isset($mail_data['message']) ? $mail_data['message'] : '',
            'email_headers' => isset($mail_data['headers']) ? $mail_data['headers'] : '',
            'status' => 'success',
            'error_message' => null,
            'dkim_signed' => (get_option('simple_smtp_dkim_dkim_enabled', false) && get_option('simple_smtp_dkim_dns_verified', false)),
        );
        
        Simple_SMTP_DKIM_Logger::log_email($log_data);
    }
    
    /**
     * Log failed email
     *
     * @param WP_Error $error Error object
     */
    public static function log_email_failure($error) {
        if (!get_option('simple_smtp_dkim_logging_enabled', false)) {
            return;
        }
        
        $error_data = $error->get_error_data();
        
        $to_email = '';
        if (isset($error_data['to']) && is_array($error_data['to'])) {
            $to_email = implode(', ', $error_data['to']);
        } elseif (isset($error_data['to'])) {
            $to_email = $error_data['to'];
        }
        
        $log_data = array(
            'to_email' => $to_email,
            'from_email' => get_option('simple_smtp_dkim_from_email', ''),
            'subject' => isset($error_data['subject']) ? $error_data['subject'] : '',
            'email_body' => isset($error_data['message']) ? $error_data['message'] : '',
            'email_headers' => isset($error_data['headers']) ? $error_data['headers'] : '',
            'status' => 'failed',
            'error_message' => $error->get_error_message(),
            'dkim_signed' => (get_option('simple_smtp_dkim_dkim_enabled', false) && get_option('simple_smtp_dkim_dns_verified', false)),
        );
        
        Simple_SMTP_DKIM_Logger::log_email($log_data);
    }
    
    /**
     * Test SMTP connection
     *
     * @param array $settings SMTP settings to test
     * @return array Result with 'success' boolean and 'message' string
     */
    public static function test_connection($settings) {
        require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
        require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
        require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
        
        $phpmailer = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Enable debug output
            $debug_output = '';
            $phpmailer->SMTPDebug = 2;
            $phpmailer->Debugoutput = function($str, $level) use (&$debug_output) {
                $debug_output .= "[$level] $str\n";
            };
            
            // Configure SMTP
            $phpmailer->isSMTP();
            $phpmailer->Host = $settings['host'];
            $phpmailer->Port = $settings['port'];
            $phpmailer->SMTPSecure = $settings['secure'];
            
            if ($settings['auth']) {
                $phpmailer->SMTPAuth = true;
                $phpmailer->Username = $settings['username'];
                $phpmailer->Password = $settings['password'];
            }
            
            // Set timeout
            $phpmailer->Timeout = 10;
            
            // Test connection
            $phpmailer->smtpConnect();
            $phpmailer->smtpClose();
            
            // Check SPF record
            $from_email = get_option('simple_smtp_dkim_from_email', get_option('admin_email'));
            $from_domain = substr(strrchr($from_email, "@"), 1);
            $spf_check = self::check_spf_record($from_domain, $settings['host']);
            
            return array(
                'success' => true,
                'message' => __('SMTP connection successful!', 'simple-smtp-dkim'),
                'debug' => $debug_output,
                'spf_check' => $spf_check
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage(),
                'debug' => $debug_output
            );
        }
    }
    
    /**
     * Send a test email
     *
     * @param string $to_email Recipient email address
     * @param array $settings Optional settings to use (if null, uses saved settings)
     * @return array Result with 'success' boolean and 'message' string
     */
    public static function send_test_email($to_email, $settings = null) {
        // When custom settings are supplied, store them in the static override so
        // configure_phpmailer() picks them up without touching the database.
        if ($settings !== null) {
            self::$test_settings = $settings;
        }

        // Gather email details
        $site_name = get_option('blogname');
        $site_url = get_option('siteurl');
        $from_name = get_option('simple_smtp_dkim_from_name', $site_name);
        $from_email = get_option('simple_smtp_dkim_from_email', get_option('admin_email'));
        $smtp_host = get_option('simple_smtp_dkim_host');
        $smtp_port = get_option('simple_smtp_dkim_port');
        $encryption = strtoupper(get_option('simple_smtp_dkim_secure', 'tls'));
        $dkim_enabled = get_option('simple_smtp_dkim_dkim_enabled', false);
        
        $utc_timestamp = gmdate('Y-m-d H:i:s') . ' UTC';
        $message_id = '<' . uniqid('smtp-test-', true) . '@' . wp_parse_url($site_url, PHP_URL_HOST) . '>';
        $plugin_version = SIMPLE_SMTP_DKIM_VERSION;
        $from_domain = substr(strrchr($from_email, "@"), 1);
        $environment = defined('WP_ENV') ? WP_ENV : (WP_DEBUG ? 'development' : 'production');
        $request_id = uniqid('req-', true);
        
        // Build HTML email
        $html_message = self::get_test_email_html_template($site_name, $site_url, $from_name, $from_email, 
            $to_email, $smtp_host, $smtp_port, $encryption, $dkim_enabled, $utc_timestamp, $message_id, 
            $plugin_version, $from_domain, $environment, $request_id);
        
        // Prepare test email
        $subject = __('SMTP Config Manager - Test Email', 'simple-smtp-dkim');
        
        // Set content type to HTML
        $html_content_type = function() { return 'text/html'; };
        add_filter('wp_mail_content_type', $html_content_type);
        
        // Send email
        $result = wp_mail($to_email, $subject, $html_message);
        
        // Remove the content type filter (must use same reference)
        remove_filter('wp_mail_content_type', $html_content_type);

        // Clear the in-memory override so normal mail delivery is unaffected.
        self::$test_settings = null;

        if ($result) {
            return array(
                'success' => true,
                /* translators: %s: recipient email address */
                'message' => sprintf(__('Test email sent successfully to %s', 'simple-smtp-dkim'), $to_email)
            );
        } else {
            global $phpmailer;
            $error_message = __('Failed to send test email.', 'simple-smtp-dkim');
            
            if (isset($phpmailer->ErrorInfo) && !empty($phpmailer->ErrorInfo)) {
                $error_message .= ' ' . $phpmailer->ErrorInfo;
            }
            
            return array(
                'success' => false,
                'message' => $error_message
            );
        }
    }
    
    /**
     * Get HTML template for test email
     */
    private static function get_test_email_html_template($site_name, $site_url, $from_name, $from_email, 
        $to_email, $smtp_host, $smtp_port, $encryption, $dkim_enabled, $utc_timestamp, $message_id, 
        $plugin_version, $from_domain, $environment, $request_id) {
        
        $auth_method = get_option('simple_smtp_dkim_auth') ? 'SMTP AUTH (username/password)' : 'No authentication';
        $tls_version = 'TLS 1.2+';
        
        $dkim_status = $dkim_enabled ? 
            '<tr><td style="padding:6px 0;color:#5b6675;">' . __('DKIM signing', 'simple-smtp-dkim') . '</td><td style="padding:6px 0;"><strong>✅ ' . __('Enabled', 'simple-smtp-dkim') . '</strong></td></tr>' : 
            '<tr><td style="padding:6px 0;color:#5b6675;">' . __('DKIM signing', 'simple-smtp-dkim') . '</td><td style="padding:6px 0;">❌ ' . __('Disabled', 'simple-smtp-dkim') . '</td></tr>';
        
        return '<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="x-apple-disable-message-reformatting">
  <title>' . esc_html__('SMTP Test Email', 'simple-smtp-dkim') . '</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f8;">
  <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
    ' . esc_html__('This is a delivery and formatting test message generated by your WordPress SMTP plugin.', 'simple-smtp-dkim') . '
  </div>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f4f6f8;">
    <tr>
      <td align="center" style="padding:24px 12px;">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:100%;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e6e9ee;">
          <tr>
            <td style="padding:20px 24px;background:#262829;color:#ffffff;font-family:Arial,Helvetica,sans-serif;">
              <div style="font-size:16px;line-height:22px;font-weight:700;">
                ' . esc_html__('SMTP Test Email', 'simple-smtp-dkim') . '
              </div>
              <div style="font-size:13px;line-height:18px;opacity:0.95;margin-top:4px;">
                ' . esc_html__('Generated by your WordPress SMTP plugin to verify authentication and delivery.', 'simple-smtp-dkim') . '
              </div>
            </td>
          </tr>

          <tr>
            <td style="padding:24px;font-family:Arial,Helvetica,sans-serif;color:#1b1f24;">
              <div style="font-size:16px;line-height:24px;margin:0 0 14px;">
                ' . esc_html__('Hello,', 'simple-smtp-dkim') . '
              </div>

              <div style="font-size:14px;line-height:22px;margin:0 0 14px;">
                ' . sprintf(
                    /* translators: %s: content type (HTML/text) */
                    esc_html__('This message was sent as a %s to confirm that your SMTP configuration is working correctly. It\'s safe to ignore once you\'ve verified delivery. No action is required unless you expected this email and did not receive it.', 'simple-smtp-dkim'),
                    '<strong>' . esc_html__('test email', 'simple-smtp-dkim') . '</strong>'
                ) . '
              </div>

              <div style="font-size:14px;line-height:22px;margin:0 0 14px;">
                ' . esc_html__('If you\'re reviewing this for troubleshooting, the details below can help support teams confirm the sending path, authentication method, and basic message integrity.', 'simple-smtp-dkim') . '
              </div>

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                     style="margin:16px 0 18px;border:1px solid #e6e9ee;border-radius:10px;">
                <tr>
                  <td style="padding:14px 16px;background:#f8fafc;font-family:Arial,Helvetica,sans-serif;">
                    <div style="font-size:13px;line-height:18px;font-weight:700;color:#2b3440;margin-bottom:10px;">
                      ' . esc_html__('Test details', 'simple-smtp-dkim') . '
                    </div>

                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
                           style="font-size:13px;line-height:18px;color:#2b3440;">
                      <tr>
                        <td style="padding:6px 0;width:170px;color:#5b6675;">' . esc_html__('Site', 'simple-smtp-dkim') . '</td>
                        <td style="padding:6px 0;"><strong>' . esc_html($site_name) . '</strong> (' . esc_html($site_url) . ')</td>
                      </tr>
                      <tr>
                        <td style="padding:6px 0;color:#5b6675;">' . esc_html__('Date (UTC)', 'simple-smtp-dkim') . '</td>
                        <td style="padding:6px 0;">' . esc_html($utc_timestamp) . '</td>
                      </tr>
                      <tr>
                        <td style="padding:6px 0;color:#5b6675;">' . esc_html__('From', 'simple-smtp-dkim') . '</td>
                        <td style="padding:6px 0;">' . esc_html($from_name) . ' &lt;' . esc_html($from_email) . '&gt;</td>
                      </tr>
                      <tr>
                        <td style="padding:6px 0;color:#5b6675;">' . esc_html__('To', 'simple-smtp-dkim') . '</td>
                        <td style="padding:6px 0;">' . esc_html($to_email) . '</td>
                      </tr>
                      <tr>
                        <td style="padding:6px 0;color:#5b6675;">' . esc_html__('SMTP Host', 'simple-smtp-dkim') . '</td>
                        <td style="padding:6px 0;">' . esc_html($smtp_host) . ':' . esc_html($smtp_port) . '</td>
                      </tr>
                      <tr>
                        <td style="padding:6px 0;color:#5b6675;">' . esc_html__('Encryption', 'simple-smtp-dkim') . '</td>
                        <td style="padding:6px 0;">' . esc_html($encryption) . ' (' . sprintf(
                            /* translators: %s: TLS version */
                            esc_html__('TLS version negotiated: %s', 'simple-smtp-dkim'), esc_html($tls_version)) . ')</td>
                      </tr>
                      <tr>
                        <td style="padding:6px 0;color:#5b6675;">' . esc_html__('Auth method', 'simple-smtp-dkim') . '</td>
                        <td style="padding:6px 0;">' . esc_html($auth_method) . '</td>
                      </tr>
                      ' . $dkim_status . '
                      <tr>
                        <td style="padding:6px 0;color:#5b6675;">' . esc_html__('Message ID', 'simple-smtp-dkim') . '</td>
                        <td style="padding:6px 0;font-family:monospace;font-size:11px;">' . esc_html($message_id) . '</td>
                      </tr>
                      <tr>
                        <td style="padding:6px 0;color:#5b6675;">' . esc_html__('Plugin version', 'simple-smtp-dkim') . '</td>
                        <td style="padding:6px 0;">' . esc_html($plugin_version) . '</td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>

              <div style="font-size:14px;line-height:22px;margin:0 0 14px;">
                ' . esc_html__('Notes:', 'simple-smtp-dkim') . '
              </div>
              <ul style="margin:0 0 18px;padding-left:20px;font-size:14px;line-height:22px;color:#1b1f24;">
                <li style="margin-bottom:8px;">
                  ' . sprintf(
                      /* translators: %s: domain name */
                      esc_html__('If this email landed in spam/junk, check SPF/DKIM/DMARC alignment for %s, and confirm that the "From" address matches your authorized sending identity.', 'simple-smtp-dkim'),
                      '<strong>' . esc_html($from_domain) . '</strong>'
                  ) . '
                </li>
                <li style="margin-bottom:8px;">
                  ' . esc_html__('Some mail providers evaluate reputation and content together. A successful send confirms SMTP delivery, but inbox placement can still vary.', 'simple-smtp-dkim') . '
                </li>
                <li>
                  ' . sprintf(
                      /* translators: %s: message ID */
                      esc_html__('If you contact support, include the %s and the timestamp above.', 'simple-smtp-dkim'),
                      '<strong>' . esc_html__('Message ID', 'simple-smtp-dkim') . '</strong>'
                  ) . '
                </li>
              </ul>

              <div style="font-size:14px;line-height:22px;margin:0;">
                ' . esc_html__('Regards,', 'simple-smtp-dkim') . '<br>
                <strong>' . esc_html($site_name) . '</strong> ' . esc_html__('automated mailer', 'simple-smtp-dkim') . '
              </div>
            </td>
          </tr>

          <tr>
            <td style="padding:18px 24px;background:#ffffff;border-top:1px solid #e6e9ee;font-family:Arial,Helvetica,sans-serif;color:#5b6675;">
              <div style="font-size:12px;line-height:18px;margin:0 0 8px;">
                ' . esc_html__('This is an automated test message generated by a WordPress SMTP configuration tool.', 'simple-smtp-dkim') . '
              </div>
              <div style="font-size:12px;line-height:18px;margin:0;">
                ' . sprintf(
                    /* translators: %1$s: site URL, %2$s: environment name, %3$s: request ID */
                    esc_html__('Site: %1$s • Environment: %2$s • Request ID: %3$s', 'simple-smtp-dkim'),
                    esc_html($site_url),
                    esc_html($environment),
                    esc_html($request_id)
                ) . '
              </div>
              <div style="font-size:12px;line-height:18px;margin:10px 0 0;">
                ' . esc_html__('If you did not request this test, you can safely ignore this email.', 'simple-smtp-dkim') . '
              </div>
            </td>
          </tr>
        </table>

        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:100%;margin-top:10px;">
          <tr>
            <td style="padding:0 6px;font-family:Arial,Helvetica,sans-serif;color:#8a94a3;font-size:11px;line-height:16px;text-align:center;">
              ' . esc_html__('Generated for diagnostics only. No marketing content, tracking pixels, or promotional links are included.', 'simple-smtp-dkim') . '
            </td>
          </tr>
        </table>

      </td>
    </tr>
  </table>
</body>
</html>';
    }
    
    /**
     * Check SPF record for domain
     *
     * @param string $domain The domain to check
     * @param string $smtp_host The SMTP host being used
     * @return array SPF check result
     */
    private static function check_spf_record($domain, $smtp_host) {
        if (empty($domain)) {
            return array(
                'found' => false,
                'message' => __('No domain to check', 'simple-smtp-dkim')
            );
        }
        
        // Get TXT records for domain
        $records = @dns_get_record($domain, DNS_TXT);
        
        if ($records === false || empty($records)) {
            return array(
                'found' => false,
                /* translators: %s: domain name */
                'message' => sprintf(__('No DNS TXT records found for %s', 'simple-smtp-dkim'), $domain)
            );
        }
        
        // Look for SPF record
        $spf_record = '';
        foreach ($records as $record) {
            if (isset($record['txt']) && (strpos($record['txt'], 'v=spf1') === 0)) {
                $spf_record = $record['txt'];
                break;
            }
        }
        
        if (empty($spf_record)) {
            return array(
                'found' => false,
                /* translators: %s: domain name */
                'message' => sprintf(__('⚠️ No SPF record found for %s. Consider adding one to improve email deliverability.', 'simple-smtp-dkim'), $domain)
            );
        }
        
        // Try to resolve SMTP host to IP
        $smtp_ips = array();
        $ip_addr = @gethostbyname($smtp_host);
        if ($ip_addr !== $smtp_host) {
            $smtp_ips[] = $ip_addr;
        }
        
        // Check if SPF record includes the SMTP host or its IP
        $includes_host = stripos($spf_record, $smtp_host) !== false;
        $includes_ip = false;
        
        foreach ($smtp_ips as $ip) {
            if (strpos($spf_record, $ip) !== false) {
                $includes_ip = true;
                break;
            }
        }
        
        // Check for common SPF mechanisms that might allow the host
        $has_include_all = stripos($spf_record, 'include:') !== false;
        $has_a_mechanism = stripos($spf_record, ' a ') !== false || stripos($spf_record, ' a:') !== false;
        $has_mx_mechanism = stripos($spf_record, ' mx') !== false;
        $has_ip4 = stripos($spf_record, 'ip4:') !== false;
        $has_ip6 = stripos($spf_record, 'ip6:') !== false;
        
        if ($includes_host || $includes_ip) {
            return array(
                'found' => true,
                'authorized' => true,
                /* translators: %s: domain name */
                'message' => sprintf(__('✅ SPF record found for %s and appears to authorize your SMTP host.', 'simple-smtp-dkim'), $domain),
                'record' => substr($spf_record, 0, 200)
            );
        } elseif ($has_include_all || $has_a_mechanism || $has_mx_mechanism || $has_ip4 || $has_ip6) {
            return array(
                'found' => true,
                'authorized' => 'maybe',
                /* translators: %1$s: domain name, %2$s: SPF record content */
                'message' => sprintf(__('SPF record found for %1$s. The SMTP host may be authorized through include/a/mx mechanisms. Record: %2$s', 'simple-smtp-dkim'), $domain, substr($spf_record, 0, 100) . '...'),
                'record' => substr($spf_record, 0, 200)
            );
        } else {
            return array(
                'found' => true,
                'authorized' => false,
                /* translators: %1$s: domain name, %2$s: SMTP host */
                'message' => sprintf(__('⚠️ SPF record found for %1$s but may not authorize your SMTP host (%2$s). Check your SPF record.', 'simple-smtp-dkim'), $domain, $smtp_host),
                'record' => substr($spf_record, 0, 200)
            );
        }
    }
}
