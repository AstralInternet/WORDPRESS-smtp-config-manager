<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables in included file scope.
/**
 * Tab partial: SMTP Settings
 * @package Simple_SMTP_DKIM
 */
if (!defined('WPINC')) { die; }

$enabled    = get_option('simple_smtp_dkim_enabled', false);
$host       = get_option('simple_smtp_dkim_host', '');
$port       = get_option('simple_smtp_dkim_port', 587);
$secure     = get_option('simple_smtp_dkim_secure', 'tls');
$auth       = get_option('simple_smtp_dkim_auth', true);
$username   = get_option('simple_smtp_dkim_username', '');
$from_email = get_option('simple_smtp_dkim_from_email', get_option('admin_email'));
$from_name  = get_option('simple_smtp_dkim_from_name', get_option('blogname'));
$force_from = get_option('simple_smtp_dkim_force_from', false);
$has_pw     = !empty(Simple_SMTP_DKIM_Encryption::get_decrypted_option('simple_smtp_dkim_password', ''));
$status     = Simple_SMTP_DKIM_Validator::get_connection_status();
?>

<div class="simple-smtp-dkim-section">
    <!-- Status Banner -->
    <div class="smtp-status-banner smtp-status-<?php echo esc_attr($status['class']); ?>" role="status">
        <span class="dashicons dashicons-<?php echo $status['status'] === 'configured' ? 'yes-alt' : ($status['status'] === 'disabled' ? 'warning' : 'info'); ?>" aria-hidden="true"></span>
        <span><?php echo esc_html($status['message']); ?></span>
    </div>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="simple-smtp-dkim-form" id="smtp-settings-form" novalidate>
        <?php wp_nonce_field('simple_smtp_dkim_save_settings', 'simple_smtp_dkim_nonce'); ?>
        <input type="hidden" name="action" value="simple_smtp_dkim_save_settings">
        <input type="hidden" name="tab" value="smtp">

        <!-- Enable SMTP -->
        <div class="simple-smtp-dkim-card">
            <h2><?php esc_html_e('Enable SMTP', 'simple-smtp-dkim'); ?></h2>
            <table class="form-table"><tr>
                <th scope="row">
                    <label for="simple_smtp_dkim_enabled"><?php esc_html_e('Enable SMTP', 'simple-smtp-dkim'); ?></label>
                    <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('Send emails via your SMTP server instead of PHP mail().', 'simple-smtp-dkim')); ?>
                </th>
                <td><?php Simple_SMTP_DKIM_Helpers::render_toggle('simple_smtp_dkim_enabled', 'simple_smtp_dkim_enabled', $enabled); ?></td>
            </tr></table>
        </div>

        <!-- Server Settings -->
        <div class="simple-smtp-dkim-card">
            <h2><?php esc_html_e('Server Settings', 'simple-smtp-dkim'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="simple_smtp_dkim_host"><?php esc_html_e('SMTP Host', 'simple-smtp-dkim'); ?> <abbr title="<?php esc_attr_e('required', 'simple-smtp-dkim'); ?>">*</abbr></label></th>
                    <td>
                        <input type="text" name="simple_smtp_dkim_host" id="simple_smtp_dkim_host" value="<?php echo esc_attr($host); ?>" class="regular-text" placeholder="smtp.example.com" required data-validate="host" aria-describedby="host-feedback">
                        <span class="smtp-field-feedback" id="host-feedback" aria-live="polite"></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="simple_smtp_dkim_secure"><?php esc_html_e('Encryption', 'simple-smtp-dkim'); ?></label>
                        <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('Port auto-adjusts: TLS→587, SSL→465, None→25', 'simple-smtp-dkim')); ?>
                    </th>
                    <td>
                        <select name="simple_smtp_dkim_secure" id="simple_smtp_dkim_secure">
                            <option value="tls" <?php selected($secure, 'tls'); ?>>TLS (<?php esc_html_e('Recommended', 'simple-smtp-dkim'); ?>)</option>
                            <option value="ssl" <?php selected($secure, 'ssl'); ?>>SSL</option>
                            <option value="" <?php selected($secure, ''); ?>><?php esc_html_e('None', 'simple-smtp-dkim'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="simple_smtp_dkim_port"><?php esc_html_e('Port', 'simple-smtp-dkim'); ?> <abbr title="<?php esc_attr_e('required', 'simple-smtp-dkim'); ?>">*</abbr></label></th>
                    <td>
                        <input type="number" name="simple_smtp_dkim_port" id="simple_smtp_dkim_port" value="<?php echo esc_attr($port); ?>" class="small-text" min="1" max="65535" required data-validate="port" aria-describedby="port-feedback">
                        <span class="smtp-field-feedback" id="port-feedback" aria-live="polite"></span>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Authentication -->
        <div class="simple-smtp-dkim-card">
            <h2><?php esc_html_e('Authentication', 'simple-smtp-dkim'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="simple_smtp_dkim_auth"><?php esc_html_e('Use Authentication', 'simple-smtp-dkim'); ?></label></th>
                    <td><?php Simple_SMTP_DKIM_Helpers::render_toggle('simple_smtp_dkim_auth', 'simple_smtp_dkim_auth', $auth); ?></td>
                </tr>
                <tr class="smtp-auth-field">
                    <th scope="row"><label for="simple_smtp_dkim_username"><?php esc_html_e('Username', 'simple-smtp-dkim'); ?> <abbr title="<?php esc_attr_e('required', 'simple-smtp-dkim'); ?>">*</abbr></label></th>
                    <td>
                        <input type="text" name="simple_smtp_dkim_username" id="simple_smtp_dkim_username" value="<?php echo esc_attr($username); ?>" class="regular-text" autocomplete="off" data-validate="required" aria-describedby="username-feedback">
                        <span class="smtp-field-feedback" id="username-feedback" aria-live="polite"></span>
                    </td>
                </tr>
                <tr class="smtp-auth-field">
                    <th scope="row"><label for="simple_smtp_dkim_password"><?php esc_html_e('Password', 'simple-smtp-dkim'); ?> <abbr title="<?php esc_attr_e('required', 'simple-smtp-dkim'); ?>">*</abbr></label></th>
                    <td>
                        <input type="password" name="simple_smtp_dkim_password" id="simple_smtp_dkim_password" value="" class="regular-text" autocomplete="new-password" placeholder="<?php echo $has_pw ? esc_attr__('Saved — leave blank to keep', 'simple-smtp-dkim') : esc_attr__('Enter SMTP password', 'simple-smtp-dkim'); ?>" data-has-saved-password="<?php echo $has_pw ? '1' : '0'; ?>" aria-describedby="password-desc">
                        <p class="description" id="password-desc">
                            <?php if ($has_pw): ?>
                                <span class="dashicons dashicons-yes-alt" style="color:#00a32a" aria-hidden="true"></span> <?php esc_html_e('Saved.', 'simple-smtp-dkim'); ?>
                            <?php endif; ?>
                            <?php if (Simple_SMTP_DKIM_Encryption::is_encryption_available()): ?>
                                <span class="dashicons dashicons-lock" style="color:#00a32a" aria-hidden="true"></span> <?php esc_html_e('AES-256-CBC encrypted', 'simple-smtp-dkim'); ?>
                            <?php else: ?>
                                <span class="dashicons dashicons-warning" style="color:#d63638" aria-hidden="true"></span> <?php esc_html_e('Encryption unavailable — stored as plain text', 'simple-smtp-dkim'); ?>
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- From Address -->
        <div class="simple-smtp-dkim-card">
            <h2><?php esc_html_e('From Address', 'simple-smtp-dkim'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="simple_smtp_dkim_from_email"><?php esc_html_e('Email', 'simple-smtp-dkim'); ?></label></th>
                    <td>
                        <input type="email" name="simple_smtp_dkim_from_email" id="simple_smtp_dkim_from_email" value="<?php echo esc_attr($from_email); ?>" class="regular-text" data-validate="email" aria-describedby="from-email-feedback">
                        <span class="smtp-field-feedback" id="from-email-feedback" aria-live="polite"></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="simple_smtp_dkim_from_name"><?php esc_html_e('Name', 'simple-smtp-dkim'); ?></label></th>
                    <td><input type="text" name="simple_smtp_dkim_from_name" id="simple_smtp_dkim_from_name" value="<?php echo esc_attr($from_name); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="simple_smtp_dkim_force_from"><?php esc_html_e('Force From', 'simple-smtp-dkim'); ?></label>
                        <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('Override the From address set by other plugins/themes.', 'simple-smtp-dkim')); ?>
                    </th>
                    <td><?php Simple_SMTP_DKIM_Helpers::render_toggle('simple_smtp_dkim_force_from', 'simple_smtp_dkim_force_from', $force_from); ?></td>
                </tr>
            </table>
        </div>

        <!-- Test Area (inline) -->
        <div class="simple-smtp-dkim-card">
            <h2><?php esc_html_e('Test Your Configuration', 'simple-smtp-dkim'); ?></h2>
            <div class="smtp-test-row">
                <button type="button" id="smtp-test-connection" class="button button-secondary">
                    <span class="dashicons dashicons-update-alt" aria-hidden="true"></span> <?php esc_html_e('Test Connection', 'simple-smtp-dkim'); ?>
                </button>
                <div class="smtp-test-email-inline">
                    <label for="smtp_test_email_to" class="screen-reader-text"><?php esc_html_e('Test email recipient', 'simple-smtp-dkim'); ?></label>
                    <input type="email" id="smtp_test_email_to" class="regular-text" value="<?php echo esc_attr(get_option('admin_email')); ?>" placeholder="email@example.com" aria-label="<?php esc_attr_e('Test email recipient', 'simple-smtp-dkim'); ?>">
                    <button type="button" id="smtp-send-test-email" class="button button-primary">
                        <span class="dashicons dashicons-email-alt" aria-hidden="true"></span> <?php esc_html_e('Send Test Email', 'simple-smtp-dkim'); ?>
                    </button>
                </div>
            </div>
            <div id="smtp-test-result" class="smtp-test-result" style="display:none;" role="alert" aria-live="assertive"></div>
            <div id="smtp-test-debug" class="smtp-test-debug" style="display:none;">
                <button type="button" class="smtp-debug-toggle" aria-expanded="false"><?php esc_html_e('Show Debug Info', 'simple-smtp-dkim'); ?></button>
                <pre class="smtp-debug-content" role="log"></pre>
            </div>
        </div>

        <p class="submit">
            <button type="submit" class="button button-primary button-large"><?php esc_html_e('Save SMTP Settings', 'simple-smtp-dkim'); ?></button>
        </p>
    </form>
</div>
