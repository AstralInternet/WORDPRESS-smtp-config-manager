<?php
/**
 * Email logger for tracking sent emails
 *
 * This class handles logging of sent emails with auto-purge functionality
 * based on retention settings.
 *
 * @package Simple_SMTP_DKIM_Manager
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Email logging system.
 *  *
 *  * Records sent and failed emails in a custom database table with
 *  * support for pagination, statistics, CSV export, and automatic purging.
 *  *
 *  * @since 1.0.0
 */
class Simple_SMTP_DKIM_Logger {
    
    /**
     * Table name for logs
     */
    private static $table_name = null;
    
    /**
     * Initialize the logger
     */
    public static function init() {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'smtp_email_logs';

        // Hook purge to cron event (the event itself is scheduled in save-handlers.php)
        add_action('simple_smtp_dkim_purge_logs', array(__CLASS__, 'purge_old_logs'));
    }

    /**
     * Ensure table name is set (called lazily if init wasn't called)
     */
    private static function ensure_table() {
        if (empty(self::$table_name)) {
            global $wpdb;
            self::$table_name = $wpdb->prefix . 'smtp_email_logs';
        }
    }
    
    /**
     * Log an email attempt
     *
     * @param array $email_data Email information
     * @return int|false The log ID or false on failure
     */
    public static function log_email($email_data) {
        self::ensure_table();
        // Check if logging is enabled
        if (!get_option('simple_smtp_dkim_logging_enabled', false)) {
            return false;
        }
        
        global $wpdb;
        
        $defaults = array(
            'to_email' => '',
            'from_email' => '',
            'subject' => '',
            'email_body' => '',
            'email_headers' => '',
            'status' => 'unknown',
            'error_message' => null,
            'dkim_signed' => false,
        );
        
        $data = wp_parse_args($email_data, $defaults);
        
        // Sanitize data
        $log_data = array(
            'timestamp' => current_time('mysql'),
            'to_email' => sanitize_email($data['to_email']),
            'from_email' => sanitize_email($data['from_email']),
            'subject' => sanitize_text_field(substr($data['subject'], 0, 500)),
            'email_body' => '', // Default to empty
            'email_headers' => is_array($data['email_headers']) ? implode("\n", $data['email_headers']) : $data['email_headers'],
            'status' => sanitize_text_field($data['status']),
            'error_message' => !empty($data['error_message']) ? sanitize_text_field(substr($data['error_message'], 0, 1000)) : null,
            'dkim_signed' => (int) $data['dkim_signed'],
        );
        
        // Only store email body if explicitly enabled (opt-in for privacy)
        if (get_option('simple_smtp_dkim_log_email_body', false) && !empty($data['email_body'])) {
            // Encrypt the email body before storage
            $encrypted_body = Simple_SMTP_DKIM_Encryption::encrypt($data['email_body']);
            $log_data['email_body'] = ($encrypted_body !== false) ? $encrypted_body : '';
        }
        
        $result = $wpdb->insert(
            self::$table_name,
            $log_data,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
        );
        
        if ($result === false) {
            error_log('SMTP Config Manager: Failed to insert log entry - ' . $wpdb->last_error);
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get logs with pagination and filtering
     *
     * @param array $args Query arguments
     * @return array Array with 'logs' and 'total' count
     */
    public static function get_logs($args = array()) {
        self::ensure_table();
        global $wpdb;
        
        $defaults = array(
            'per_page' => 20,
            'page' => 1,
            'status' => '',
            'search' => '',
            'order_by' => 'timestamp',
            'order' => 'DESC',
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Build WHERE clause
        $where = array('1=1');
        $where_values = array();
        
        // Filter by status
        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $where_values[] = $args['status'];
        }
        
        // Search in to_email, from_email, or subject
        if (!empty($args['search'])) {
            $where[] = '(to_email LIKE %s OR from_email LIKE %s OR subject LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Sanitize order by and order
        $allowed_order_by = array('timestamp', 'to_email', 'from_email', 'status');
        $order_by = in_array($args['order_by'], $allowed_order_by) ? $args['order_by'] : 'timestamp';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        // Get total count (always use prepare to avoid WP 6.x deprecation)
        if (!empty($where_values)) {
            $count_query = $wpdb->prepare(
                "SELECT COUNT(*) FROM " . self::$table_name . " WHERE " . $where_clause,
                $where_values
            );
        } else {
            $count_query = $wpdb->prepare(
                "SELECT COUNT(*) FROM %i WHERE 1=1",
                self::$table_name
            );
        }
        $total = $wpdb->get_var($count_query); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        
        // Get logs
        $offset = ($args['page'] - 1) * $args['per_page'];
        $limit = (int) $args['per_page'];
        
        $query = "SELECT * FROM " . self::$table_name . " WHERE " . $where_clause . " ORDER BY $order_by $order LIMIT %d OFFSET %d";
        $query_values = array_merge($where_values, array($limit, $offset));
        
        $logs = $wpdb->get_results($wpdb->prepare($query, $query_values), ARRAY_A);
        
        return array(
            'logs' => $logs,
            'total' => (int) $total,
            'pages' => ceil($total / $args['per_page']),
        );
    }
    
    /**
     * Get log statistics
     *
     * @param int $days Number of days to look back (default: 30)
     * @return array Statistics array
     */
    public static function get_statistics($days = 30) {
        self::ensure_table();
        global $wpdb;
        
        $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Single query with conditional aggregation instead of 4 separate queries
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) AS total,
                SUM(status = 'success') AS success,
                SUM(status = 'failed') AS failed,
                SUM(dkim_signed = 1) AS dkim_signed
             FROM " . self::$table_name . " 
             WHERE timestamp >= %s",
            $date_from
        ));
        
        $stats = array(
            'total'        => $row ? (int) $row->total : 0,
            'success'      => $row ? (int) $row->success : 0,
            'failed'       => $row ? (int) $row->failed : 0,
            'dkim_signed'  => $row ? (int) $row->dkim_signed : 0,
            'success_rate' => 0,
        );
        
        if ($stats['total'] > 0) {
            $stats['success_rate'] = round(($stats['success'] / $stats['total']) * 100, 2);
        }
        
        return $stats;
    }
    
    /**
     * Purge old logs based on retention settings
     *
     * @return int Number of deleted rows
     */
    public static function purge_old_logs() {
        self::ensure_table();
        global $wpdb;
        
        $retention_days = get_option('simple_smtp_dkim_log_retention_days', 30);
        
        if ($retention_days <= 0) {
            return 0;
        }
        
        $date_threshold = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM " . self::$table_name . " WHERE timestamp < %s",
            $date_threshold
        ));
        
        if ($deleted > 0) {
            error_log("SMTP Config Manager: Purged {$deleted} old log entries");
        }
        
        return $deleted;
    }
    
    /**
     * Delete all logs
     *
     * @return int|false Number of deleted rows or false on failure
     */
    public static function delete_all_logs() {
        self::ensure_table();
        global $wpdb;
        
        $table = self::$table_name;
        
        // Validate table name contains only allowed characters
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
            error_log('SMTP Config Manager: Invalid table name detected');
            return false;
        }
        
        $result = $wpdb->query("TRUNCATE TABLE `{$table}`");
        
        if ($result !== false) {
            error_log('SMTP Config Manager: All logs deleted');
        }
        
        return $result;
    }
    
    /**
     * Delete a single log entry
     *
     * @param int $log_id The log ID
     * @return bool True on success, false on failure
     */
    public static function delete_log($log_id) {
        self::ensure_table();
        global $wpdb;
        
        $result = $wpdb->delete(
            self::$table_name,
            array('id' => $log_id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get a single log entry
     *
     * @param int $log_id The log ID
     * @return array|null Log data or null if not found
     */
    public static function get_log($log_id) {
        self::ensure_table();
        global $wpdb;
        
        $log = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::$table_name . " WHERE id = %d",
            $log_id
        ), ARRAY_A);
        
        return $log;
    }
    
    /**
     * Export logs to CSV
     *
     * @param array $args Query arguments (same as get_logs)
     * @return string|false CSV content or false on failure
     */
    public static function export_logs_csv($args = array()) {
        self::ensure_table();
        $logs_data = self::get_logs(array_merge($args, array('per_page' => -1, 'page' => 1)));
        
        if (empty($logs_data['logs'])) {
            return false;
        }
        
        // Create CSV content
        $output = fopen('php://temp', 'r+');
        
        // Header row
        fputcsv($output, array(
            'ID',
            'Timestamp',
            'To',
            'From',
            'Subject',
            'Status',
            'DKIM Signed',
            'Error Message'
        ));
        
        // Data rows
        foreach ($logs_data['logs'] as $log) {
            fputcsv($output, array(
                $log['id'],
                $log['timestamp'],
                $log['to_email'],
                $log['from_email'],
                $log['subject'],
                $log['status'],
                $log['dkim_signed'] ? 'Yes' : 'No',
                $log['error_message']
            ));
        }
        
        rewind($output);
        $csv_content = stream_get_contents($output);
        fclose($output);
        
        return $csv_content;
    }
    
    /**
     * Get recent errors
     *
     * @param int $limit Number of errors to retrieve
     * @return array Array of error logs
     */
    public static function get_recent_errors($limit = 10) {
        self::ensure_table();
        global $wpdb;
        
        $errors = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM " . self::$table_name . " WHERE status = 'failed' ORDER BY timestamp DESC LIMIT %d",
            $limit
        ), ARRAY_A);
        
        return $errors;
    }
    
    /**
     * Clean up on plugin deactivation
     */
    public static function cleanup() {
        // Remove scheduled event
        $timestamp = wp_next_scheduled('simple_smtp_dkim_purge_logs');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'simple_smtp_dkim_purge_logs');
        }
    }
}

// Lazy-initialized: call Simple_SMTP_DKIM_Logger::init() only when logging is active.
