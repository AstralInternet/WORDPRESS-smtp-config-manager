<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables in included file scope.
/**
 * Mailer sub-tab: SMTP (classic username/password)
 * @package Simple_SMTP_DKIM
 */
if (!defined('WPINC')) { die; }

$host     = get_option('simple_smtp_dkim_host', '');
$port     = get_option('simple_smtp_dkim_port', 587);
$secure   = get_option('simple_smtp_dkim_secure', 'tls');
$auth     = (bool) get_option('simple_smtp_dkim_auth', true);
$username = get_option('simple_smtp_dkim_username', '');
$has_pw   = !empty(Simple_SMTP_DKIM_Encryption::get_decrypted_option('simple_smtp_dkim_password', ''));
?>

<div class="ssd-grid-2">
    <!-- Server -->
    <div class="ssd-card">
        <div class="ssd-card-head">
            <?php Simple_SMTP_DKIM_Helpers::icon('server', 18); ?>
            <h2><?php esc_html_e('Server', 'simple-smtp-dkim'); ?></h2>
        </div>
        <div class="ssd-field">
            <label for="simple_smtp_dkim_host">
                <?php esc_html_e('SMTP host', 'simple-smtp-dkim'); ?> <span class="ssd-req">*</span>
                <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('The address of your sending server, e.g. smtp.gmail.com', 'simple-smtp-dkim')); ?>
            </label>
            <input type="text" name="simple_smtp_dkim_host" id="simple_smtp_dkim_host" value="<?php echo esc_attr($host); ?>"
                   class="ssd-inp mono" placeholder="smtp.example.com" required data-validate="host" aria-describedby="ssd-host-feedback">
            <span class="ssd-inp-feedback" id="ssd-host-feedback" aria-live="polite"></span>
        </div>
        <div class="ssd-field-row">
            <div class="ssd-field">
                <label for="simple_smtp_dkim_secure">
                    <?php esc_html_e('Encryption', 'simple-smtp-dkim'); ?>
                    <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('The port adjusts automatically: TLS→587, SSL→465, None→25', 'simple-smtp-dkim')); ?>
                </label>
                <select name="simple_smtp_dkim_secure" id="simple_smtp_dkim_secure" class="ssd-sel">
                    <option value="tls" <?php selected($secure, 'tls'); ?>><?php esc_html_e('TLS (recommended)', 'simple-smtp-dkim'); ?></option>
                    <option value="ssl" <?php selected($secure, 'ssl'); ?>>SSL</option>
                    <option value="" <?php selected($secure, ''); ?>><?php esc_html_e('None', 'simple-smtp-dkim'); ?></option>
                </select>
            </div>
            <div class="ssd-field">
                <label for="simple_smtp_dkim_port"><?php esc_html_e('Port', 'simple-smtp-dkim'); ?> <span class="ssd-req">*</span></label>
                <input type="number" name="simple_smtp_dkim_port" id="simple_smtp_dkim_port" value="<?php echo esc_attr($port); ?>"
                       class="ssd-inp" min="1" max="65535" required data-validate="port" aria-describedby="ssd-port-feedback">
                <span class="ssd-inp-feedback" id="ssd-port-feedback" aria-live="polite"></span>
            </div>
        </div>
    </div>

    <!-- Authentication -->
    <div class="ssd-card">
        <div class="ssd-card-head">
            <?php Simple_SMTP_DKIM_Helpers::icon('key', 18); ?>
            <h2><?php esc_html_e('Authentication', 'simple-smtp-dkim'); ?></h2>
        </div>
        <?php
        Simple_SMTP_DKIM_Helpers::render_toggle_row(array(
            'name'    => 'simple_smtp_dkim_auth',
            'id'      => 'simple_smtp_dkim_auth',
            'checked' => $auth,
            'title'   => __('Use authentication', 'simple-smtp-dkim'),
        ));
        ?>
        <div class="ssd-auth-fields" style="margin-top:14px;">
            <div class="ssd-field">
                <label for="simple_smtp_dkim_username"><?php esc_html_e('Username', 'simple-smtp-dkim'); ?> <span class="ssd-req">*</span></label>
                <input type="text" name="simple_smtp_dkim_username" id="simple_smtp_dkim_username" value="<?php echo esc_attr($username); ?>"
                       class="ssd-inp" placeholder="you@example.com" autocomplete="off" data-validate="required" aria-describedby="ssd-username-feedback">
                <span class="ssd-inp-feedback" id="ssd-username-feedback" aria-live="polite"></span>
            </div>
            <div class="ssd-field">
                <label for="simple_smtp_dkim_password"><?php esc_html_e('Password', 'simple-smtp-dkim'); ?> <span class="ssd-req">*</span></label>
                <input type="password" name="simple_smtp_dkim_password" id="simple_smtp_dkim_password" value="" class="ssd-inp"
                       autocomplete="new-password"
                       placeholder="<?php echo $has_pw ? esc_attr__('•••••••• (saved)', 'simple-smtp-dkim') : esc_attr__('Enter the SMTP password', 'simple-smtp-dkim'); ?>"
                       data-has-saved-password="<?php echo $has_pw ? '1' : '0'; ?>" aria-describedby="ssd-password-desc">
                <p class="ssd-desc <?php echo Simple_SMTP_DKIM_Encryption::is_encryption_available() ? 'ok' : ''; ?>" id="ssd-password-desc" style="display:flex;align-items:center;gap:6px;">
                    <?php if (Simple_SMTP_DKIM_Encryption::is_encryption_available()): ?>
                        <?php Simple_SMTP_DKIM_Helpers::icon('lock', 13); ?>
                        <span><?php esc_html_e('Encrypted with AES-256-CBC', 'simple-smtp-dkim'); ?></span>
                    <?php else: ?>
                        <?php Simple_SMTP_DKIM_Helpers::icon('alert', 13); ?>
                        <span style="color:var(--err-text);"><?php esc_html_e('Encryption unavailable — stored as plain text', 'simple-smtp-dkim'); ?></span>
                    <?php endif; ?>
                </p>
                <?php if ($has_pw): ?>
                    <p class="ssd-desc"><?php esc_html_e('Leave blank to keep the saved password.', 'simple-smtp-dkim'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
