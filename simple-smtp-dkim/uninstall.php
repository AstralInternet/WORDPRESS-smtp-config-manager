<?php
/**
 * Fired when the plugin is uninstalled via the WordPress admin.
 *
 * Removes all plugin options, drops the log table, and cleans up
 * uploaded files. Only runs if the user has opted in via the
 * "Delete data on uninstall" setting.
 *
 * @since 1.0.0
 * @package Simple_SMTP_DKIM
 */

// Abort if not called by WordPress.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

// Only clean up if the user opted in.
if (!get_option('simple_smtp_dkim_delete_on_uninstall', false)) {
    return;
}

global $wpdb;

// 1. Remove all plugin options.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$simple_smtp_dkim_options = $wpdb->get_col(
    $wpdb->prepare(
        "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
        'simple_smtp_dkim_%'
    )
);

foreach ($simple_smtp_dkim_options as $simple_smtp_dkim_option) {
    delete_option($simple_smtp_dkim_option);
}

// 2. Remove the rate-limit transients created by the test-email endpoint.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$simple_smtp_dkim_transients = $wpdb->get_col(
    $wpdb->prepare(
        "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
        '_transient_smtp_test_email_%',
        '_transient_timeout_smtp_test_email_%'
    )
);
foreach ($simple_smtp_dkim_transients as $simple_smtp_dkim_transient) {
    delete_option($simple_smtp_dkim_transient);
}

// 3. Drop the email log table (created as {prefix}smtp_email_logs by the activator).
$simple_smtp_dkim_table = $wpdb->prefix . 'smtp_email_logs';
$wpdb->query("DROP TABLE IF EXISTS `{$simple_smtp_dkim_table}`"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

// 4. Remove uploaded DKIM key files (stored in wp-content/simple-smtp-dkim/).
$simple_smtp_dkim_dir = WP_CONTENT_DIR . '/simple-smtp-dkim/';

if (is_dir($simple_smtp_dkim_dir)) {
    // Two globs: GLOB_BRACE is not available on every platform, and the
    // directory contains a dotfile (.htaccess) that "*" does not match.
    $simple_smtp_dkim_files = array_merge(
        (array) glob($simple_smtp_dkim_dir . '*'),
        (array) glob($simple_smtp_dkim_dir . '.*')
    );
    foreach ($simple_smtp_dkim_files as $simple_smtp_dkim_file) {
        if (is_file($simple_smtp_dkim_file)) {
            wp_delete_file($simple_smtp_dkim_file);
        }
    }
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
    @rmdir($simple_smtp_dkim_dir);
}

// 5. Clear scheduled cron events.
$simple_smtp_dkim_ts = wp_next_scheduled('simple_smtp_dkim_purge_logs');
if ($simple_smtp_dkim_ts) {
    wp_unschedule_event($simple_smtp_dkim_ts, 'simple_smtp_dkim_purge_logs');
}
