<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables in included file scope.
/**
 * Tab partial: Advanced Settings
 * @package Simple_SMTP_DKIM
 */
if (!defined('WPINC')) { die; }

$debug_mode          = (bool) get_option('simple_smtp_dkim_debug_mode', false);
$delete_on_uninstall = (bool) get_option('simple_smtp_dkim_delete_on_uninstall', false);
$enc                 = Simple_SMTP_DKIM_Encryption::get_encryption_info();
$key_in_config       = (!defined('SIMPLE_SMTP_DKIM_KEY_IN_DB') || !SIMPLE_SMTP_DKIM_KEY_IN_DB);
?>

<div class="ssd-section">
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="ssd-form">
        <?php wp_nonce_field('simple_smtp_dkim_save_settings', 'simple_smtp_dkim_nonce'); ?>
        <input type="hidden" name="action" value="simple_smtp_dkim_save_settings">
        <input type="hidden" name="tab" value="advanced">

        <!-- Debug mode -->
        <div class="ssd-card">
            <div class="ssd-card-head">
                <?php Simple_SMTP_DKIM_Helpers::icon('bug', 18); ?>
                <h2><?php esc_html_e('Debug mode', 'simple-smtp-dkim'); ?></h2>
            </div>
            <?php
            Simple_SMTP_DKIM_Helpers::render_toggle_row(array(
                'name'    => 'simple_smtp_dkim_debug_mode',
                'id'      => 'simple_smtp_dkim_debug_mode',
                'checked' => $debug_mode,
                'title'   => __('Enable debug logging', 'simple-smtp-dkim'),
                'tip'     => __('Logs detailed SMTP communication to the PHP error log. Disable in production.', 'simple-smtp-dkim'),
                'desc'    => __('Use only for troubleshooting.', 'simple-smtp-dkim'),
            ));
            ?>
        </div>

        <!-- Encryption security -->
        <div class="ssd-card">
            <div class="ssd-card-head">
                <?php Simple_SMTP_DKIM_Helpers::icon('lock', 18); ?>
                <h2><?php esc_html_e('Encryption security', 'simple-smtp-dkim'); ?></h2>
            </div>
            <table class="ssd-summary">
                <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('OpenSSL', 'simple-smtp-dkim'); ?></th>
                        <td>
                            <?php if ($enc['available']): ?>
                                <span class="ssd-badge ok"><span class="ssd-bd"></span><?php esc_html_e('Available', 'simple-smtp-dkim'); ?></span>
                            <?php else: ?>
                                <span class="ssd-badge err"><span class="ssd-bd"></span><?php esc_html_e('Not available', 'simple-smtp-dkim'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Algorithm', 'simple-smtp-dkim'); ?></th>
                        <td><span class="ssd-mono"><?php echo esc_html($enc['method']); ?></span></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Key location', 'simple-smtp-dkim'); ?></th>
                        <td>
                            <?php if ($key_in_config): ?>
                                <span class="ssd-badge ok"><span class="ssd-bd"></span><?php esc_html_e('wp-config.php (secure)', 'simple-smtp-dkim'); ?></span>
                            <?php else: ?>
                                <span class="ssd-badge warn"><span class="ssd-bd"></span><?php esc_html_e('Database — see the notice above to migrate it to wp-config.php', 'simple-smtp-dkim'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Uninstall -->
        <div class="ssd-card">
            <div class="ssd-card-head">
                <?php Simple_SMTP_DKIM_Helpers::icon('trash', 18); ?>
                <h2><?php esc_html_e('Uninstall', 'simple-smtp-dkim'); ?></h2>
            </div>
            <?php
            Simple_SMTP_DKIM_Helpers::render_toggle_row(array(
                'name'    => 'simple_smtp_dkim_delete_on_uninstall',
                'id'      => 'simple_smtp_dkim_delete_on_uninstall',
                'checked' => $delete_on_uninstall,
                'title'   => __('Delete all data on uninstall', 'simple-smtp-dkim'),
                'tip'     => __('Permanently removes settings, logs and tables when the plugin is deleted. Cannot be undone.', 'simple-smtp-dkim'),
            ));
            ?>
            <div class="ssd-banner err <?php echo $delete_on_uninstall ? '' : 'ssd-hidden'; ?>" id="ssd-uninstall-warning" style="margin-top:14px;margin-bottom:0;">
                <?php Simple_SMTP_DKIM_Helpers::icon('alert', 19); ?>
                <div>
                    <strong><?php esc_html_e('Warning:', 'simple-smtp-dkim'); ?></strong>
                    <?php esc_html_e('all logs, DKIM keys and settings will be permanently deleted when the plugin is removed.', 'simple-smtp-dkim'); ?>
                </div>
            </div>
        </div>

        <div class="ssd-submit-row">
            <button type="submit" class="ssd-btn ssd-btn-primary ssd-btn-lg">
                <?php Simple_SMTP_DKIM_Helpers::icon('check', 16); ?><?php esc_html_e('Save advanced settings', 'simple-smtp-dkim'); ?>
            </button>
        </div>
    </form>
</div>
