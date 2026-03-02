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
$options = $wpdb->get_col(
    $wpdb->prepare(
        "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
        'simple_smtp_dkim_%'
    )
);

foreach ($options as $option) {
    delete_option($option);
}

// 2. Drop the email log table.
$table_name = $wpdb->prefix . 'simple_smtp_dkim_logs';
$wpdb->query("DROP TABLE IF EXISTS {$table_name}"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

// 3. Remove uploaded DKIM key files.
$upload_dir = wp_upload_dir();
$dkim_dir   = $upload_dir['basedir'] . '/simple-smtp-dkim/';

if (is_dir($dkim_dir)) {
    $files = glob($dkim_dir . '*');
    if ($files) {
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }
    @rmdir($dkim_dir);
}

// 4. Clear scheduled cron events.
$timestamp = wp_next_scheduled('simple_smtp_dkim_purge_logs');
if ($timestamp) {
    wp_unschedule_event($timestamp, 'simple_smtp_dkim_purge_logs');
}
