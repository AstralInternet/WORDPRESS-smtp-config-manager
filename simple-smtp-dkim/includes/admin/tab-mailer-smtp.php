<?php
/**
 * Mailer sub-tab: SMTP (classic username/password)
 * @package Simple_SMTP_DKIM
 */
if (!defined('WPINC')) { die; }

$host     = get_option('simple_smtp_dkim_host', '');
$port     = get_option('simple_smtp_dkim_port', 587);
$secure   = get_option('simple_smtp_dkim_secure', 'tls');
$auth     = get_option('simple_smtp_dkim_auth', true);
$username = get_option('simple_smtp_dkim_username', '');
$has_pw   = !empty(Simple_SMTP_DKIM_Encryption::get_decrypted_option('simple_smtp_dkim_password', ''));
?>

<!-- Server Settings -->
<div class="simple-smtp-dkim-card">
    <h2><?php _e('Server Settings', 'simple-smtp-dkim'); ?></h2>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="simple_smtp_dkim_host"><?php _e('SMTP Host', 'simple-smtp-dkim'); ?> <abbr title="<?php esc_attr_e('required', 'simple-smtp-dkim'); ?>">*</abbr></label></th>
            <td>
                <input type="text" name="simple_smtp_dkim_host" id="simple_smtp_dkim_host" value="<?php echo esc_attr($host); ?>" class="regular-text" placeholder="smtp.example.com" required data-validate="host" aria-describedby="host-feedback">
                <span class="smtp-field-feedback" id="host-feedback" aria-live="polite"></span>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="simple_smtp_dkim_secure"><?php _e('Encryption', 'simple-smtp-dkim'); ?></label>
                <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('Port auto-adjusts: TLS→587, SSL→465, None→25', 'simple-smtp-dkim')); ?>
            </th>
            <td>
                <select name="simple_smtp_dkim_secure" id="simple_smtp_dkim_secure">
                    <option value="tls" <?php selected($secure, 'tls'); ?>>TLS (<?php _e('Recommended', 'simple-smtp-dkim'); ?>)</option>
                    <option value="ssl" <?php selected($secure, 'ssl'); ?>>SSL</option>
                    <option value="" <?php selected($secure, ''); ?>><?php _e('None', 'simple-smtp-dkim'); ?></option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="simple_smtp_dkim_port"><?php _e('Port', 'simple-smtp-dkim'); ?> <abbr title="<?php esc_attr_e('required', 'simple-smtp-dkim'); ?>">*</abbr></label></th>
            <td>
                <input type="number" name="simple_smtp_dkim_port" id="simple_smtp_dkim_port" value="<?php echo esc_attr($port); ?>" class="small-text" min="1" max="65535" required data-validate="port" aria-describedby="port-feedback">
                <span class="smtp-field-feedback" id="port-feedback" aria-live="polite"></span>
            </td>
        </tr>
    </table>
</div>

<!-- Authentication -->
<div class="simple-smtp-dkim-card">
    <h2><?php _e('Authentication', 'simple-smtp-dkim'); ?></h2>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="simple_smtp_dkim_auth"><?php _e('Use Authentication', 'simple-smtp-dkim'); ?></label></th>
            <td><?php Simple_SMTP_DKIM_Helpers::render_toggle('simple_smtp_dkim_auth', 'simple_smtp_dkim_auth', $auth); ?></td>
        </tr>
        <tr class="smtp-auth-field">
            <th scope="row"><label for="simple_smtp_dkim_username"><?php _e('Username', 'simple-smtp-dkim'); ?> <abbr title="<?php esc_attr_e('required', 'simple-smtp-dkim'); ?>">*</abbr></label></th>
            <td>
                <input type="text" name="simple_smtp_dkim_username" id="simple_smtp_dkim_username" value="<?php echo esc_attr($username); ?>" class="regular-text" autocomplete="off" data-validate="required" aria-describedby="username-feedback">
                <span class="smtp-field-feedback" id="username-feedback" aria-live="polite"></span>
            </td>
        </tr>
        <tr class="smtp-auth-field">
            <th scope="row"><label for="simple_smtp_dkim_password"><?php _e('Password', 'simple-smtp-dkim'); ?> <abbr title="<?php esc_attr_e('required', 'simple-smtp-dkim'); ?>">*</abbr></label></th>
            <td>
                <input type="password" name="simple_smtp_dkim_password" id="simple_smtp_dkim_password" value="" class="regular-text" autocomplete="new-password" placeholder="<?php echo $has_pw ? esc_attr__('Saved — leave blank to keep', 'simple-smtp-dkim') : esc_attr__('Enter SMTP password', 'simple-smtp-dkim'); ?>" data-has-saved-password="<?php echo $has_pw ? '1' : '0'; ?>" aria-describedby="password-desc">
                <p class="description" id="password-desc">
                    <?php if ($has_pw): ?>
                        <span class="dashicons dashicons-yes-alt" style="color:#00a32a" aria-hidden="true"></span> <?php _e('Saved.', 'simple-smtp-dkim'); ?>
                    <?php endif; ?>
                    <?php if (Simple_SMTP_DKIM_Encryption::is_encryption_available()): ?>
                        <span class="dashicons dashicons-lock" style="color:#00a32a" aria-hidden="true"></span> <?php _e('AES-256-CBC encrypted', 'simple-smtp-dkim'); ?>
                    <?php else: ?>
                        <span class="dashicons dashicons-warning" style="color:#d63638" aria-hidden="true"></span> <?php _e('Encryption unavailable — stored as plain text', 'simple-smtp-dkim'); ?>
                    <?php endif; ?>
                </p>
            </td>
        </tr>
    </table>
</div>
