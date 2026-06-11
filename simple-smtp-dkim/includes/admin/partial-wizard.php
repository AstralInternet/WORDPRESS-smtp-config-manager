<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables in included file scope.
/**
 * Partial: guided setup wizard modal (hidden until launched from the
 * dashboard). Steps: Provider → Credentials → Test → Done.
 *
 * On completion it submits the regular mailer save handler
 * (admin-post simple_smtp_dkim_save_settings) — no extra endpoint.
 *
 * @package Simple_SMTP_DKIM
 */
if (!defined('WPINC')) { die; }

$ssd_wiz_providers = array(
    array('id' => 'custom',    'name' => __('Custom SMTP', 'simple-smtp-dkim'), 'host' => '',                    'color' => '#5c6470', 'abbr' => '@'),
    array('id' => 'gmail',     'name' => 'Gmail / Workspace',                   'host' => 'smtp.gmail.com',      'color' => '#ea4335', 'abbr' => 'G'),
    array('id' => 'microsoft', 'name' => 'Microsoft 365',                       'host' => 'smtp.office365.com',  'color' => '#0078d4', 'abbr' => 'M'),
    array('id' => 'sendgrid',  'name' => 'SendGrid',                            'host' => 'smtp.sendgrid.net',   'color' => '#1a82e2', 'abbr' => 'S'),
    array('id' => 'mailgun',   'name' => 'Mailgun',                             'host' => 'smtp.mailgun.org',    'color' => '#c02e2e', 'abbr' => 'M'),
    array('id' => 'ses',       'name' => 'Amazon SES',                          'host' => 'email-smtp.us-east-1.amazonaws.com', 'color' => '#ff9900', 'abbr' => 'A'),
);
$ssd_wiz_steps = array(
    __('Provider', 'simple-smtp-dkim'),
    __('Credentials', 'simple-smtp-dkim'),
    __('Test', 'simple-smtp-dkim'),
);
?>
<div class="ssd-overlay ssd-hidden" id="ssd-wizard-overlay">
    <div class="ssd-modal wide" role="dialog" aria-modal="true" aria-labelledby="ssd-wiz-title" tabindex="-1">
        <div class="ssd-modal-head">
            <div class="ssd-modal-glyph grad"><?php Simple_SMTP_DKIM_Helpers::icon('wand', 20); ?></div>
            <div>
                <h3 id="ssd-wiz-title"><?php esc_html_e('Setup wizard', 'simple-smtp-dkim'); ?></h3>
                <div class="ssd-mh-sub"><?php esc_html_e('Configure email sending in a few steps', 'simple-smtp-dkim'); ?></div>
            </div>
            <button type="button" class="ssd-modal-x" data-ssd-close="wizard" aria-label="<?php esc_attr_e('Close', 'simple-smtp-dkim'); ?>">
                <?php Simple_SMTP_DKIM_Helpers::icon('x', 18); ?>
            </button>
        </div>

        <div class="ssd-modal-body">
            <!-- progress dots -->
            <div class="ssd-wiz-progress" id="ssd-wiz-progress">
                <?php foreach ($ssd_wiz_steps as $ssd_i => $ssd_label): ?>
                    <div class="ssd-wiz-dot <?php echo $ssd_i === 0 ? 'active' : ''; ?>" data-wiz-dot="<?php echo (int) $ssd_i; ?>">
                        <div class="ssd-wd-circle">
                            <span class="ssd-wd-num"><?php echo (int) ($ssd_i + 1); ?></span>
                            <?php Simple_SMTP_DKIM_Helpers::icon('check', 15, 'ssd-ico-ok'); ?>
                        </div>
                        <div class="ssd-wd-label"><?php echo esc_html($ssd_label); ?></div>
                    </div>
                    <?php if ($ssd_i < count($ssd_wiz_steps) - 1): ?>
                        <div class="ssd-wiz-bar" data-wiz-bar="<?php echo (int) $ssd_i; ?>"></div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <!-- Pane 0 — provider -->
            <div class="ssd-wiz-pane active" data-wiz-pane="0">
                <p class="ssd-section-label"><?php esc_html_e('Choose your email provider', 'simple-smtp-dkim'); ?></p>
                <div class="ssd-provider-grid">
                    <?php foreach ($ssd_wiz_providers as $ssd_prov): ?>
                        <button type="button" class="ssd-prov" data-provider="<?php echo esc_attr($ssd_prov['id']); ?>" data-host="<?php echo esc_attr($ssd_prov['host']); ?>">
                            <span class="ssd-prov-logo" style="background:<?php echo esc_attr($ssd_prov['color']); ?>"><?php echo esc_html($ssd_prov['abbr']); ?></span>
                            <span>
                                <span class="ssd-prov-name"><?php echo esc_html($ssd_prov['name']); ?></span>
                                <span class="ssd-prov-host" style="display:block;"><?php echo esc_html($ssd_prov['host'] !== '' ? $ssd_prov['host'] : 'smtp.example.com'); ?></span>
                            </span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Pane 1 — credentials -->
            <div class="ssd-wiz-pane" data-wiz-pane="1">
                <p class="ssd-section-label"><?php esc_html_e('Server credentials', 'simple-smtp-dkim'); ?></p>
                <div class="ssd-field">
                    <label for="ssd-wiz-host"><?php esc_html_e('SMTP server', 'simple-smtp-dkim'); ?> <span class="ssd-req">*</span></label>
                    <input type="text" id="ssd-wiz-host" class="ssd-inp mono" placeholder="smtp.example.com" autocomplete="off">
                </div>
                <div class="ssd-field-row">
                    <div class="ssd-field">
                        <label for="ssd-wiz-secure"><?php esc_html_e('Encryption', 'simple-smtp-dkim'); ?></label>
                        <select id="ssd-wiz-secure" class="ssd-sel">
                            <option value="tls" selected><?php esc_html_e('TLS (recommended)', 'simple-smtp-dkim'); ?></option>
                            <option value="ssl">SSL</option>
                            <option value=""><?php esc_html_e('None', 'simple-smtp-dkim'); ?></option>
                        </select>
                    </div>
                    <div class="ssd-field">
                        <label for="ssd-wiz-port"><?php esc_html_e('Port', 'simple-smtp-dkim'); ?> <span class="ssd-req">*</span></label>
                        <input type="number" id="ssd-wiz-port" class="ssd-inp" value="587" min="1" max="65535">
                    </div>
                </div>
                <div class="ssd-field">
                    <label for="ssd-wiz-username"><?php esc_html_e('Username', 'simple-smtp-dkim'); ?> <span class="ssd-req">*</span></label>
                    <input type="text" id="ssd-wiz-username" class="ssd-inp" placeholder="you@example.com" autocomplete="off">
                </div>
                <div class="ssd-field">
                    <label for="ssd-wiz-password"><?php esc_html_e('Password', 'simple-smtp-dkim'); ?> <span class="ssd-req">*</span></label>
                    <input type="password" id="ssd-wiz-password" class="ssd-inp" placeholder="••••••••••••" autocomplete="new-password">
                    <p class="ssd-desc"><?php esc_html_e('Encrypted with AES-256 before storage.', 'simple-smtp-dkim'); ?></p>
                </div>
                <hr class="ssd-hr">
                <p class="ssd-section-label"><?php esc_html_e('From address', 'simple-smtp-dkim'); ?></p>
                <div class="ssd-field-row">
                    <div class="ssd-field">
                        <label for="ssd-wiz-from-email"><?php esc_html_e('From email', 'simple-smtp-dkim'); ?></label>
                        <input type="email" id="ssd-wiz-from-email" class="ssd-inp" value="<?php echo esc_attr(get_option('admin_email')); ?>">
                    </div>
                    <div class="ssd-field">
                        <label for="ssd-wiz-from-name"><?php esc_html_e('From name', 'simple-smtp-dkim'); ?></label>
                        <input type="text" id="ssd-wiz-from-name" class="ssd-inp" value="<?php echo esc_attr(get_option('blogname')); ?>">
                    </div>
                </div>
            </div>

            <!-- Pane 2 — test -->
            <div class="ssd-wiz-pane" data-wiz-pane="2">
                <div class="ssd-wiz-test-intro" id="ssd-wiz-test-intro">
                    <div class="ssd-wt-ring"><?php Simple_SMTP_DKIM_Helpers::icon('rocket', 34); ?></div>
                    <p class="ssd-lead"><?php esc_html_e('We check everything at once: connection, encryption, authentication, SPF, DKIM and a real test send.', 'simple-smtp-dkim'); ?></p>
                    <button type="button" class="ssd-btn ssd-btn-primary ssd-btn-lg" id="ssd-wiz-run-test">
                        <?php Simple_SMTP_DKIM_Helpers::icon('spark', 16); ?><?php esc_html_e('Run the diagnostic', 'simple-smtp-dkim'); ?>
                    </button>
                </div>
                <div class="ssd-hidden" id="ssd-wiz-test-steps">
                    <?php include SIMPLE_SMTP_DKIM_PATH . 'includes/admin/partial-diag-steps.php'; ?>
                </div>
            </div>

            <!-- Pane 3 — done -->
            <div class="ssd-wiz-pane" data-wiz-pane="3">
                <div class="ssd-wiz-success">
                    <div class="ssd-ws-ring"><?php Simple_SMTP_DKIM_Helpers::icon('check', 40); ?></div>
                    <h3><?php esc_html_e('Setup complete 🎉', 'simple-smtp-dkim'); ?></h3>
                    <p>
                        <?php esc_html_e('Your site now sends its emails through your SMTP server. You can also configure DKIM signing to further improve deliverability.', 'simple-smtp-dkim'); ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="ssd-modal-foot">
            <button type="button" class="ssd-btn ssd-btn-ghost ssd-hidden" id="ssd-wiz-prev"><?php esc_html_e('Previous', 'simple-smtp-dkim'); ?></button>
            <span class="ssd-spacer"></span>
            <button type="button" class="ssd-btn ssd-btn-primary" id="ssd-wiz-next" disabled>
                <span id="ssd-wiz-next-label"><?php esc_html_e('Continue', 'simple-smtp-dkim'); ?></span>
                <?php Simple_SMTP_DKIM_Helpers::icon('arrow', 16); ?>
            </button>
            <button type="button" class="ssd-btn ssd-btn-primary ssd-hidden" id="ssd-wiz-finish">
                <?php Simple_SMTP_DKIM_Helpers::icon('check', 16); ?><?php esc_html_e('Go to the dashboard', 'simple-smtp-dkim'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Hidden form: the wizard saves through the regular mailer handler -->
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="ssd-wizard-form" class="ssd-hidden" aria-hidden="true">
    <?php wp_nonce_field('simple_smtp_dkim_save_settings', 'simple_smtp_dkim_nonce'); ?>
    <input type="hidden" name="action" value="simple_smtp_dkim_save_settings">
    <input type="hidden" name="tab" value="mailer">
    <input type="hidden" name="mailer_sub" value="smtp">
    <input type="hidden" name="ssd_wizard" value="1">
    <input type="hidden" name="simple_smtp_dkim_enabled" value="1">
    <input type="hidden" name="simple_smtp_dkim_auth" value="1" id="ssd-wizf-auth">
    <input type="hidden" name="simple_smtp_dkim_host" value="" id="ssd-wizf-host">
    <input type="hidden" name="simple_smtp_dkim_port" value="587" id="ssd-wizf-port">
    <input type="hidden" name="simple_smtp_dkim_secure" value="tls" id="ssd-wizf-secure">
    <input type="hidden" name="simple_smtp_dkim_username" value="" id="ssd-wizf-username">
    <input type="hidden" name="simple_smtp_dkim_password" value="" id="ssd-wizf-password">
    <input type="hidden" name="simple_smtp_dkim_from_email" value="" id="ssd-wizf-from-email">
    <input type="hidden" name="simple_smtp_dkim_from_name" value="" id="ssd-wizf-from-name">
</form>
