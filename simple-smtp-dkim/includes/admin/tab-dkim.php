<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables in included file scope.
/**
 * Tab partial: DKIM Settings (simplified)
 *
 * Layout: Enable + Domain/Selector → Auto-Generate (primary) → Validate → Manual (collapsed)
 *
 * @package Simple_SMTP_DKIM
 */
if (!defined('WPINC')) { die; }

$enabled        = get_option('simple_smtp_dkim_dkim_enabled', false);
$dns_verified   = (bool) get_option('simple_smtp_dkim_dns_verified', false);
$domain         = get_option('simple_smtp_dkim_dkim_domain', '');
$selector       = get_option('simple_smtp_dkim_dkim_selector', '');
$selector       = get_option('simple_smtp_dkim_dkim_selector', '');
$storage_method = get_option('simple_smtp_dkim_dkim_storage_method', 'database');
$file_path      = get_option('simple_smtp_dkim_dkim_file_path', '');
$has_generated  = !empty(get_option('simple_smtp_dkim_dkim_public_key', ''));
$has_passphrase = !empty(Simple_SMTP_DKIM_Encryption::get_decrypted_option('simple_smtp_dkim_dkim_passphrase', ''));
$status         = Simple_SMTP_DKIM_Validator::get_dkim_status();
?>

<div class="simple-smtp-dkim-section">
    <!-- Status Banner -->
    <div class="smtp-status-banner smtp-status-<?php echo esc_attr($status['class']); ?>" role="status">
        <span class="dashicons dashicons-<?php echo esc_attr($status['status'] === 'configured' ? 'yes-alt' : ($status['status'] === 'disabled' ? 'minus' : 'info')); ?>" aria-hidden="true"></span>
        <span><?php echo esc_html($status['message']); ?></span>
    </div>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="simple-smtp-dkim-form" enctype="multipart/form-data">
        <?php wp_nonce_field('simple_smtp_dkim_save_settings', 'simple_smtp_dkim_nonce'); ?>
        <input type="hidden" name="action" value="simple_smtp_dkim_save_settings">
        <input type="hidden" name="tab" value="dkim">

        <!-- 1. Enable + Domain + Selector -->
        <div class="simple-smtp-dkim-card">
            <h2><?php esc_html_e('DKIM Settings', 'simple-smtp-dkim'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="simple_smtp_dkim_dkim_enabled"><?php esc_html_e('Enable DKIM Signing', 'simple-smtp-dkim'); ?></label>
                        <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('Sign outgoing emails with DKIM to improve deliverability. DKIM signing is only applied after DNS validation passes.', 'simple-smtp-dkim')); ?>
                    </th>
                    <td>
                        <?php Simple_SMTP_DKIM_Helpers::render_toggle('simple_smtp_dkim_dkim_enabled', 'simple_smtp_dkim_dkim_enabled', $enabled); ?>
                        <?php if ($enabled && !$dns_verified): ?>
                            <p class="description" style="color:#d63638; margin-top:8px;">
                                <span class="dashicons dashicons-warning" aria-hidden="true" style="font-size:16px;"></span>
                                <?php echo wp_kses( __( 'DKIM is enabled but <strong>not signing emails</strong> yet. Complete the DNS validation below to activate signing.', 'simple-smtp-dkim' ), array( 'strong' => array() ) ); ?>
                            </p>
                        <?php elseif ($enabled && $dns_verified): ?>
                            <p class="description" style="color:#00a32a; margin-top:8px;">
                                <span class="dashicons dashicons-yes-alt" aria-hidden="true" style="font-size:16px;"></span>
                                <?php echo wp_kses( __( 'DKIM is <strong>active</strong> — outgoing emails are being signed.', 'simple-smtp-dkim' ), array( 'strong' => array() ) ); ?>
                            </p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr class="smtp-dkim-field">
                    <th scope="row"><label for="simple_smtp_dkim_dkim_domain"><?php esc_html_e('Domain', 'simple-smtp-dkim'); ?> <abbr title="<?php esc_attr_e('required', 'simple-smtp-dkim'); ?>">*</abbr></label></th>
                    <td><input type="text" name="simple_smtp_dkim_dkim_domain" id="simple_smtp_dkim_dkim_domain" value="<?php echo esc_attr($domain); ?>" class="regular-text" placeholder="example.com"></td>
                </tr>
                <tr class="smtp-dkim-field">
                    <th scope="row">
                        <label for="simple_smtp_dkim_dkim_selector"><?php esc_html_e('Selector', 'simple-smtp-dkim'); ?> <abbr title="<?php esc_attr_e('required', 'simple-smtp-dkim'); ?>">*</abbr></label>
                        <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('Unique identifier for this key. Common values: "default", "mail".', 'simple-smtp-dkim')); ?>
                    </th>
                    <td><input type="text" name="simple_smtp_dkim_dkim_selector" id="simple_smtp_dkim_dkim_selector" value="<?php echo esc_attr($selector); ?>" class="regular-text" placeholder="default"></td>
                </tr>
            </table>
        </div>

        <!-- 2. AUTO-GENERATE (Primary Workflow) -->
        <div class="simple-smtp-dkim-card smtp-dkim-field">
            <h2><?php esc_html_e('Generate DKIM Keys', 'simple-smtp-dkim'); ?></h2>
            <p><?php esc_html_e('Generate a key pair automatically. You will then need to add the DNS record at your domain registrar.', 'simple-smtp-dkim'); ?></p>

            <div id="smtp-dkim-generate-section">
                <button type="button" id="smtp-generate-dkim-keys" class="button button-primary">
                    <span class="dashicons dashicons-admin-network" aria-hidden="true"></span>
                    <?php esc_html_e('Generate DKIM Keys', 'simple-smtp-dkim'); ?>
                </button>

                <?php if ($has_generated): ?>
                <button type="button" id="smtp-view-dkim-keys" class="button button-secondary" style="margin-left:8px;">
                    <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
                    <?php esc_html_e('View Saved DNS Record', 'simple-smtp-dkim'); ?>
                </button>
                <p class="description" style="margin-top:8px;color:#00a32a;">
                    <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
                    <?php esc_html_e('Keys generated. Click "View Saved DNS Record" to see the value.', 'simple-smtp-dkim'); ?>
                </p>
                <?php endif; ?>
            </div>

            <!-- DNS Result Block -->
            <div id="smtp-dkim-generated-result" class="smtp-dkim-dns-block" style="display:none;" role="region" aria-label="<?php esc_attr_e('DNS Record', 'simple-smtp-dkim'); ?>">
                <h3 id="smtp-dkim-result-title"><?php esc_html_e('Copy this DNS record and add it at your registrar:', 'simple-smtp-dkim'); ?></h3>
                <table class="smtp-dns-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Name', 'simple-smtp-dkim'); ?></th>
                        <td>
                            <code id="smtp-dns-record-name"></code>
                            <button type="button" class="button button-small smtp-copy-btn" data-copy-target="smtp-dns-record-name" aria-label="<?php esc_attr_e('Copy DNS record name', 'simple-smtp-dkim'); ?>"><?php esc_html_e('Copy', 'simple-smtp-dkim'); ?></button>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Type', 'simple-smtp-dkim'); ?></th>
                        <td><code>TXT</code></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Value', 'simple-smtp-dkim'); ?></th>
                        <td>
                            <textarea id="smtp-dns-record-value" readonly rows="3" class="large-text code" aria-label="<?php esc_attr_e('DNS record value', 'simple-smtp-dkim'); ?>"></textarea>
                            <button type="button" class="button button-small smtp-copy-btn" data-copy-target="smtp-dns-record-value" aria-label="<?php esc_attr_e('Copy DNS record value', 'simple-smtp-dkim'); ?>"><?php esc_html_e('Copy', 'simple-smtp-dkim'); ?></button>
                        </td>
                    </tr>
                </table>
                <div class="smtp-success-notice">
                    <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
                    <?php esc_html_e('Private key saved automatically. Add the DNS record above, then save and validate.', 'simple-smtp-dkim'); ?>
                </div>
                <button type="button" id="smtp-close-dkim-display" class="button" style="margin-top:10px;"><?php esc_html_e('Close', 'simple-smtp-dkim'); ?></button>
            </div>

            <?php if ($has_generated): ?>
            <input type="hidden" id="smtp-saved-public-key" value="<?php echo esc_attr(get_option('simple_smtp_dkim_dkim_public_key', '')); ?>">
            <input type="hidden" id="smtp-saved-dkim-domain" value="<?php echo esc_attr($domain); ?>">
            <input type="hidden" id="smtp-saved-dkim-selector" value="<?php echo esc_attr($selector); ?>">
            <?php endif; ?>
        </div>

        <!-- 3. Validate -->
        <div class="simple-smtp-dkim-card smtp-dkim-field">
            <h2><?php esc_html_e('Validate Configuration', 'simple-smtp-dkim'); ?></h2>
            <p><?php esc_html_e('Check that your private key is valid and the DNS record is correctly set.', 'simple-smtp-dkim'); ?></p>
            <button type="button" id="smtp-validate-dkim" class="button button-secondary">
                <span class="dashicons dashicons-shield-alt" aria-hidden="true"></span> <?php esc_html_e('Validate DKIM', 'simple-smtp-dkim'); ?>
            </button>
            <div id="smtp-dkim-result" class="smtp-test-result" style="display:none;" role="alert" aria-live="assertive"></div>
        </div>

        <!-- 4. ADVANCED / MANUAL (collapsed) -->
        <div class="simple-smtp-dkim-card smtp-dkim-field">
            <details class="smtp-advanced-details">
                <summary>
                    <h2 class="smtp-inline-heading"><?php esc_html_e('Advanced / Manual Configuration', 'simple-smtp-dkim'); ?></h2>
                </summary>

                <div class="smtp-advanced-inner">
                    <p class="description"><?php esc_html_e('Use this section if you prefer to manage DKIM keys manually (e.g., generated externally).', 'simple-smtp-dkim'); ?></p>

                    <!-- Passphrase -->
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="simple_smtp_dkim_dkim_passphrase"><?php esc_html_e('Key Passphrase', 'simple-smtp-dkim'); ?></label></th>
                            <td>
                                <input type="password" name="simple_smtp_dkim_dkim_passphrase" id="simple_smtp_dkim_dkim_passphrase" value="" class="regular-text" autocomplete="new-password" placeholder="<?php echo $has_passphrase ? esc_attr__('Saved — leave blank to keep', 'simple-smtp-dkim') : esc_attr__('Passphrase (if key is encrypted)', 'simple-smtp-dkim'); ?>">
                                <?php if ($has_passphrase): ?>
                                <p class="description"><span class="dashicons dashicons-yes-alt" style="color:#00a32a" aria-hidden="true"></span> <?php esc_html_e('Passphrase saved.', 'simple-smtp-dkim'); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>

                    <!-- Storage Method -->
                    <h3><?php esc_html_e('Private Key Storage', 'simple-smtp-dkim'); ?></h3>
                    <table class="form-table"><tr>
                        <th scope="row"><?php esc_html_e('Method', 'simple-smtp-dkim'); ?></th>
                        <td>
                            <fieldset><legend class="screen-reader-text"><?php esc_html_e('Storage method', 'simple-smtp-dkim'); ?></legend>
                            <label><input type="radio" name="simple_smtp_dkim_dkim_storage_method" value="database" <?php checked($storage_method, 'database'); ?>> <?php esc_html_e('Database (encrypted)', 'simple-smtp-dkim'); ?></label><br>
                            <label><input type="radio" name="simple_smtp_dkim_dkim_storage_method" value="file" <?php checked($storage_method, 'file'); ?>> <?php esc_html_e('File on server', 'simple-smtp-dkim'); ?></label>
                            </fieldset>
                        </td>
                    </tr></table>

                    <!-- DB upload -->
                    <div class="smtp-storage-option smtp-storage-database" style="<?php echo esc_attr($storage_method === 'database' ? '' : 'display:none;'); ?>">
                        <table class="form-table"><tr>
                            <th scope="row"><label for="simple_smtp_dkim_dkim_upload"><?php esc_html_e('Upload Private Key', 'simple-smtp-dkim'); ?></label></th>
                            <td>
                                <input type="file" name="simple_smtp_dkim_dkim_upload" id="simple_smtp_dkim_dkim_upload" accept=".pem,.private,.key">
                                <?php if (get_option('simple_smtp_dkim_dkim_private_key')): ?>
                                <p class="description"><span class="dashicons dashicons-yes-alt" style="color:#00a32a" aria-hidden="true"></span> <?php esc_html_e('Key stored (encrypted).', 'simple-smtp-dkim'); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr></table>
                    </div>

                    <!-- File path -->
                    <div class="smtp-storage-option smtp-storage-file" style="<?php echo esc_attr($storage_method === 'file' ? '' : 'display:none;'); ?>">
                        <table class="form-table"><tr>
                            <th scope="row"><label for="simple_smtp_dkim_dkim_file_path"><?php esc_html_e('File Path', 'simple-smtp-dkim'); ?></label></th>
                            <td>
                                <input type="text" name="simple_smtp_dkim_dkim_file_path" id="simple_smtp_dkim_dkim_file_path" value="<?php echo esc_attr($file_path); ?>" class="large-text code" placeholder="/path/to/key.private">
                                <p class="description"><?php esc_html_e('Or upload:', 'simple-smtp-dkim'); ?></p>
                                <input type="file" name="simple_smtp_dkim_dkim_file_upload" id="simple_smtp_dkim_dkim_file_upload" accept=".pem,.private,.key">
                                <?php if (!empty($file_path) && file_exists($file_path)): ?>
                                <?php /* translators: %s: file path */ ?>
                                <p class="description"><span class="dashicons dashicons-yes-alt" style="color:#00a32a" aria-hidden="true"></span> <?php echo esc_html(sprintf(__('File readable: %s', 'simple-smtp-dkim'), $file_path)); ?></p>
                                <?php elseif (!empty($file_path)): ?>
                                <?php /* translators: %s: file path */ ?>
                                <p class="description" style="color:#d63638;"><span class="dashicons dashicons-warning" aria-hidden="true"></span> <?php echo esc_html(sprintf(__('File not found: %s', 'simple-smtp-dkim'), $file_path)); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr></table>
                    </div>

                    <!-- How to generate manually -->
                    <details class="smtp-advanced-details" style="margin-top:15px;">
                        <summary><?php esc_html_e('How to generate keys manually (OpenSSL)', 'simple-smtp-dkim'); ?></summary>
                        <div class="smtp-code-block" style="margin-top:10px;">
                            <code>
                                # <?php esc_html_e('Generate private key', 'simple-smtp-dkim'); ?><br>
                                openssl genrsa -out dkim.private 2048<br><br>
                                # <?php esc_html_e('Extract public key', 'simple-smtp-dkim'); ?><br>
                                openssl rsa -in dkim.private -pubout -out dkim.public
                            </code>
                        </div>
                    </details>
                </div>
            </details>
        </div>

        <p class="submit">
            <button type="submit" class="button button-primary button-large"><?php esc_html_e('Save DKIM Settings', 'simple-smtp-dkim'); ?></button>
        </p>
    </form>
</div>
