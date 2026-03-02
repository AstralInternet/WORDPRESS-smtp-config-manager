<?php
/**
 * Tab partial: Email Logs (improved)
 * @package Simple_SMTP_DKIM
 */
if (!defined('WPINC')) { die; }

$logging_enabled = get_option('simple_smtp_dkim_logging_enabled', false);
$retention_days  = get_option('simple_smtp_dkim_log_retention_days', 30);
$page            = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$search          = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$status_filter   = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$logs_data       = Simple_SMTP_DKIM_Logger::get_logs(array('page' => $page, 'per_page' => 20, 'search' => $search, 'status' => $status_filter));
$stats           = Simple_SMTP_DKIM_Logger::get_statistics(30);
?>

<div class="simple-smtp-dkim-section">
    <!-- Settings -->
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="simple-smtp-dkim-form">
        <?php wp_nonce_field('simple_smtp_dkim_save_settings', 'simple_smtp_dkim_nonce'); ?>
        <input type="hidden" name="action" value="simple_smtp_dkim_save_settings">
        <input type="hidden" name="tab" value="logs">
        <div class="simple-smtp-dkim-card">
            <h2><?php _e('Logging Settings', 'simple-smtp-dkim'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="simple_smtp_dkim_logging_enabled"><?php _e('Enable Logging', 'simple-smtp-dkim'); ?></label></th>
                    <td><?php Simple_SMTP_DKIM_Helpers::render_toggle('simple_smtp_dkim_logging_enabled', 'simple_smtp_dkim_logging_enabled', $logging_enabled); ?></td>
                </tr>
                <tr>
                    <th scope="row"><label for="simple_smtp_dkim_log_retention_days"><?php _e('Retention', 'simple-smtp-dkim'); ?></label></th>
                    <td>
                        <input type="number" name="simple_smtp_dkim_log_retention_days" id="simple_smtp_dkim_log_retention_days" value="<?php echo esc_attr($retention_days); ?>" class="small-text" min="0" max="365"> <?php _e('days', 'simple-smtp-dkim'); ?>
                        <p class="description"><?php _e('0 = keep forever.', 'simple-smtp-dkim'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="simple_smtp_dkim_log_email_body"><?php _e('Store Email Content', 'simple-smtp-dkim'); ?></label>
                        <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('Content is encrypted before storage. May include sensitive data.', 'simple-smtp-dkim')); ?>
                    </th>
                    <td>
                        <?php Simple_SMTP_DKIM_Helpers::render_toggle('simple_smtp_dkim_log_email_body', 'simple_smtp_dkim_log_email_body', get_option('simple_smtp_dkim_log_email_body', false)); ?>
                        <p class="description" style="color:#d63638;"><strong><?php _e('Privacy:', 'simple-smtp-dkim'); ?></strong> <?php _e('May include password resets, verification codes. Encrypted with AES-256-CBC.', 'simple-smtp-dkim'); ?></p>
                    </td>
                </tr>
            </table>
            <p class="submit"><button type="submit" class="button button-primary"><?php _e('Save Logging Settings', 'simple-smtp-dkim'); ?></button></p>
        </div>
    </form>

    <?php if ($logging_enabled): ?>

    <!-- Stats -->
    <div class="simple-smtp-dkim-card">
        <h2><?php _e('Last 30 Days', 'simple-smtp-dkim'); ?></h2>
        <div class="smtp-stats-grid">
            <div class="smtp-stat-box"><div class="smtp-stat-number"><?php echo number_format($stats['total']); ?></div><div class="smtp-stat-label"><?php _e('Total', 'simple-smtp-dkim'); ?></div></div>
            <div class="smtp-stat-box smtp-stat-success"><div class="smtp-stat-number"><?php echo number_format($stats['success']); ?></div><div class="smtp-stat-label"><?php _e('Sent', 'simple-smtp-dkim'); ?></div></div>
            <div class="smtp-stat-box smtp-stat-error"><div class="smtp-stat-number"><?php echo number_format($stats['failed']); ?></div><div class="smtp-stat-label"><?php _e('Failed', 'simple-smtp-dkim'); ?></div></div>
            <div class="smtp-stat-box"><div class="smtp-stat-number"><?php echo $stats['success_rate']; ?>%</div><div class="smtp-stat-label"><?php _e('Rate', 'simple-smtp-dkim'); ?></div></div>
            <div class="smtp-stat-box"><div class="smtp-stat-number"><?php echo number_format($stats['dkim_signed']); ?></div><div class="smtp-stat-label"><?php _e('DKIM', 'simple-smtp-dkim'); ?></div></div>
        </div>
    </div>

    <!-- Privacy Notice (condensed) -->
    <div class="simple-smtp-dkim-card smtp-notice-banner smtp-notice-warning">
        <span class="dashicons dashicons-info" aria-hidden="true"></span>
        <div>
            <strong><?php _e('Privacy:', 'simple-smtp-dkim'); ?></strong>
            <?php printf(__('Logs contain personal data. Retention: %d days. Ensure your privacy policy covers this.', 'simple-smtp-dkim'), $retention_days); ?>
        </div>
    </div>

    <!-- Log Table -->
    <div class="simple-smtp-dkim-card">
        <div class="smtp-logs-header">
            <h2><?php _e('Email Logs', 'simple-smtp-dkim'); ?></h2>
            <div class="smtp-logs-actions">
                <form method="get" class="smtp-search-form" role="search">
                    <input type="hidden" name="page" value="simple-smtp-dkim">
                    <input type="hidden" name="tab" value="logs">
                    <label for="smtp-log-search" class="screen-reader-text"><?php _e('Search logs', 'simple-smtp-dkim'); ?></label>
                    <input type="search" id="smtp-log-search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search...', 'simple-smtp-dkim'); ?>">
                    <label for="smtp-log-status-filter" class="screen-reader-text"><?php _e('Filter by status', 'simple-smtp-dkim'); ?></label>
                    <select id="smtp-log-status-filter" name="status">
                        <option value=""><?php _e('All', 'simple-smtp-dkim'); ?></option>
                        <option value="success" <?php selected($status_filter, 'success'); ?>><?php _e('Success', 'simple-smtp-dkim'); ?></option>
                        <option value="failed" <?php selected($status_filter, 'failed'); ?>><?php _e('Failed', 'simple-smtp-dkim'); ?></option>
                    </select>
                    <button type="submit" class="button"><?php _e('Filter', 'simple-smtp-dkim'); ?></button>
                </form>
                <button type="button" id="smtp-export-logs" class="button button-secondary"><span class="dashicons dashicons-download" aria-hidden="true"></span> <?php _e('Export CSV', 'simple-smtp-dkim'); ?></button>
                <button type="button" id="smtp-delete-all-logs" class="button button-secondary smtp-btn-danger"><?php _e('Delete All', 'simple-smtp-dkim'); ?></button>
            </div>
        </div>

        <?php if (!empty($logs_data['logs'])): ?>
        <table class="wp-list-table widefat fixed striped smtp-logs-table" role="table">
            <thead><tr>
                <th scope="col" class="smtp-col-date"><?php _e('Date', 'simple-smtp-dkim'); ?></th>
                <th scope="col" class="smtp-col-to"><?php _e('To', 'simple-smtp-dkim'); ?></th>
                <th scope="col" class="smtp-col-subject"><?php _e('Subject', 'simple-smtp-dkim'); ?></th>
                <th scope="col" class="smtp-col-status"><?php _e('Status', 'simple-smtp-dkim'); ?></th>
                <th scope="col" class="smtp-col-dkim"><?php _e('DKIM', 'simple-smtp-dkim'); ?></th>
                <th scope="col" class="smtp-col-actions"><?php _e('Actions', 'simple-smtp-dkim'); ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($logs_data['logs'] as $log): ?>
                <tr class="smtp-log-row-<?php echo esc_attr($log['status']); ?>">
                    <td data-colname="<?php esc_attr_e('Date', 'simple-smtp-dkim'); ?>"><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log['timestamp']))); ?></td>
                    <td data-colname="<?php esc_attr_e('To', 'simple-smtp-dkim'); ?>"><?php echo esc_html($log['to_email']); ?></td>
                    <td data-colname="<?php esc_attr_e('Subject', 'simple-smtp-dkim'); ?>">
                        <?php echo esc_html($log['subject']); ?>
                        <?php if (!empty($log['error_message'])): ?>
                            <div class="smtp-error-message"><small><?php echo esc_html($log['error_message']); ?></small></div>
                        <?php endif; ?>
                    </td>
                    <td data-colname="<?php esc_attr_e('Status', 'simple-smtp-dkim'); ?>"><span class="smtp-status-badge smtp-status-<?php echo esc_attr($log['status']); ?>"><?php echo esc_html(ucfirst($log['status'])); ?></span></td>
                    <td data-colname="<?php esc_attr_e('DKIM', 'simple-smtp-dkim'); ?>">
                        <?php if ($log['dkim_signed']): ?>
                            <span class="dashicons dashicons-yes-alt" style="color:#00a32a" title="<?php esc_attr_e('DKIM Signed', 'simple-smtp-dkim'); ?>" aria-label="<?php esc_attr_e('DKIM Signed', 'simple-smtp-dkim'); ?>"></span>
                        <?php else: ?>
                            <span class="dashicons dashicons-minus" style="color:#999" title="<?php esc_attr_e('Not Signed', 'simple-smtp-dkim'); ?>" aria-label="<?php esc_attr_e('Not Signed', 'simple-smtp-dkim'); ?>"></span>
                        <?php endif; ?>
                    </td>
                    <td data-colname="<?php esc_attr_e('Actions', 'simple-smtp-dkim'); ?>">
                        <?php if (!empty($log['email_body'])): ?>
                        <button type="button" class="button button-small smtp-view-email" data-log-id="<?php echo esc_attr($log['id']); ?>" aria-label="<?php esc_attr_e('View email', 'simple-smtp-dkim'); ?>">
                            <span class="dashicons dashicons-visibility" aria-hidden="true"></span> <?php _e('View', 'simple-smtp-dkim'); ?>
                        </button>
                        <?php else: ?>
                        <span class="smtp-no-content">&mdash;</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Email Modal (a11y) -->
        <div id="smtp-email-view-modal" class="smtp-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="smtp-modal-title">
            <div class="smtp-modal-overlay" tabindex="-1"></div>
            <div class="smtp-modal-content">
                <div class="smtp-modal-header">
                    <h3 id="smtp-modal-title"><?php _e('Email Content', 'simple-smtp-dkim'); ?></h3>
                    <button type="button" class="smtp-modal-close" aria-label="<?php esc_attr_e('Close', 'simple-smtp-dkim'); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
                </div>
                <div class="smtp-modal-body">
                    <div class="smtp-email-meta">
                        <p><strong><?php _e('To:', 'simple-smtp-dkim'); ?></strong> <span id="smtp-email-to"></span></p>
                        <p><strong><?php _e('From:', 'simple-smtp-dkim'); ?></strong> <span id="smtp-email-from"></span></p>
                        <p><strong><?php _e('Subject:', 'simple-smtp-dkim'); ?></strong> <span id="smtp-email-subject"></span></p>
                        <p><strong><?php _e('Date:', 'simple-smtp-dkim'); ?></strong> <span id="smtp-email-date"></span></p>
                    </div>
                    <div class="smtp-email-content-wrapper">
                        <iframe id="smtp-email-iframe" frameborder="0" sandbox="allow-same-origin" title="<?php esc_attr_e('Email content', 'simple-smtp-dkim'); ?>"></iframe>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($logs_data['pages'] > 1): ?>
        <div class="tablenav"><div class="tablenav-pages">
            <?php echo paginate_links(array('base' => add_query_arg('paged', '%#%'), 'format' => '', 'prev_text' => '&laquo;', 'next_text' => '&raquo;', 'total' => $logs_data['pages'], 'current' => $page)); ?>
        </div></div>
        <?php endif; ?>

        <?php else: ?>
        <p class="smtp-no-logs"><?php _e('No email logs found.', 'simple-smtp-dkim'); ?></p>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <div class="simple-smtp-dkim-card">
        <p class="smtp-logging-disabled"><span class="dashicons dashicons-info" aria-hidden="true"></span> <?php _e('Logging is disabled. Enable it above to start tracking emails.', 'simple-smtp-dkim'); ?></p>
    </div>
    <?php endif; ?>
</div>
