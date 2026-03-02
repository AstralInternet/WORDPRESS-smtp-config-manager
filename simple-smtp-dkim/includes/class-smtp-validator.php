<?php
/**
 * Validation & status checks for SMTP and DKIM
 *
 * Pure logic — no AJAX, no HTTP, no rendering.
 *
 * @package Simple_SMTP_DKIM
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Configuration status checks and DKIM validation.
 *  *
 *  * Provides connection status banners, DKIM key validation,
 *  * and DNS record verification for the admin interface.
 *  *
 *  * @since 1.0.0
 */
class Simple_SMTP_DKIM_Validator {

    /**
     * Get SMTP connection status
     *
     * @return array {status, message, class}
     */
    public static function get_connection_status() {
        if (!get_option('simple_smtp_dkim_enabled', false)) {
            return array(
                'status'  => 'disabled',
                'message' => __('SMTP is currently disabled.', 'simple-smtp-dkim'),
                'class'   => 'warning',
            );
        }

        $host     = get_option('simple_smtp_dkim_host', '');
        $username = get_option('simple_smtp_dkim_username', '');
        $password = Simple_SMTP_DKIM_Encryption::get_decrypted_option('simple_smtp_dkim_password', '');

        if (empty($host)) {
            return array('status' => 'incomplete', 'message' => __('SMTP is enabled but not fully configured. Please enter your SMTP host.', 'simple-smtp-dkim'), 'class' => 'error');
        }

        if (get_option('simple_smtp_dkim_auth', true) && (empty($username) || empty($password))) {
            return array('status' => 'incomplete', 'message' => __('SMTP is enabled but authentication credentials are missing.', 'simple-smtp-dkim'), 'class' => 'error');
        }

        return array('status' => 'configured', 'message' => __('SMTP is enabled and configured. Send a test email to verify.', 'simple-smtp-dkim'), 'class' => 'success');
    }

    /**
     * Get DKIM status
     *
     * @return array {status, message, class}
     */
    public static function get_dkim_status() {
        if (!get_option('simple_smtp_dkim_dkim_enabled', false)) {
            return array('status' => 'disabled', 'message' => __('DKIM is currently disabled.', 'simple-smtp-dkim'), 'class' => 'default');
        }

        $domain   = get_option('simple_smtp_dkim_dkim_domain', '');
        $selector = get_option('simple_smtp_dkim_dkim_selector', '');

        if (empty($domain) || empty($selector)) {
            return array('status' => 'incomplete', 'message' => __('DKIM is enabled but domain or selector is missing.', 'simple-smtp-dkim'), 'class' => 'error');
        }

        $storage = get_option('simple_smtp_dkim_dkim_storage_method', 'database');
        $has_key = false;
        if ($storage === 'file') {
            $fp = get_option('simple_smtp_dkim_dkim_file_path', '');
            $has_key = !empty($fp) && file_exists($fp);
        } else {
            $has_key = !empty(get_option('simple_smtp_dkim_dkim_private_key', ''));
        }

        if (!$has_key) {
            return array('status' => 'incomplete', 'message' => __('DKIM is enabled but private key is missing.', 'simple-smtp-dkim'), 'class' => 'error');
        }

        return array('status' => 'configured', 'message' => __('DKIM is enabled and configured.', 'simple-smtp-dkim'), 'class' => 'success');
    }

    /**
     * Validate DKIM private key format
     *
     * @param  string $private_key PEM content
     * @return array  {valid, message, info?, debug?}
     */
    public static function validate_dkim_key($private_key) {
        if (empty($private_key)) {
            return array('valid' => false, 'message' => __('Private key is empty.', 'simple-smtp-dkim'), 'debug' => 'Empty');
        }

        if (get_option('simple_smtp_dkim_debug_mode', false)) {
            error_log('SMTP Config: Validating DKIM key (' . strlen($private_key) . ' bytes)');
        }

        if (strpos($private_key, '-----BEGIN') === false || strpos($private_key, 'PRIVATE KEY-----') === false) {
            return array('valid' => false, 'message' => __('Private key is not in valid PEM format.', 'simple-smtp-dkim'), 'debug' => 'Missing PEM headers');
        }

        if (function_exists('openssl_pkey_get_private')) {
            $res = @openssl_pkey_get_private($private_key);

            if ($res === false) {
                $error = openssl_error_string();
                if (get_option('simple_smtp_dkim_debug_mode', false)) {
                    error_log('SMTP Config: OpenSSL error — ' . $error);
                }
                if (stripos($error, 'bad decrypt') !== false || stripos($error, 'bad password') !== false) {
                    return array('valid' => false, 'message' => __('Key is encrypted. Enter the passphrase above.', 'simple-smtp-dkim'), 'debug' => $error);
                }
                return array('valid' => false, 'message' => __('Private key is not valid or is encrypted.', 'simple-smtp-dkim'), 'debug' => $error);
            }

            $details = openssl_pkey_get_details($res);
            if ($details) {
                $type = isset($details['type']) ? ($details['type'] === OPENSSL_KEYTYPE_RSA ? 'RSA' : ($details['type'] === OPENSSL_KEYTYPE_DSA ? 'DSA' : 'Unknown')) : 'Unknown';
                $bits = isset($details['bits']) ? $details['bits'] : 0;
                $info = sprintf(__('Key type: %s, Key size: %d bits', 'simple-smtp-dkim'), $type, $bits);

                if (get_option('simple_smtp_dkim_debug_mode', false)) {
                    error_log('SMTP Config: Key valid — ' . $info);
                }

                if ($bits < 1024) {
                    return array('valid' => false, 'message' => __('Key too small. Use at least 1024 bits (2048 recommended).', 'simple-smtp-dkim'), 'debug' => $info);
                }

                if (PHP_MAJOR_VERSION < 8) {
                    openssl_free_key($res);
                }

                return array('valid' => true, 'message' => __('Private key is valid.', 'simple-smtp-dkim'), 'info' => $info, 'debug' => 'OK');
            }

            if (PHP_MAJOR_VERSION < 8) {
                openssl_free_key($res);
            }
        }

        return array('valid' => true, 'message' => __('Private key appears valid (detailed check unavailable).', 'simple-smtp-dkim'), 'debug' => 'Basic PEM check passed');
    }

    /**
     * Check if DKIM DNS record exists
     *
     * @param string $domain
     * @param string $selector
     * @return array {found, message, record_name, record_content?, matched?}
     */
    public static function check_dkim_dns($domain, $selector) {
        $dns_record = $selector . '._domainkey.' . $domain;
        $records    = @dns_get_record($dns_record, DNS_TXT);

        if ($records === false || empty($records)) {
            return array('found' => false, 'message' => sprintf(__('DKIM DNS record not found at: %s', 'simple-smtp-dkim'), $dns_record), 'record_name' => $dns_record);
        }

        $dkim_found      = false;
        $record_content   = '';
        $public_key_in_dns = '';

        foreach ($records as $record) {
            if (isset($record['txt']) && strpos($record['txt'], 'p=') !== false) {
                $dkim_found     = true;
                $record_content = $record['txt'];
                if (preg_match('/p=([A-Za-z0-9+\/=]+)/', $record['txt'], $m)) {
                    $public_key_in_dns = $m[1];
                }
                break;
            }
        }

        if (!$dkim_found) {
            return array('found' => false, 'message' => sprintf(__('DNS record at %s does not appear to be a valid DKIM record.', 'simple-smtp-dkim'), $dns_record), 'record_name' => $dns_record);
        }

        $saved_public = get_option('simple_smtp_dkim_dkim_public_key', '');
        if (!empty($saved_public) && !empty($public_key_in_dns)) {
            $matched = ($saved_public === $public_key_in_dns);
            return array(
                'found'          => true,
                'matched'        => $matched,
                'message'        => $matched
                    ? sprintf(__('✅ DNS record found and matches at: %s', 'simple-smtp-dkim'), $dns_record)
                    : sprintf(__('⚠️ DNS record found at %s but public key does NOT match.', 'simple-smtp-dkim'), $dns_record),
                'record_name'    => $dns_record,
                'record_content' => substr($record_content, 0, 100) . '...',
            );
        }

        return array('found' => true, 'message' => sprintf(__('DKIM DNS record found at: %s', 'simple-smtp-dkim'), $dns_record), 'record_name' => $dns_record, 'record_content' => substr($record_content, 0, 100) . '...');
    }
}
