<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables in included file scope.
/**
 * Tab partial: Advanced Settings
 * @package Simple_SMTP_DKIM
 */
if (!defined('WPINC')) { die; }

$debug_mode         = get_option('simple_smtp_dkim_debug_mode', false);
$delete_on_uninstall = get_option('simple_smtp_dkim_delete_on_uninstall', false);
?>

<div class="simple-smtp-dkim-section">
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="simple-smtp-dkim-form">
        <?php wp_nonce_field('simple_smtp_dkim_save_settings', 'simple_smtp_dkim_nonce'); ?>
        <input type="hidden" name="action" value="simple_smtp_dkim_save_settings">
        <input type="hidden" name="tab" value="advanced">

        <!-- Debug Mode -->
        <div class="simple-smtp-dkim-card">
            <h2><?php esc_html_e('Debug Mode', 'simple-smtp-dkim'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="simple_smtp_dkim_debug_mode"><?php esc_html_e('Enable Debug Logging', 'simple-smtp-dkim'); ?></label>
                        <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('Logs detailed SMTP communication to the PHP error log. Use only for troubleshooting — disable in production.', 'simple-smtp-dkim')); ?>
                    </th>
                    <td>
                        <?php Simple_SMTP_DKIM_Helpers::render_toggle('simple_smtp_dkim_debug_mode', 'simple_smtp_dkim_debug_mode', $debug_mode); ?>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Uninstall Behaviour -->
        <div class="simple-smtp-dkim-card">
            <h2><?php esc_html_e('Uninstall Behaviour', 'simple-smtp-dkim'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="simple_smtp_dkim_delete_on_uninstall"><?php esc_html_e('Delete All Data on Uninstall', 'simple-smtp-dkim'); ?></label>
                        <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('Permanently removes all plugin data (settings, logs, database tables) when you delete the plugin. Cannot be undone.', 'simple-smtp-dkim')); ?>
                    </th>
                    <td>
                        <?php Simple_SMTP_DKIM_Helpers::render_toggle('simple_smtp_dkim_delete_on_uninstall', 'simple_smtp_dkim_delete_on_uninstall', $delete_on_uninstall); ?>
                        <p class="description" style="color:#d63638;">
                            <strong><?php esc_html_e('Warning:', 'simple-smtp-dkim'); ?></strong>
                            <?php esc_html_e('All email logs, DKIM keys, settings and configuration will be permanently deleted.', 'simple-smtp-dkim'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Encryption Status -->
        <div class="simple-smtp-dkim-card">
            <h2><?php esc_html_e('Encryption Status', 'simple-smtp-dkim'); ?></h2>
            <?php $enc = Simple_SMTP_DKIM_Encryption::get_encryption_info(); ?>
            <table class="smtp-summary-table">
                <tr>
                    <th><?php esc_html_e('OpenSSL', 'simple-smtp-dkim'); ?></th>
                    <td>
                        <?php if ($enc['available']): ?>
                            <span style="color:#00a32a;">&#10003; <?php esc_html_e('Available', 'simple-smtp-dkim'); ?></span>
                        <?php else: ?>
                            <span style="color:#d63638;">&#10007; <?php esc_html_e('Not available', 'simple-smtp-dkim'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Cipher', 'simple-smtp-dkim'); ?></th>
                    <td><code><?php echo esc_html($enc['method']); ?></code></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Key Location', 'simple-smtp-dkim'); ?></th>
                    <td>
                        <?php if (!defined('SIMPLE_SMTP_DKIM_KEY_IN_DB') || !SIMPLE_SMTP_DKIM_KEY_IN_DB): ?>
                            <span style="color:#00a32a;">&#10003; <?php esc_html_e('wp-config.php (secure)', 'simple-smtp-dkim'); ?></span>
                        <?php else: ?>
                            <span style="color:#d63638;">&#10007; <?php esc_html_e('Database — see admin notice above to migrate to wp-config.php', 'simple-smtp-dkim'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>

        <p class="submit">
            <button type="submit" class="button button-primary button-large"><?php esc_html_e('Save Advanced Settings', 'simple-smtp-dkim'); ?></button>
        </p>
    </form>
</div>
