<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables in included file scope.
/**
 * Tab partial: Dashboard
 * @package Simple_SMTP_DKIM
 */
if (!defined('WPINC')) { die; }

$enabled      = (bool) get_option('simple_smtp_dkim_enabled', false);
$host         = get_option('simple_smtp_dkim_host', '');
$port         = get_option('simple_smtp_dkim_port', 587);
$secure       = get_option('simple_smtp_dkim_secure', 'tls');
$from_email   = get_option('simple_smtp_dkim_from_email', get_option('admin_email'));
$logging_on   = (bool) get_option('simple_smtp_dkim_logging_enabled', false);
$test_ok      = (bool) get_option('simple_smtp_dkim_last_test_success', false);
$dkim_enabled = (bool) get_option('simple_smtp_dkim_dkim_enabled', false);
$dkim_domain  = get_option('simple_smtp_dkim_dkim_domain', '');
$dns_ok       = (bool) get_option('simple_smtp_dkim_dns_verified', false);
$mailer_url   = admin_url('options-general.php?page=simple-smtp-dkim&tab=mailer');
$dkim_url     = admin_url('options-general.php?page=simple-smtp-dkim&tab=dkim');
$logs_url     = admin_url('options-general.php?page=simple-smtp-dkim&tab=logs');

$steps = array(
    array(
        'done'  => $host !== '',
        'label' => __('Configure the sending server', 'simple-smtp-dkim'),
        'desc'  => $host !== '' ? $host . ':' . $port : __('No server configured', 'simple-smtp-dkim'),
        'link'  => $mailer_url,
        'mono'  => $host !== '',
    ),
    array(
        'done'  => $test_ok,
        'label' => __('Pass a test send', 'simple-smtp-dkim'),
        'desc'  => $test_ok ? __('Last test succeeded', 'simple-smtp-dkim') : __('Not tested yet', 'simple-smtp-dkim'),
        'link'  => $mailer_url,
        'mono'  => false,
    ),
    array(
        'done'  => $dkim_enabled,
        'label' => __('Enable DKIM signing', 'simple-smtp-dkim'),
        /* translators: %s: domain name */
        'desc'  => $dkim_enabled ? sprintf(__('Domain %s', 'simple-smtp-dkim'), $dkim_domain) : __('DKIM disabled', 'simple-smtp-dkim'),
        'link'  => $dkim_url,
        'mono'  => false,
    ),
    array(
        'done'  => $dns_ok,
        'label' => __('Verify the DNS record', 'simple-smtp-dkim'),
        'desc'  => $dns_ok ? __('DNS verified', 'simple-smtp-dkim') : __('Not verified yet', 'simple-smtp-dkim'),
        'link'  => $dkim_url,
        'mono'  => false,
    ),
);
$done_count = count(array_filter(array_column($steps, 'done')));
$total      = count($steps);
$pct        = $total > 0 ? (int) round(($done_count / $total) * 100) : 0;
$fully_ok   = $enabled && $host !== '' && $test_ok;

// SVG progress ring: r=28, stroke 6
$ring_r = 28;
$ring_c = 2 * M_PI * $ring_r;
$ring_offset = $ring_c - ($ring_c * $pct) / 100;
?>

<?php if ($fully_ok): ?>
    <div class="ssd-banner ok">
        <?php Simple_SMTP_DKIM_Helpers::icon('check', 19); ?>
        <div>
            <strong><?php esc_html_e('Everything is operational.', 'simple-smtp-dkim'); ?></strong>
            <?php
            if ($dkim_enabled && $dns_ok) {
                esc_html_e('Your site sends its emails over SMTP with active DKIM signing.', 'simple-smtp-dkim');
            } else {
                esc_html_e('Your site sends its emails over SMTP.', 'simple-smtp-dkim');
            }
            ?>
        </div>
    </div>
<?php else: ?>
    <div class="ssd-banner warn">
        <?php Simple_SMTP_DKIM_Helpers::icon('alert', 19); ?>
        <div>
            <strong><?php esc_html_e('Setup incomplete.', 'simple-smtp-dkim'); ?></strong>
            <?php esc_html_e('Finish the steps below to make your email delivery reliable.', 'simple-smtp-dkim'); ?>
        </div>
    </div>
<?php endif; ?>

<!-- Setup progress -->
<div class="ssd-card">
    <div class="ssd-card-head">
        <?php Simple_SMTP_DKIM_Helpers::icon('gauge', 18); ?>
        <h2><?php esc_html_e('Setup progress', 'simple-smtp-dkim'); ?></h2>
        <span class="ssd-spacer"></span>
        <button type="button" class="ssd-btn ssd-btn-soft ssd-btn-sm" id="ssd-open-wizard">
            <?php Simple_SMTP_DKIM_Helpers::icon('wand', 14); ?><?php esc_html_e('Guided wizard', 'simple-smtp-dkim'); ?>
        </button>
    </div>

    <div class="ssd-progress-wrap">
        <div class="ssd-progress-ring" role="progressbar" aria-valuenow="<?php echo esc_attr($pct); ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?php esc_attr_e('Setup progress', 'simple-smtp-dkim'); ?>">
            <svg width="66" height="66" viewBox="0 0 66 66" aria-hidden="true">
                <circle cx="33" cy="33" r="<?php echo esc_attr($ring_r); ?>" fill="none" stroke="#e5e8ee" stroke-width="6"></circle>
                <circle class="ssd-pr-arc" cx="33" cy="33" r="<?php echo esc_attr($ring_r); ?>" fill="none" stroke="#0d9488" stroke-width="6"
                        stroke-linecap="round" stroke-dasharray="<?php echo esc_attr(round($ring_c, 2)); ?>"
                        stroke-dashoffset="<?php echo esc_attr(round($ring_offset, 2)); ?>"
                        transform="rotate(-90 33 33)"></circle>
            </svg>
            <span class="ssd-pr-num"><?php echo esc_html($pct); ?>%</span>
        </div>
        <div class="ssd-progress-meta">
            <div class="ssd-pm-title">
                <?php
                /* translators: %1$d: completed steps, %2$d: total steps */
                echo esc_html(sprintf(__('%1$d of %2$d steps completed', 'simple-smtp-dkim'), $done_count, $total));
                ?>
            </div>
            <div class="ssd-pm-sub">
                <?php
                if ($pct === 100) {
                    esc_html_e('Well done — your configuration is complete.', 'simple-smtp-dkim');
                } else {
                    esc_html_e('Keep going for optimal deliverability.', 'simple-smtp-dkim');
                }
                ?>
            </div>
        </div>
    </div>

    <div class="ssd-checklist">
        <?php foreach ($steps as $step): ?>
            <div class="ssd-cl-item <?php echo $step['done'] ? 'done' : 'pending'; ?>">
                <div class="ssd-cl-check">
                    <?php Simple_SMTP_DKIM_Helpers::icon($step['done'] ? 'check' : 'arrow', 15); ?>
                </div>
                <div class="ssd-cl-body">
                    <div class="ssd-cl-title"><?php echo esc_html($step['label']); ?></div>
                    <div class="ssd-cl-desc <?php echo $step['mono'] ? 'ssd-mono' : ''; ?>"><?php echo esc_html($step['desc']); ?></div>
                </div>
                <?php if (!$step['done']): ?>
                    <a class="ssd-btn ssd-btn-ghost ssd-btn-sm" href="<?php echo esc_url($step['link']); ?>">
                        <?php esc_html_e('Configure', 'simple-smtp-dkim'); ?><?php Simple_SMTP_DKIM_Helpers::icon('arrow', 14); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Sending activity -->
<?php if ($logging_on):
    $stats = Simple_SMTP_DKIM_Logger::get_statistics(30);
?>
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
<?php else: ?>
<div class="ssd-card">
    <p class="ssd-muted-note" style="margin:0;">
        <?php Simple_SMTP_DKIM_Helpers::icon('info', 15); ?>
        <span>
            <?php esc_html_e('Logging is disabled.', 'simple-smtp-dkim'); ?>
            <a class="ssd-linkish" href="<?php echo esc_url($logs_url); ?>"><?php esc_html_e('Enable it to see sending statistics.', 'simple-smtp-dkim'); ?></a>
        </span>
    </p>
</div>
<?php endif; ?>

<div class="ssd-grid-2">
    <!-- Current configuration -->
    <div class="ssd-card">
        <div class="ssd-card-head">
            <?php Simple_SMTP_DKIM_Helpers::icon('settings', 18); ?>
            <h2><?php esc_html_e('Current configuration', 'simple-smtp-dkim'); ?></h2>
        </div>
        <table class="ssd-summary">
            <tbody>
                <tr>
                    <th scope="row"><?php esc_html_e('SMTP', 'simple-smtp-dkim'); ?></th>
                    <td><?php Simple_SMTP_DKIM_Helpers::render_badge($enabled); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Server', 'simple-smtp-dkim'); ?></th>
                    <td><span class="ssd-mono"><?php echo esc_html($host !== '' ? $host : '—'); ?></span></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Port / Encryption', 'simple-smtp-dkim'); ?></th>
                    <td><span class="ssd-mono"><?php echo esc_html($port . ' / ' . ($secure !== '' ? strtoupper($secure) : __('NONE', 'simple-smtp-dkim'))); ?></span></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('From', 'simple-smtp-dkim'); ?></th>
                    <td><?php echo esc_html($from_email !== '' ? $from_email : '—'); ?></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('DKIM', 'simple-smtp-dkim'); ?></th>
                    <td>
                        <?php if ($dkim_enabled && $dns_ok): ?>
                            <span class="ssd-badge ok"><span class="ssd-bd"></span><?php esc_html_e('Active', 'simple-smtp-dkim'); ?></span>
                        <?php elseif ($dkim_enabled): ?>
                            <span class="ssd-badge warn"><span class="ssd-bd"></span><?php esc_html_e('Awaiting DNS', 'simple-smtp-dkim'); ?></span>
                        <?php else: ?>
                            <span class="ssd-badge muted"><span class="ssd-bd"></span><?php esc_html_e('Disabled', 'simple-smtp-dkim'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Recent failures -->
    <div class="ssd-card">
        <div class="ssd-card-head">
            <?php Simple_SMTP_DKIM_Helpers::icon('alertc', 18); ?>
            <h2><?php esc_html_e('Recent failures', 'simple-smtp-dkim'); ?></h2>
            <span class="ssd-spacer"></span>
            <a class="ssd-linkish" href="<?php echo esc_url($logs_url); ?>"><?php esc_html_e('All logs', 'simple-smtp-dkim'); ?></a>
        </div>
        <?php
        $recent_errors = $logging_on ? Simple_SMTP_DKIM_Logger::get_recent_errors(3) : array();
        if (!empty($recent_errors)):
        ?>
            <div class="ssd-log-list">
                <?php foreach ($recent_errors as $err): ?>
                    <div class="ssd-log-item" style="cursor:default;grid-template-columns:38px 1fr;">
                        <div class="ssd-log-status-ico err"><?php Simple_SMTP_DKIM_Helpers::icon('x', 16); ?></div>
                        <div class="ssd-log-main">
                            <span class="ssd-log-to"><?php echo esc_html($err['to_email']); ?></span>
                            <div class="ssd-log-subject"><span class="errtxt"><?php echo esc_html($err['error_message']); ?></span></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="ssd-muted-note" style="margin:0;">
                <?php Simple_SMTP_DKIM_Helpers::icon('check', 15); ?>
                <span>
                    <?php
                    if ($logging_on) {
                        esc_html_e('No recent failures — everything is being delivered.', 'simple-smtp-dkim');
                    } else {
                        esc_html_e('Enable logging to track delivery failures here.', 'simple-smtp-dkim');
                    }
                    ?>
                </span>
            </p>
        <?php endif; ?>
    </div>
</div>

<?php include SIMPLE_SMTP_DKIM_PATH . 'includes/admin/partial-wizard.php'; ?>
