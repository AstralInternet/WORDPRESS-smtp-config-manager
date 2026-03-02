<?php
/**
 * Tab partial: Dashboard
 * @package Simple_SMTP_DKIM
 */
if (!defined('WPINC')) { die; }

$smtp_status = Simple_SMTP_DKIM_Validator::get_connection_status();
$dkim_status = Simple_SMTP_DKIM_Validator::get_dkim_status();
$logging_on  = get_option('simple_smtp_dkim_logging_enabled', false);
$test_ok     = (bool) get_option('simple_smtp_dkim_last_test_success', false);
$dns_ok      = (bool) get_option('simple_smtp_dkim_dns_verified', false);

$steps = array(
    array('done' => $smtp_status['status'] === 'configured', 'label' => __('Configure mail server', 'simple-smtp-dkim'), 'link' => admin_url('options-general.php?page=simple-smtp-dkim&tab=mailer'), 'desc' => $smtp_status['message']),
    array('done' => $test_ok, 'label' => __('Send a successful test email', 'simple-smtp-dkim'), 'link' => admin_url('options-general.php?page=simple-smtp-dkim&tab=mailer'), 'desc' => $test_ok ? __('Last test succeeded.', 'simple-smtp-dkim') : __('Not yet tested.', 'simple-smtp-dkim')),
    array('done' => $dkim_status['status'] === 'configured', 'label' => __('Configure DKIM signing', 'simple-smtp-dkim'), 'link' => admin_url('options-general.php?page=simple-smtp-dkim&tab=dkim'), 'desc' => $dkim_status['message']),
    array('done' => $dns_ok, 'label' => __('Verify DKIM DNS record', 'simple-smtp-dkim'), 'link' => admin_url('options-general.php?page=simple-smtp-dkim&tab=dkim'), 'desc' => $dns_ok ? __('DNS verified.', 'simple-smtp-dkim') : __('Not yet verified.', 'simple-smtp-dkim')),
);
$done_count = count(array_filter(array_column($steps, 'done')));
$total      = count($steps);
$pct        = $total > 0 ? round(($done_count / $total) * 100) : 0;
?>

<!-- Progress -->
<div class="simple-smtp-dkim-card">
    <h2><?php _e('Setup Progress', 'simple-smtp-dkim'); ?></h2>
    <div class="smtp-progress-bar-wrap" role="progressbar" aria-valuenow="<?php echo esc_attr($pct); ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?php esc_attr_e('Setup progress', 'simple-smtp-dkim'); ?>">
        <div class="smtp-progress-bar" style="width:<?php echo esc_attr($pct); ?>%">
            <span class="smtp-progress-text"><?php printf('%d%%', $pct); ?></span>
        </div>
    </div>
    <p class="smtp-progress-summary"><?php printf(__('%1$d of %2$d steps completed', 'simple-smtp-dkim'), $done_count, $total); ?></p>
    <ul class="smtp-checklist">
        <?php foreach ($steps as $step): ?>
        <li class="<?php echo esc_attr($step['done'] ? 'done' : 'pending'); ?>">
            <span class="dashicons <?php echo esc_attr($step['done'] ? 'dashicons-yes-alt' : 'dashicons-marker'); ?>" aria-hidden="true"></span>
            <?php if ($step['done']): ?>
                <span><?php echo esc_html($step['label']); ?></span>
            <?php else: ?>
                <a href="<?php echo esc_url($step['link']); ?>"><?php echo esc_html($step['label']); ?></a>
            <?php endif; ?>
            <span class="smtp-step-desc"><?php echo esc_html($step['desc']); ?></span>
        </li>
        <?php endforeach; ?>
    </ul>
</div>

<?php if ($logging_on):
    $stats = Simple_SMTP_DKIM_Logger::get_statistics(30);
?>
<!-- Quick Stats -->
<div class="simple-smtp-dkim-card">
    <h2><?php _e('Email Activity — Last 30 Days', 'simple-smtp-dkim'); ?></h2>
    <div class="smtp-stats-grid">
        <div class="smtp-stat-box"><div class="smtp-stat-number"><?php echo number_format($stats['total']); ?></div><div class="smtp-stat-label"><?php _e('Total', 'simple-smtp-dkim'); ?></div></div>
        <div class="smtp-stat-box smtp-stat-success"><div class="smtp-stat-number"><?php echo number_format($stats['success']); ?></div><div class="smtp-stat-label"><?php _e('Sent', 'simple-smtp-dkim'); ?></div></div>
        <div class="smtp-stat-box smtp-stat-error"><div class="smtp-stat-number"><?php echo number_format($stats['failed']); ?></div><div class="smtp-stat-label"><?php _e('Failed', 'simple-smtp-dkim'); ?></div></div>
        <div class="smtp-stat-box"><div class="smtp-stat-number"><?php echo $stats['success_rate']; ?>%</div><div class="smtp-stat-label"><?php _e('Rate', 'simple-smtp-dkim'); ?></div></div>
        <div class="smtp-stat-box"><div class="smtp-stat-number"><?php echo number_format($stats['dkim_signed']); ?></div><div class="smtp-stat-label"><?php _e('DKIM', 'simple-smtp-dkim'); ?></div></div>
    </div>

    <?php $recent_errors = Simple_SMTP_DKIM_Logger::get_recent_errors(5);
    if (!empty($recent_errors)): ?>
    <h3><?php _e('Recent Failures', 'simple-smtp-dkim'); ?></h3>
    <table class="widefat fixed striped">
        <thead><tr><th><?php _e('Date', 'simple-smtp-dkim'); ?></th><th><?php _e('To', 'simple-smtp-dkim'); ?></th><th><?php _e('Error', 'simple-smtp-dkim'); ?></th></tr></thead>
        <tbody>
        <?php foreach ($recent_errors as $err): ?>
            <tr>
                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($err['timestamp']))); ?></td>
                <td><?php echo esc_html($err['to_email']); ?></td>
                <td><?php echo esc_html($err['error_message']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p><a href="<?php echo esc_url(admin_url('options-general.php?page=simple-smtp-dkim&tab=logs')); ?>"><?php _e('View all logs →', 'simple-smtp-dkim'); ?></a></p>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="simple-smtp-dkim-card">
    <p class="smtp-logging-disabled">
        <span class="dashicons dashicons-info" aria-hidden="true"></span>
        <?php _e('Logging is disabled.', 'simple-smtp-dkim'); ?>
        <a href="<?php echo esc_url(admin_url('options-general.php?page=simple-smtp-dkim&tab=logs')); ?>"><?php _e('Enable it to see statistics.', 'simple-smtp-dkim'); ?></a>
    </p>
</div>
<?php endif; ?>

<!-- Configuration Summary -->
<div class="simple-smtp-dkim-card">
    <h2><?php _e('Current Configuration', 'simple-smtp-dkim'); ?></h2>
    <table class="smtp-summary-table">
        <tr><th><?php _e('SMTP', 'simple-smtp-dkim'); ?></th><td><?php Simple_SMTP_DKIM_Helpers::render_badge(get_option('simple_smtp_dkim_enabled')); ?></td></tr>
        <tr><th><?php _e('Host', 'simple-smtp-dkim'); ?></th><td><?php echo esc_html(get_option('simple_smtp_dkim_host', '—')); ?></td></tr>
        <tr><th><?php _e('Port / Encryption', 'simple-smtp-dkim'); ?></th><td><?php echo esc_html(get_option('simple_smtp_dkim_port', '—') . ' / ' . strtoupper(get_option('simple_smtp_dkim_secure', 'none'))); ?></td></tr>
        <tr><th><?php _e('From', 'simple-smtp-dkim'); ?></th><td><?php echo esc_html(get_option('simple_smtp_dkim_from_email', '—')); ?></td></tr>
        <tr><th><?php _e('DKIM', 'simple-smtp-dkim'); ?></th><td><?php Simple_SMTP_DKIM_Helpers::render_badge(get_option('simple_smtp_dkim_dkim_enabled')); ?></td></tr>
        <?php if (get_option('simple_smtp_dkim_dkim_enabled')): ?>
        <tr><th><?php _e('DKIM Domain', 'simple-smtp-dkim'); ?></th><td><?php echo esc_html(get_option('simple_smtp_dkim_dkim_domain', '—')); ?></td></tr>
        <?php endif; ?>
    </table>
</div>
