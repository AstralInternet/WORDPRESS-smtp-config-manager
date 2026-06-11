<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables in included file scope.
/**
 * Tab partial: Email Logs
 * @package Simple_SMTP_DKIM
 */
if (!defined('WPINC')) { die; }

$logging_enabled = (bool) get_option('simple_smtp_dkim_logging_enabled', false);
$retention_days  = (int) get_option('simple_smtp_dkim_log_retention_days', 30);
$log_body_on     = (bool) get_option('simple_smtp_dkim_log_email_body', false);
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$page            = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$search          = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$status_filter   = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
if (!in_array($status_filter, array('', 'success', 'failed'), true)) {
    $status_filter = '';
}

$date_format = get_option('date_format') . ' ' . get_option('time_format');
$logs_url    = admin_url('options-general.php?page=simple-smtp-dkim&tab=logs');
?>

<div class="ssd-section">
    <!-- Logging settings -->
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="ssd-form">
        <?php wp_nonce_field('simple_smtp_dkim_save_settings', 'simple_smtp_dkim_nonce'); ?>
        <input type="hidden" name="action" value="simple_smtp_dkim_save_settings">
        <input type="hidden" name="tab" value="logs">

        <div class="ssd-card">
            <div class="ssd-card-head">
                <?php Simple_SMTP_DKIM_Helpers::icon('list', 18); ?>
                <h2><?php esc_html_e('Logging settings', 'simple-smtp-dkim'); ?></h2>
                <span class="ssd-spacer"></span>
                <button type="submit" class="ssd-btn ssd-btn-primary ssd-btn-sm"><?php esc_html_e('Save', 'simple-smtp-dkim'); ?></button>
            </div>
            <?php
            Simple_SMTP_DKIM_Helpers::render_toggle_row(array(
                'name'    => 'simple_smtp_dkim_logging_enabled',
                'id'      => 'simple_smtp_dkim_logging_enabled',
                'checked' => $logging_enabled,
                'title'   => __('Enable logging', 'simple-smtp-dkim'),
                'desc'    => __('Records every email sent with its delivery status.', 'simple-smtp-dkim'),
            ));
            Simple_SMTP_DKIM_Helpers::render_toggle_row(array(
                'name'    => 'simple_smtp_dkim_log_email_body',
                'id'      => 'simple_smtp_dkim_log_email_body',
                'checked' => $log_body_on,
                'title'   => __('Keep the email content', 'simple-smtp-dkim'),
                'tip'     => __('Content is encrypted before storage. It may include sensitive data such as password resets or verification codes.', 'simple-smtp-dkim'),
                'desc'    => __('Lets you review the full message later. Encrypted with AES-256-CBC.', 'simple-smtp-dkim'),
            ));
            ?>
            <div class="ssd-trow">
                <div class="ssd-t-main">
                    <label class="ssd-t-title" for="simple_smtp_dkim_log_retention_days"><?php esc_html_e('Retention period', 'simple-smtp-dkim'); ?></label>
                    <div class="ssd-t-desc"><?php esc_html_e('Older logs are deleted automatically. 0 = keep forever.', 'simple-smtp-dkim'); ?></div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <input type="number" name="simple_smtp_dkim_log_retention_days" id="simple_smtp_dkim_log_retention_days"
                           value="<?php echo esc_attr($retention_days); ?>" class="ssd-inp" min="0" max="365" style="width:80px;">
                    <span class="ssd-muted-note"><?php esc_html_e('days', 'simple-smtp-dkim'); ?></span>
                </div>
            </div>
        </div>
    </form>

    <?php if ($logging_enabled): ?>
        <?php
        $logs_data = Simple_SMTP_DKIM_Logger::get_logs(array(
            'page'     => $page,
            'per_page' => 20,
            'search'   => $search,
            'status'   => $status_filter,
        ));
        $counts = Simple_SMTP_DKIM_Logger::count_by_status($search);
        $stats  = Simple_SMTP_DKIM_Logger::get_statistics(30);

        $export_url = wp_nonce_url(
            add_query_arg(
                array('action' => 'simple_smtp_dkim_export_logs', 's' => $search, 'status' => $status_filter),
                admin_url('admin-post.php')
            ),
            'simple_smtp_dkim_export_logs'
        );

        $pill_base = add_query_arg(array('s' => $search ? $search : false), $logs_url);
        $pills = array(
            ''        => array('label' => __('All', 'simple-smtp-dkim'),       'count' => $counts['all']),
            'success' => array('label' => __('Delivered', 'simple-smtp-dkim'), 'count' => $counts['success']),
            'failed'  => array('label' => __('Failed', 'simple-smtp-dkim'),    'count' => $counts['failed']),
        );
        ?>

        <!-- Stats -->
        <div class="ssd-card">
            <div class="ssd-card-head">
                <?php Simple_SMTP_DKIM_Helpers::icon('gauge', 18); ?>
                <h2><?php esc_html_e('Sending activity', 'simple-smtp-dkim'); ?></h2>
                <span class="ssd-hint"><?php esc_html_e('Last 30 days', 'simple-smtp-dkim'); ?></span>
            </div>
            <div class="ssd-stats">
                <div class="ssd-stat"><div class="ssd-s-num"><?php echo esc_html(number_format_i18n($stats['total'])); ?></div><div class="ssd-s-lab"><?php esc_html_e('Total', 'simple-smtp-dkim'); ?></div></div>
                <div class="ssd-stat ok"><div class="ssd-s-num"><?php echo esc_html(number_format_i18n($stats['success'])); ?></div><div class="ssd-s-lab"><?php esc_html_e('Delivered', 'simple-smtp-dkim'); ?></div></div>
                <div class="ssd-stat err"><div class="ssd-s-num"><?php echo esc_html(number_format_i18n($stats['failed'])); ?></div><div class="ssd-s-lab"><?php esc_html_e('Failed', 'simple-smtp-dkim'); ?></div></div>
                <div class="ssd-stat accent"><div class="ssd-s-num"><?php echo esc_html(round($stats['success_rate'])); ?>%</div><div class="ssd-s-lab"><?php esc_html_e('Rate', 'simple-smtp-dkim'); ?></div></div>
                <div class="ssd-stat"><div class="ssd-s-num"><?php echo esc_html(number_format_i18n($stats['dkim_signed'])); ?></div><div class="ssd-s-lab"><?php esc_html_e('DKIM signed', 'simple-smtp-dkim'); ?></div></div>
            </div>
        </div>

        <!-- Email log -->
        <div class="ssd-card">
            <div class="ssd-card-head">
                <?php Simple_SMTP_DKIM_Helpers::icon('inbox', 18); ?>
                <h2><?php esc_html_e('Email log', 'simple-smtp-dkim'); ?></h2>
                <span class="ssd-spacer"></span>
                <div class="ssd-actions">
                    <a href="<?php echo esc_url($export_url); ?>" class="ssd-btn ssd-btn-ghost ssd-btn-sm">
                        <?php Simple_SMTP_DKIM_Helpers::icon('download', 14); ?><?php esc_html_e('Export CSV', 'simple-smtp-dkim'); ?>
                    </a>
                    <button type="button" class="ssd-btn ssd-btn-danger ssd-btn-sm" id="ssd-delete-all-logs">
                        <?php Simple_SMTP_DKIM_Helpers::icon('trash', 14); ?><?php esc_html_e('Clear all', 'simple-smtp-dkim'); ?>
                    </button>
                </div>
            </div>

            <div class="ssd-logs-toolbar">
                <div class="ssd-filter-pills">
                    <?php foreach ($pills as $pill_status => $pill): ?>
                        <a class="ssd-fp <?php echo $status_filter === $pill_status ? 'active' : ''; ?>"
                           href="<?php echo esc_url($pill_status === '' ? remove_query_arg('status', $pill_base) : add_query_arg('status', $pill_status, $pill_base)); ?>">
                            <?php echo esc_html($pill['label']); ?>
                            <span class="ssd-fp-n"><?php echo esc_html(number_format_i18n($pill['count'])); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
                <form method="get" class="ssd-search-box" role="search">
                    <input type="hidden" name="page" value="simple-smtp-dkim">
                    <input type="hidden" name="tab" value="logs">
                    <?php if ($status_filter !== ''): ?>
                        <input type="hidden" name="status" value="<?php echo esc_attr($status_filter); ?>">
                    <?php endif; ?>
                    <?php Simple_SMTP_DKIM_Helpers::icon('search', 16); ?>
                    <label for="ssd-log-search" class="ssd-screen-reader-text"><?php esc_html_e('Search logs', 'simple-smtp-dkim'); ?></label>
                    <input type="search" id="ssd-log-search" name="s" value="<?php echo esc_attr($search); ?>"
                           placeholder="<?php esc_attr_e('Search by recipient or subject…', 'simple-smtp-dkim'); ?>">
                </form>
            </div>

            <?php if (!empty($logs_data['logs'])): ?>
                <div class="ssd-log-list">
                    <?php foreach ($logs_data['logs'] as $log):
                        $is_ok = ($log['status'] === 'success');
                        $log_date = date_i18n($date_format, strtotime($log['timestamp']));
                    ?>
                        <button type="button" class="ssd-log-item ssd-open-log"
                                data-log-id="<?php echo esc_attr($log['id']); ?>"
                                data-status="<?php echo $is_ok ? 'ok' : 'err'; ?>"
                                data-to="<?php echo esc_attr($log['to_email']); ?>"
                                data-from="<?php echo esc_attr($log['from_email']); ?>"
                                data-subject="<?php echo esc_attr($log['subject']); ?>"
                                data-date="<?php echo esc_attr($log_date); ?>"
                                data-dkim="<?php echo $log['dkim_signed'] ? '1' : '0'; ?>"
                                data-error="<?php echo esc_attr((string) $log['error_message']); ?>"
                                data-has-body="<?php echo !empty($log['email_body']) ? '1' : '0'; ?>">
                            <span class="ssd-log-status-ico <?php echo $is_ok ? 'ok' : 'err'; ?>">
                                <?php Simple_SMTP_DKIM_Helpers::icon($is_ok ? 'check' : 'x', 16); ?>
                            </span>
                            <span class="ssd-log-main">
                                <span class="ssd-log-to"><?php echo esc_html($log['to_email']); ?></span>
                                <span class="ssd-log-subject">
                                    <?php if (!$is_ok && !empty($log['error_message'])): ?>
                                        <span class="errtxt"><?php echo esc_html($log['error_message']); ?></span>
                                    <?php else: ?>
                                        <?php echo esc_html($log['subject']); ?>
                                    <?php endif; ?>
                                </span>
                            </span>
                            <span class="ssd-log-meta">
                                <span class="ssd-log-date"><?php echo esc_html($log_date); ?></span>
                                <?php if ($log['dkim_signed']): ?>
                                    <span class="ssd-dkim-tag yes"><?php esc_html_e('DKIM ✓', 'simple-smtp-dkim'); ?></span>
                                <?php else: ?>
                                    <span class="ssd-dkim-tag no"><?php esc_html_e('not signed', 'simple-smtp-dkim'); ?></span>
                                <?php endif; ?>
                            </span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <?php if ($logs_data['pages'] > 1): ?>
                    <nav class="ssd-pagination" aria-label="<?php esc_attr_e('Logs pagination', 'simple-smtp-dkim'); ?>">
                        <?php
                        echo wp_kses_post(paginate_links(array(
                            'base'      => add_query_arg('paged', '%#%'),
                            'format'    => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total'     => (int) $logs_data['pages'],
                            'current'   => $page,
                        )));
                        ?>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="ssd-empty">
                    <?php Simple_SMTP_DKIM_Helpers::icon($search !== '' || $status_filter !== '' ? 'search' : 'inbox', 40); ?>
                    <div class="ssd-e-title">
                        <?php
                        if ($search !== '' || $status_filter !== '') {
                            esc_html_e('No results for these filters', 'simple-smtp-dkim');
                        } else {
                            esc_html_e('No emails logged yet', 'simple-smtp-dkim');
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Log detail slide-over -->
        <div class="ssd-slideover-overlay ssd-hidden" id="ssd-log-overlay"></div>
        <div class="ssd-slideover ssd-hidden" id="ssd-log-detail" role="dialog" aria-modal="true" aria-labelledby="ssd-so-title" tabindex="-1">
            <div class="ssd-so-head">
                <span class="ssd-log-status-ico ok"><?php Simple_SMTP_DKIM_Helpers::icon('check', 16); ?></span>
                <span class="ssd-log-status-ico err"><?php Simple_SMTP_DKIM_Helpers::icon('x', 16); ?></span>
                <h3 id="ssd-so-title">
                    <span class="ssd-so-title-ok"><?php esc_html_e('Email delivered', 'simple-smtp-dkim'); ?></span>
                    <span class="ssd-so-title-err"><?php esc_html_e('Delivery failed', 'simple-smtp-dkim'); ?></span>
                </h3>
                <span class="ssd-spacer"></span>
                <button type="button" class="ssd-modal-x" data-ssd-close="logdetail" aria-label="<?php esc_attr_e('Close', 'simple-smtp-dkim'); ?>">
                    <?php Simple_SMTP_DKIM_Helpers::icon('x', 18); ?>
                </button>
            </div>
            <div class="ssd-so-body">
                <div class="ssd-so-meta">
                    <div class="ssd-so-meta-row"><span class="k"><?php esc_html_e('To', 'simple-smtp-dkim'); ?></span><span class="v" id="ssd-so-to"></span></div>
                    <div class="ssd-so-meta-row"><span class="k"><?php esc_html_e('From', 'simple-smtp-dkim'); ?></span><span class="v" id="ssd-so-from"></span></div>
                    <div class="ssd-so-meta-row"><span class="k"><?php esc_html_e('Subject', 'simple-smtp-dkim'); ?></span><span class="v" id="ssd-so-subject"></span></div>
                    <div class="ssd-so-meta-row"><span class="k"><?php esc_html_e('Date', 'simple-smtp-dkim'); ?></span><span class="v" id="ssd-so-date"></span></div>
                    <div class="ssd-so-meta-row">
                        <span class="k"><?php esc_html_e('Status', 'simple-smtp-dkim'); ?></span>
                        <span class="v">
                            <span class="ssd-badge ok"><?php esc_html_e('Delivered', 'simple-smtp-dkim'); ?></span>
                            <span class="ssd-badge err"><?php esc_html_e('Failed', 'simple-smtp-dkim'); ?></span>
                        </span>
                    </div>
                    <div class="ssd-so-meta-row"><span class="k"><?php esc_html_e('DKIM', 'simple-smtp-dkim'); ?></span><span class="v" id="ssd-so-dkim"></span></div>
                </div>
                <div class="ssd-banner err ssd-so-error" style="margin-bottom:16px;">
                    <?php Simple_SMTP_DKIM_Helpers::icon('alertc', 19); ?>
                    <div id="ssd-so-error-text"></div>
                </div>
                <p class="ssd-section-label"><?php esc_html_e('Message content', 'simple-smtp-dkim'); ?></p>
                <div class="ssd-email-frame" id="ssd-so-frame">
                    <iframe id="ssd-so-iframe" sandbox="" title="<?php esc_attr_e('Email content', 'simple-smtp-dkim'); ?>"></iframe>
                    <div class="ssd-email-empty"><?php esc_html_e('Content not kept (email body storage is disabled).', 'simple-smtp-dkim'); ?></div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="ssd-card">
            <div class="ssd-empty">
                <?php Simple_SMTP_DKIM_Helpers::icon('inbox', 40); ?>
                <div class="ssd-e-title"><?php esc_html_e('Logging is disabled', 'simple-smtp-dkim'); ?></div>
                <p class="ssd-muted-note" style="justify-content:center;"><?php esc_html_e('Enable it above to start tracking your emails.', 'simple-smtp-dkim'); ?></p>
            </div>
        </div>
    <?php endif; ?>
</div>
