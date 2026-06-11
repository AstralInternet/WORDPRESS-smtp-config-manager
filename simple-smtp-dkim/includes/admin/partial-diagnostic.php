<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables in included file scope.
/**
 * Partial: delivery diagnostic modal (hidden until launched).
 *
 * The 7 steps run against the real AJAX endpoints
 * (simple_smtp_dkim_test_connection / simple_smtp_dkim_send_test_email);
 * admin-script.js drives the animation and fills the notes.
 *
 * @package Simple_SMTP_DKIM
 */
if (!defined('WPINC')) { die; }
?>
<div class="ssd-overlay ssd-hidden" id="ssd-diag-overlay">
    <div class="ssd-modal" role="dialog" aria-modal="true" aria-labelledby="ssd-diag-title" tabindex="-1">
        <div class="ssd-modal-head">
            <div class="ssd-modal-glyph"><?php Simple_SMTP_DKIM_Helpers::icon('rocket', 20); ?></div>
            <div>
                <h3 id="ssd-diag-title"><?php esc_html_e('Delivery diagnostic', 'simple-smtp-dkim'); ?></h3>
                <div class="ssd-mh-sub">
                    <?php esc_html_e('Sending a test message to', 'simple-smtp-dkim'); ?>
                    <strong id="ssd-diag-recipient"></strong>
                </div>
            </div>
            <button type="button" class="ssd-modal-x" data-ssd-close="diag" aria-label="<?php esc_attr_e('Close', 'simple-smtp-dkim'); ?>">
                <?php Simple_SMTP_DKIM_Helpers::icon('x', 18); ?>
            </button>
        </div>

        <div class="ssd-modal-body">
            <div class="ssd-diag-summary ok ssd-hidden" id="ssd-diag-summary-ok" role="status">
                <?php Simple_SMTP_DKIM_Helpers::icon('check', 20); ?>
                <span><?php esc_html_e('Everything works — the test email was delivered successfully.', 'simple-smtp-dkim'); ?></span>
            </div>
            <div class="ssd-diag-summary err ssd-hidden" id="ssd-diag-summary-err" role="alert">
                <?php Simple_SMTP_DKIM_Helpers::icon('alert', 20); ?>
                <span id="ssd-diag-summary-err-text"><?php esc_html_e('The diagnostic found a problem. Review the failing step below.', 'simple-smtp-dkim'); ?></span>
            </div>

            <?php include SIMPLE_SMTP_DKIM_PATH . 'includes/admin/partial-diag-steps.php'; ?>

            <button type="button" class="ssd-linkish ssd-hidden" id="ssd-diag-debug-toggle" aria-expanded="false" style="margin-top:16px;">
                <?php Simple_SMTP_DKIM_Helpers::icon('bug', 15); ?>
                <span class="ssd-lbl-show"><?php esc_html_e('Show the SMTP debug log', 'simple-smtp-dkim'); ?></span>
                <span class="ssd-lbl-hide"><?php esc_html_e('Hide the SMTP debug log', 'simple-smtp-dkim'); ?></span>
            </button>
            <pre class="ssd-debug-pre ssd-hidden" id="ssd-diag-debug" role="log"></pre>
        </div>

        <div class="ssd-modal-foot">
            <button type="button" class="ssd-btn ssd-btn-ghost ssd-hidden" id="ssd-diag-rerun">
                <?php Simple_SMTP_DKIM_Helpers::icon('refresh', 16); ?><?php esc_html_e('Run again', 'simple-smtp-dkim'); ?>
            </button>
            <span class="ssd-spacer"></span>
            <button type="button" class="ssd-btn ssd-btn-ghost" id="ssd-diag-done" data-ssd-close="diag">
                <span class="ssd-lbl-cancel"><?php esc_html_e('Cancel', 'simple-smtp-dkim'); ?></span>
                <span class="ssd-lbl-done"><?php esc_html_e('Done', 'simple-smtp-dkim'); ?></span>
            </button>
        </div>
    </div>
</div>
