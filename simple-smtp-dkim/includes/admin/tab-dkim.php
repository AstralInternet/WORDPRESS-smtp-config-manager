<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables in included file scope.
/**
 * Tab partial: DKIM Settings
 *
 * Layout: Enable + Domain/Selector → Auto-Generate (primary) → Validate → Manual (collapsed)
 *
 * @package Simple_SMTP_DKIM
 */
if (!defined('WPINC')) { die; }

$enabled        = (bool) get_option('simple_smtp_dkim_dkim_enabled', false);
$dns_verified   = (bool) get_option('simple_smtp_dkim_dns_verified', false);
$domain         = get_option('simple_smtp_dkim_dkim_domain', '');
$selector       = get_option('simple_smtp_dkim_dkim_selector', '');
$storage_method = get_option('simple_smtp_dkim_dkim_storage_method', 'database');
$file_path      = get_option('simple_smtp_dkim_dkim_file_path', '');
$public_key     = get_option('simple_smtp_dkim_dkim_public_key', '');
$has_generated  = !empty($public_key);
$has_passphrase = !empty(Simple_SMTP_DKIM_Encryption::get_decrypted_option('simple_smtp_dkim_dkim_passphrase', ''));
$has_db_key     = !empty(get_option('simple_smtp_dkim_dkim_private_key', ''));
?>

<div class="ssd-section">
    <!-- Status banner -->
    <?php if ($enabled && $dns_verified): ?>
        <div class="ssd-banner ok">
            <?php Simple_SMTP_DKIM_Helpers::icon('shieldcheck', 19); ?>
            <div><?php echo wp_kses(__('DKIM is <strong>active</strong> — your outgoing emails are signed.', 'simple-smtp-dkim'), array('strong' => array())); ?></div>
        </div>
    <?php elseif ($enabled): ?>
        <div class="ssd-banner warn">
            <?php Simple_SMTP_DKIM_Helpers::icon('alert', 19); ?>
            <div><?php echo wp_kses(__('DKIM is enabled but <strong>not signing yet</strong>. Complete the DNS validation below.', 'simple-smtp-dkim'), array('strong' => array())); ?></div>
        </div>
    <?php else: ?>
        <div class="ssd-banner info">
            <?php Simple_SMTP_DKIM_Helpers::icon('info', 19); ?>
            <div><?php esc_html_e('DKIM signing greatly improves deliverability. Enable it to get started.', 'simple-smtp-dkim'); ?></div>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="ssd-form" enctype="multipart/form-data">
        <?php wp_nonce_field('simple_smtp_dkim_save_settings', 'simple_smtp_dkim_nonce'); ?>
        <input type="hidden" name="action" value="simple_smtp_dkim_save_settings">
        <input type="hidden" name="tab" value="dkim">

        <!-- 1. DKIM signature -->
        <div class="ssd-card">
            <div class="ssd-card-head">
                <?php Simple_SMTP_DKIM_Helpers::icon('shield', 18); ?>
                <h2><?php esc_html_e('DKIM signature', 'simple-smtp-dkim'); ?></h2>
            </div>
            <?php
            Simple_SMTP_DKIM_Helpers::render_toggle_row(array(
                'name'    => 'simple_smtp_dkim_dkim_enabled',
                'id'      => 'simple_smtp_dkim_dkim_enabled',
                'checked' => $enabled,
                'title'   => __('Enable DKIM signing', 'simple-smtp-dkim'),
                'desc'    => __('Signs outgoing emails. Signing only applies after DNS validation passes.', 'simple-smtp-dkim'),
            ));
            ?>
            <div class="ssd-dkim-fields" style="margin-top:14px;">
                <div class="ssd-field-row">
                    <div class="ssd-field">
                        <label for="simple_smtp_dkim_dkim_domain"><?php esc_html_e('Domain', 'simple-smtp-dkim'); ?> <span class="ssd-req">*</span></label>
                        <input type="text" name="simple_smtp_dkim_dkim_domain" id="simple_smtp_dkim_dkim_domain"
                               value="<?php echo esc_attr($domain); ?>" class="ssd-inp mono" placeholder="example.com">
                    </div>
                    <div class="ssd-field">
                        <label for="simple_smtp_dkim_dkim_selector">
                            <?php esc_html_e('Selector', 'simple-smtp-dkim'); ?> <span class="ssd-req">*</span>
                            <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('Unique identifier for this key. Common values: "default", "mail".', 'simple-smtp-dkim')); ?>
                        </label>
                        <input type="text" name="simple_smtp_dkim_dkim_selector" id="simple_smtp_dkim_dkim_selector"
                               value="<?php echo esc_attr($selector); ?>" class="ssd-inp mono" placeholder="default">
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Generate keys -->
        <div class="ssd-card ssd-dkim-fields">
            <div class="ssd-card-head">
                <?php Simple_SMTP_DKIM_Helpers::icon('key', 18); ?>
                <h2><?php esc_html_e('Generate the DKIM keys', 'simple-smtp-dkim'); ?></h2>
            </div>
            <p class="ssd-lead"><?php esc_html_e('Automatically generate a 2048-bit key pair. You will then add the DNS record at your registrar.', 'simple-smtp-dkim'); ?></p>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <button type="button" class="ssd-btn ssd-btn-primary" id="ssd-generate-dkim">
                    <?php Simple_SMTP_DKIM_Helpers::icon('key', 16); ?>
                    <?php $has_generated ? esc_html_e('Regenerate the keys', 'simple-smtp-dkim') : esc_html_e('Generate the DKIM keys', 'simple-smtp-dkim'); ?>
                </button>
                <?php if ($has_generated): ?>
                    <button type="button" class="ssd-btn ssd-btn-ghost" id="ssd-view-dkim">
                        <?php Simple_SMTP_DKIM_Helpers::icon('eye', 16); ?><?php esc_html_e('View the DNS record', 'simple-smtp-dkim'); ?>
                    </button>
                <?php endif; ?>
            </div>

            <!-- DNS record block (revealed after generation / view) -->
            <div class="ssd-dns-block ssd-hidden" id="ssd-dns-block" role="region" aria-label="<?php esc_attr_e('DNS record', 'simple-smtp-dkim'); ?>">
                <div class="ssd-dns-title">
                    <?php Simple_SMTP_DKIM_Helpers::icon('globe', 16); ?>
                    <?php esc_html_e('Add this TXT record at your DNS registrar', 'simple-smtp-dkim'); ?>
                </div>
                <div class="ssd-dns-row">
                    <div class="ssd-dns-key"><?php esc_html_e('Name', 'simple-smtp-dkim'); ?></div>
                    <div class="ssd-dns-val">
                        <code id="ssd-dns-name"></code>
                        <?php Simple_SMTP_DKIM_Helpers::render_copy_button('ssd-dns-name', __('Copy the DNS record name', 'simple-smtp-dkim')); ?>
                    </div>
                </div>
                <div class="ssd-dns-row">
                    <div class="ssd-dns-key"><?php esc_html_e('Type', 'simple-smtp-dkim'); ?></div>
                    <div class="ssd-dns-val"><code>TXT</code></div>
                </div>
                <div class="ssd-dns-row">
                    <div class="ssd-dns-key"><?php esc_html_e('Value', 'simple-smtp-dkim'); ?></div>
                    <div class="ssd-dns-val">
                        <code id="ssd-dns-value"></code>
                        <?php Simple_SMTP_DKIM_Helpers::render_copy_button('ssd-dns-value', __('Copy the DNS record value', 'simple-smtp-dkim')); ?>
                    </div>
                </div>
            </div>
            <p class="ssd-muted-note ssd-hidden" id="ssd-dns-saved-note" style="margin-top:10px;">
                <?php Simple_SMTP_DKIM_Helpers::icon('check', 14); ?>
                <span><?php esc_html_e('Private key saved automatically (encrypted). Add the DNS record above, then validate below.', 'simple-smtp-dkim'); ?></span>
            </p>

            <?php if ($has_generated): ?>
                <input type="hidden" id="ssd-saved-public-key" value="<?php echo esc_attr($public_key); ?>">
            <?php endif; ?>
        </div>

        <!-- 3. Validate -->
        <div class="ssd-card ssd-dkim-fields">
            <div class="ssd-card-head">
                <?php Simple_SMTP_DKIM_Helpers::icon('shieldcheck', 18); ?>
                <h2><?php esc_html_e('Validate the configuration', 'simple-smtp-dkim'); ?></h2>
            </div>
            <p class="ssd-lead"><?php esc_html_e('Check that the private key is valid and the DNS record is correctly published.', 'simple-smtp-dkim'); ?></p>
            <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                <button type="button" class="ssd-btn ssd-btn-ghost" id="ssd-validate-dkim">
                    <?php Simple_SMTP_DKIM_Helpers::icon('shieldcheck', 16); ?><?php esc_html_e('Validate DKIM', 'simple-smtp-dkim'); ?>
                </button>
                <?php if ($dns_verified): ?>
                    <span class="ssd-badge ok"><?php esc_html_e('DNS verified — signing active', 'simple-smtp-dkim'); ?></span>
                <?php endif; ?>
            </div>
            <div class="ssd-banner ssd-hidden" id="ssd-dkim-result" role="alert" aria-live="assertive" style="margin-top:14px;margin-bottom:0;"></div>
        </div>

        <!-- 4. Advanced / manual (collapsed) -->
        <div class="ssd-card ssd-dkim-fields">
            <details class="ssd-details">
                <summary>
                    <?php Simple_SMTP_DKIM_Helpers::icon('settings', 16); ?>
                    <span><?php esc_html_e('Manual / advanced configuration', 'simple-smtp-dkim'); ?></span>
                    <?php Simple_SMTP_DKIM_Helpers::icon('chevron', 16, 'ssd-chev'); ?>
                </summary>
                <div class="ssd-details-inner">
                    <p class="ssd-muted-note" style="margin-bottom:14px;"><?php esc_html_e('Use this section if you manage your DKIM keys manually (generated elsewhere).', 'simple-smtp-dkim'); ?></p>

                    <div class="ssd-field">
                        <label for="simple_smtp_dkim_dkim_passphrase"><?php esc_html_e('Key passphrase', 'simple-smtp-dkim'); ?></label>
                        <input type="password" name="simple_smtp_dkim_dkim_passphrase" id="simple_smtp_dkim_dkim_passphrase" value=""
                               class="ssd-inp" autocomplete="new-password"
                               placeholder="<?php echo $has_passphrase ? esc_attr__('Saved — leave blank to keep', 'simple-smtp-dkim') : esc_attr__('Passphrase (if the key is encrypted)', 'simple-smtp-dkim'); ?>">
                    </div>

                    <p class="ssd-section-label"><?php esc_html_e('Private key storage', 'simple-smtp-dkim'); ?></p>
                    <div class="ssd-radio-cards cols-2">
                        <label class="ssd-rc <?php echo $storage_method === 'database' ? 'sel' : ''; ?>">
                            <input type="radio" name="simple_smtp_dkim_dkim_storage_method" value="database" <?php checked($storage_method, 'database'); ?>>
                            <span class="ssd-rc-dot" aria-hidden="true"></span>
                            <span>
                                <span class="ssd-rc-title" style="display:block;"><?php esc_html_e('Database', 'simple-smtp-dkim'); ?></span>
                                <span class="ssd-rc-desc" style="display:block;"><?php esc_html_e('AES-256 encrypted', 'simple-smtp-dkim'); ?></span>
                            </span>
                        </label>
                        <label class="ssd-rc <?php echo $storage_method === 'file' ? 'sel' : ''; ?>">
                            <input type="radio" name="simple_smtp_dkim_dkim_storage_method" value="file" <?php checked($storage_method, 'file'); ?>>
                            <span class="ssd-rc-dot" aria-hidden="true"></span>
                            <span>
                                <span class="ssd-rc-title" style="display:block;"><?php esc_html_e('Server file', 'simple-smtp-dkim'); ?></span>
                                <span class="ssd-rc-desc" style="display:block;"><?php esc_html_e('Protected path', 'simple-smtp-dkim'); ?></span>
                            </span>
                        </label>
                    </div>

                    <!-- Database upload -->
                    <div class="ssd-storage-option ssd-storage-database" style="margin-top:16px;<?php echo $storage_method === 'database' ? '' : 'display:none;'; ?>">
                        <div class="ssd-field">
                            <label for="simple_smtp_dkim_dkim_upload"><?php esc_html_e('Upload a private key', 'simple-smtp-dkim'); ?></label>
                            <input type="file" name="simple_smtp_dkim_dkim_upload" id="simple_smtp_dkim_dkim_upload" accept=".pem,.private,.key">
                            <?php if ($has_db_key): ?>
                                <p class="ssd-desc ok" style="display:flex;align-items:center;gap:6px;">
                                    <?php Simple_SMTP_DKIM_Helpers::icon('check', 13); ?>
                                    <span><?php esc_html_e('Key stored (encrypted).', 'simple-smtp-dkim'); ?></span>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- File path -->
                    <div class="ssd-storage-option ssd-storage-file" style="margin-top:16px;<?php echo $storage_method === 'file' ? '' : 'display:none;'; ?>">
                        <div class="ssd-field">
                            <label for="simple_smtp_dkim_dkim_file_path"><?php esc_html_e('File path', 'simple-smtp-dkim'); ?></label>
                            <input type="text" name="simple_smtp_dkim_dkim_file_path" id="simple_smtp_dkim_dkim_file_path"
                                   value="<?php echo esc_attr($file_path); ?>" class="ssd-inp mono" placeholder="/path/to/key.private">
                        </div>
                        <div class="ssd-field">
                            <label for="simple_smtp_dkim_dkim_file_upload"><?php esc_html_e('Or upload', 'simple-smtp-dkim'); ?></label>
                            <input type="file" name="simple_smtp_dkim_dkim_file_upload" id="simple_smtp_dkim_dkim_file_upload" accept=".pem,.private,.key">
                            <?php if (!empty($file_path) && file_exists($file_path)): ?>
                                <p class="ssd-desc ok" style="display:flex;align-items:center;gap:6px;">
                                    <?php Simple_SMTP_DKIM_Helpers::icon('check', 13); ?>
                                    <?php /* translators: %s: file path */ ?>
                                    <span><?php echo esc_html(sprintf(__('File readable: %s', 'simple-smtp-dkim'), $file_path)); ?></span>
                                </p>
                            <?php elseif (!empty($file_path)): ?>
                                <p class="ssd-desc" style="display:flex;align-items:center;gap:6px;color:var(--err-text);">
                                    <?php Simple_SMTP_DKIM_Helpers::icon('alert', 13); ?>
                                    <?php /* translators: %s: file path */ ?>
                                    <span><?php echo esc_html(sprintf(__('File not found: %s', 'simple-smtp-dkim'), $file_path)); ?></span>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <p class="ssd-section-label" style="margin-top:16px;"><?php esc_html_e('Generate manually (OpenSSL)', 'simple-smtp-dkim'); ?></p>
                    <div class="ssd-code-box">
                        <span class="cm"># <?php esc_html_e('Private key', 'simple-smtp-dkim'); ?></span><br>
                        openssl genrsa -out dkim.private 2048<br><br>
                        <span class="cm"># <?php esc_html_e('Public key', 'simple-smtp-dkim'); ?></span><br>
                        openssl rsa -in dkim.private -pubout -out dkim.public
                    </div>
                </div>
            </details>
        </div>

        <div class="ssd-submit-row">
            <button type="submit" class="ssd-btn ssd-btn-primary ssd-btn-lg">
                <?php Simple_SMTP_DKIM_Helpers::icon('check', 16); ?><?php esc_html_e('Save DKIM settings', 'simple-smtp-dkim'); ?>
            </button>
        </div>
    </form>
</div>
