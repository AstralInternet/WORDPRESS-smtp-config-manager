<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables in included file scope.
/**
 * Partial: the 7 animated diagnostic steps (shared by the diagnostic
 * modal and the setup wizard test pane).
 *
 * Contains no element ids so it can be included more than once per page;
 * JavaScript scopes its queries to the surrounding container.
 *
 * @package Simple_SMTP_DKIM
 */
if (!defined('WPINC')) { die; }

$ssd_diag_steps = array(
    array('key' => 'resolve', 'icon' => 'globe',       'title' => __('Server DNS resolution', 'simple-smtp-dkim')),
    array('key' => 'connect', 'icon' => 'plug',        'title' => __('Connection to the server', 'simple-smtp-dkim')),
    array('key' => 'tls',     'icon' => 'lock',        'title' => __('TLS encryption negotiation', 'simple-smtp-dkim')),
    array('key' => 'auth',    'icon' => 'key',         'title' => __('SMTP authentication', 'simple-smtp-dkim')),
    array('key' => 'spf',     'icon' => 'shield',      'title' => __('SPF check', 'simple-smtp-dkim')),
    array('key' => 'dkim',    'icon' => 'shieldcheck', 'title' => __('DKIM signature', 'simple-smtp-dkim')),
    array('key' => 'send',    'icon' => 'send',        'title' => __('Sending the test email', 'simple-smtp-dkim')),
);
?>
<div class="ssd-diag-steps" aria-live="polite">
    <?php foreach ($ssd_diag_steps as $ssd_step): ?>
    <div class="ssd-diag-step idle" data-step="<?php echo esc_attr($ssd_step['key']); ?>">
        <div class="ssd-ds-icon">
            <span class="ssd-ico-step"><?php Simple_SMTP_DKIM_Helpers::icon($ssd_step['icon'], 17); ?></span>
            <span class="ssd-spin ssd-ico-spin" aria-hidden="true"></span>
            <?php Simple_SMTP_DKIM_Helpers::icon('check', 17, 'ssd-ico-ok'); ?>
            <?php Simple_SMTP_DKIM_Helpers::icon('x', 17, 'ssd-ico-err'); ?>
        </div>
        <div class="ssd-ds-body">
            <div class="ssd-ds-title"><?php echo esc_html($ssd_step['title']); ?></div>
            <div class="ssd-ds-note"><?php esc_html_e('Waiting…', 'simple-smtp-dkim'); ?></div>
        </div>
        <span class="ssd-badge ok"><?php esc_html_e('OK', 'simple-smtp-dkim'); ?></span>
        <span class="ssd-badge warn"><?php esc_html_e('Warning', 'simple-smtp-dkim'); ?></span>
        <span class="ssd-badge err"><?php esc_html_e('Failed', 'simple-smtp-dkim'); ?></span>
    </div>
    <?php endforeach; ?>
</div>
