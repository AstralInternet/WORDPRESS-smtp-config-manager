<?php
/**
 * Form save handlers — one function per tab
 *
 * @package Simple_SMTP_DKIM
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Save mailer settings (common + SMTP or OAuth2 specific).
 *  *
 *  * Only saves settings for the active sub-tab. Enabling a mailer type
 *  * implicitly deactivates the other. Never erases the inactive
 *  * mailer settings.
 *  *
 *  * @since 1.0.0
 */
function simple_smtp_dkim_save_mailer() {
    $u = 'Simple_SMTP_DKIM_Helpers';

    // Which sub-tab was submitted?
    $sub_tab = isset($_POST['mailer_sub']) ? sanitize_text_field($_POST['mailer_sub']) : 'smtp';
    if (!in_array($sub_tab, array('smtp', 'oauth'), true)) {
        $sub_tab = 'smtp';
    }

    // Enable/disable: the toggle controls THIS sub-tab's mailer
    $wants_enabled = isset($_POST['simple_smtp_dkim_enabled']) && $_POST['simple_smtp_dkim_enabled'];

    if ($wants_enabled) {
        // Activate this mailer type (deactivates the other implicitly)
        $u::update_option_no_autoload('simple_smtp_dkim_enabled', 1);
        $u::update_option_no_autoload('simple_smtp_dkim_mailer_type', $sub_tab);
    } else {
        // Only disable if this sub-tab IS the currently active mailer
        $current_type = get_option('simple_smtp_dkim_mailer_type', 'smtp');
        if ($current_type === $sub_tab) {
            $u::update_option_no_autoload('simple_smtp_dkim_enabled', 0);
        }
        // If disabling a non-active type, don't touch the enabled flag
    }

    // Common settings (saved regardless of which sub-tab)
    $u::update_option_no_autoload('simple_smtp_dkim_from_email', sanitize_email(wp_unslash($_POST['simple_smtp_dkim_from_email'])));
    $u::update_option_no_autoload('simple_smtp_dkim_from_name',  sanitize_text_field(wp_unslash($_POST['simple_smtp_dkim_from_name'])));
    $u::update_option_no_autoload('simple_smtp_dkim_force_from', isset($_POST['simple_smtp_dkim_force_from']) ? 1 : 0);

    // Save ONLY the active sub-tab's specific settings (never erase the other)
    if ($sub_tab === 'smtp') {
        $u::update_option_no_autoload('simple_smtp_dkim_host',     sanitize_text_field(wp_unslash($_POST['simple_smtp_dkim_host'])));
        $u::update_option_no_autoload('simple_smtp_dkim_port',     intval($_POST['simple_smtp_dkim_port']));
        $u::update_option_no_autoload('simple_smtp_dkim_secure',   sanitize_text_field(wp_unslash($_POST['simple_smtp_dkim_secure'])));
        $u::update_option_no_autoload('simple_smtp_dkim_auth',     isset($_POST['simple_smtp_dkim_auth']) ? 1 : 0);
        $u::update_option_no_autoload('simple_smtp_dkim_username', sanitize_text_field(wp_unslash($_POST['simple_smtp_dkim_username'])));

        if (!empty($_POST['simple_smtp_dkim_password'])) {
            Simple_SMTP_DKIM_Encryption::save_encrypted_option('simple_smtp_dkim_password', wp_unslash($_POST['simple_smtp_dkim_password']));
        }
    }

    // OAuth-specific settings
    if ($sub_tab === 'oauth') {
        $u::update_option_no_autoload('simple_smtp_dkim_oauth_provider',        sanitize_text_field(wp_unslash($_POST['simple_smtp_dkim_oauth_provider'] ?? '')));
        $u::update_option_no_autoload('simple_smtp_dkim_oauth_grant_type',      sanitize_text_field(wp_unslash($_POST['simple_smtp_dkim_oauth_grant_type'] ?? 'authorization_code')));
        $u::update_option_no_autoload('simple_smtp_dkim_oauth_auth_method',     sanitize_text_field(wp_unslash($_POST['simple_smtp_dkim_oauth_auth_method'] ?? 'secret')));
        $u::update_option_no_autoload('simple_smtp_dkim_oauth_client_id',       sanitize_text_field(wp_unslash($_POST['simple_smtp_dkim_oauth_client_id'] ?? '')));
        $u::update_option_no_autoload('simple_smtp_dkim_oauth_smtp_address',    sanitize_email(wp_unslash($_POST['simple_smtp_dkim_oauth_smtp_address'] ?? '')));
        $u::update_option_no_autoload('simple_smtp_dkim_oauth_tenant',          sanitize_text_field(wp_unslash($_POST['simple_smtp_dkim_oauth_tenant'] ?? '')));
        $u::update_option_no_autoload('simple_smtp_dkim_oauth_hosted_domain',   sanitize_text_field(wp_unslash($_POST['simple_smtp_dkim_oauth_hosted_domain'] ?? '')));
        $u::update_option_no_autoload('simple_smtp_dkim_oauth_project_id',      sanitize_text_field(wp_unslash($_POST['simple_smtp_dkim_oauth_project_id'] ?? '')));
        $u::update_option_no_autoload('simple_smtp_dkim_oauth_service_account', sanitize_text_field(wp_unslash($_POST['simple_smtp_dkim_oauth_service_account'] ?? '')));
        $u::update_option_no_autoload('simple_smtp_dkim_oauth_impersonate',     sanitize_email(wp_unslash($_POST['simple_smtp_dkim_oauth_impersonate'] ?? '')));
        $u::update_option_no_autoload('simple_smtp_dkim_oauth_cert_thumbprint', sanitize_text_field(wp_unslash($_POST['simple_smtp_dkim_oauth_cert_thumbprint'] ?? '')));

        // Encrypted fields: only update if non-empty
        if (!empty($_POST['simple_smtp_dkim_oauth_client_secret'])) {
            Simple_SMTP_DKIM_Encryption::save_encrypted_option('simple_smtp_dkim_oauth_client_secret', wp_unslash($_POST['simple_smtp_dkim_oauth_client_secret']));
        }
        if (!empty($_POST['simple_smtp_dkim_oauth_refresh_token'])) {
            Simple_SMTP_DKIM_Encryption::save_encrypted_option('simple_smtp_dkim_oauth_refresh_token', wp_unslash($_POST['simple_smtp_dkim_oauth_refresh_token']));
        }
        if (!empty($_POST['simple_smtp_dkim_oauth_cert_private_key'])) {
            Simple_SMTP_DKIM_Encryption::save_encrypted_option('simple_smtp_dkim_oauth_cert_private_key', wp_unslash($_POST['simple_smtp_dkim_oauth_cert_private_key']));
        }

        // Auto-set SMTP host/port based on provider
        $provider_hosts = array('microsoft' => 'smtp.office365.com', 'google' => 'smtp.gmail.com', 'googleapi' => 'smtp.gmail.com');
        $prov = sanitize_text_field(wp_unslash($_POST['simple_smtp_dkim_oauth_provider'] ?? ''));
        if (isset($provider_hosts[$prov])) {
            $u::update_option_no_autoload('simple_smtp_dkim_host', $provider_hosts[$prov]);
            $u::update_option_no_autoload('simple_smtp_dkim_port', 587);
            $u::update_option_no_autoload('simple_smtp_dkim_secure', 'tls');
        }
    }

    add_settings_error('simple_smtp_dkim_messages', 'saved', __('Mailer settings saved.', 'simple-smtp-dkim'), 'success');
}

/**
 * Save DKIM settings.
 *  *
 *  * Detects changes to domain, selector, storage method, or private key
 *  * and resets dns_verified to false, requiring re-validation.
 *  *
 *  * @since 1.0.0
 */
function simple_smtp_dkim_save_dkim() {
    $u = 'Simple_SMTP_DKIM_Helpers';

    // Capture old values to detect changes that invalidate DNS verification
    $old_domain   = get_option('simple_smtp_dkim_dkim_domain', '');
    $old_selector = get_option('simple_smtp_dkim_dkim_selector', '');
    $old_method   = get_option('simple_smtp_dkim_dkim_storage_method', 'database');

    $new_domain   = sanitize_text_field(wp_unslash($_POST['simple_smtp_dkim_dkim_domain']));
    $new_selector = sanitize_text_field(wp_unslash($_POST['simple_smtp_dkim_dkim_selector']));
    $new_method   = sanitize_text_field(wp_unslash($_POST['simple_smtp_dkim_dkim_storage_method']));

    // Track whether a key-affecting setting changed
    $dkim_changed = ($new_domain !== $old_domain || $new_selector !== $old_selector || $new_method !== $old_method);

    $u::update_option_no_autoload('simple_smtp_dkim_dkim_enabled',  isset($_POST['simple_smtp_dkim_dkim_enabled']) ? 1 : 0);
    $u::update_option_no_autoload('simple_smtp_dkim_dkim_domain',   $new_domain);
    $u::update_option_no_autoload('simple_smtp_dkim_dkim_selector', $new_selector);

    if (!empty($_POST['simple_smtp_dkim_dkim_passphrase'])) {
        Simple_SMTP_DKIM_Encryption::save_encrypted_option('simple_smtp_dkim_dkim_passphrase', wp_unslash($_POST['simple_smtp_dkim_dkim_passphrase']));
    }

    $u::update_option_no_autoload('simple_smtp_dkim_dkim_storage_method', $new_method);

    // Database upload
    if ($new_method === 'database' && isset($_FILES['simple_smtp_dkim_dkim_upload']) && !empty($_FILES['simple_smtp_dkim_dkim_upload']['tmp_name'])) {
        $content = Simple_SMTP_DKIM_Helpers::validate_dkim_upload($_FILES['simple_smtp_dkim_dkim_upload']);
        if (is_wp_error($content)) {
            add_settings_error('simple_smtp_dkim_messages', 'upload', $content->get_error_message(), 'error');
            return;
        }
        Simple_SMTP_DKIM_Encryption::save_encrypted_option('simple_smtp_dkim_dkim_private_key', $content);
        $dkim_changed = true; // New key uploaded
    }

    // File upload or path
    if ($new_method === 'file') {
        if (isset($_FILES['simple_smtp_dkim_dkim_file_upload']) && !empty($_FILES['simple_smtp_dkim_dkim_file_upload']['tmp_name'])) {
            $content = Simple_SMTP_DKIM_Helpers::validate_dkim_upload($_FILES['simple_smtp_dkim_dkim_file_upload']);
            if (is_wp_error($content)) {
                add_settings_error('simple_smtp_dkim_messages', 'upload', $content->get_error_message(), 'error');
                return;
            }
            $dir  = SIMPLE_SMTP_DKIM_UPLOAD_DIR;
            $dest = $dir . 'dkim_' . time() . '.private';
            if (move_uploaded_file($_FILES['simple_smtp_dkim_dkim_file_upload']['tmp_name'], $dest)) {
                chmod($dest, 0600);
                $u::update_option_no_autoload('simple_smtp_dkim_dkim_file_path', $dest);
                $dkim_changed = true; // New key file uploaded
            }
        } else {
            $raw = sanitize_text_field(wp_unslash($_POST['simple_smtp_dkim_dkim_file_path']));
            if (!empty($raw)) {
                $old_path  = get_option('simple_smtp_dkim_dkim_file_path', '');
                $validated = Simple_SMTP_DKIM_Helpers::validate_dkim_file_path($raw);
                if (is_wp_error($validated)) {
                    add_settings_error('simple_smtp_dkim_messages', 'path', $validated->get_error_message(), 'error');
                    return;
                }
                $u::update_option_no_autoload('simple_smtp_dkim_dkim_file_path', $validated);
                if ($validated !== $old_path) {
                    $dkim_changed = true; // Key file path changed
                }
            } else {
                $u::update_option_no_autoload('simple_smtp_dkim_dkim_file_path', '');
            }
        }
    }

    // If any DKIM-critical setting changed, invalidate the DNS verification
    if ($dkim_changed) {
        $u::update_option_no_autoload('simple_smtp_dkim_dns_verified', 0);
    }

    add_settings_error('simple_smtp_dkim_messages', 'saved', __('DKIM settings saved.', 'simple-smtp-dkim'), 'success');
}

/**
 * Save logging settings (enable toggle and retention period).
 *  *
 *  * @since 1.0.0
 */
function simple_smtp_dkim_save_logging() {
    $u   = 'Simple_SMTP_DKIM_Helpers';
    $was = get_option('simple_smtp_dkim_logging_enabled', false);
    $now = isset($_POST['simple_smtp_dkim_logging_enabled']) ? 1 : 0;

    $u::update_option_no_autoload('simple_smtp_dkim_logging_enabled',   $now);
    $u::update_option_no_autoload('simple_smtp_dkim_log_retention_days', intval($_POST['simple_smtp_dkim_log_retention_days']));
    $u::update_option_no_autoload('simple_smtp_dkim_log_email_body',    isset($_POST['simple_smtp_dkim_log_email_body']) ? 1 : 0);

    if ($now && !$was) {
        if (!wp_next_scheduled('simple_smtp_dkim_purge_logs')) {
            wp_schedule_event(time(), 'daily', 'simple_smtp_dkim_purge_logs');
        }
    } elseif (!$now && $was) {
        $ts = wp_next_scheduled('simple_smtp_dkim_purge_logs');
        if ($ts) wp_unschedule_event($ts, 'simple_smtp_dkim_purge_logs');
    }

    add_settings_error('simple_smtp_dkim_messages', 'saved', __('Logging settings saved.', 'simple-smtp-dkim'), 'success');
}

/**
 * Save advanced settings (debug mode, uninstall cleanup).
 *  *
 *  * @since 1.0.0
 */
function simple_smtp_dkim_save_advanced() {
    $u = 'Simple_SMTP_DKIM_Helpers';
    $u::update_option_no_autoload('simple_smtp_dkim_debug_mode',          isset($_POST['simple_smtp_dkim_debug_mode']) ? 1 : 0);
    $u::update_option_no_autoload('simple_smtp_dkim_delete_on_uninstall', isset($_POST['simple_smtp_dkim_delete_on_uninstall']) ? 1 : 0);
    add_settings_error('simple_smtp_dkim_messages', 'saved', __('Advanced settings saved.', 'simple-smtp-dkim'), 'success');
}
