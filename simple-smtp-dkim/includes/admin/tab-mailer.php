<?php
/**
 * Tab partial: Mailer (multi-transport wrapper)
 *
 * Only one mailer type can be active at a time.
 * The enable toggle activates/deactivates the currently viewed sub-tab.
 * Switching sub-tabs does NOT erase the other mailer's saved settings.
 *
 * @package Simple_SMTP_DKIM
 */
if (!defined('WPINC')) { die; }

$enabled     = get_option('simple_smtp_dkim_enabled', false);
$mailer_type = get_option('simple_smtp_dkim_mailer_type', 'smtp');
$from_email  = get_option('simple_smtp_dkim_from_email', get_option('admin_email'));
$from_name   = get_option('simple_smtp_dkim_from_name', get_option('blogname'));
$force_from  = get_option('simple_smtp_dkim_force_from', false);
$status      = Simple_SMTP_DKIM_Validator::get_connection_status();

$sub_tab = isset($_GET['mailer']) ? sanitize_text_field($_GET['mailer']) : $mailer_type;
if (!in_array($sub_tab, array('smtp', 'oauth'), true)) {
    $sub_tab = 'smtp';
}

// Is THIS sub-tab the active mailer?
$is_this_active = ($enabled && $mailer_type === $sub_tab);

// Labels per type
$type_labels = array(
    'smtp'  => __('SMTP', 'simple-smtp-dkim'),
    'oauth' => __('OAuth2', 'simple-smtp-dkim'),
);

// Badge logic
$mailer_tabs = array(
    'smtp'  => array(
        'label' => $type_labels['smtp'],
        'badge' => ($enabled && $mailer_type === 'smtp') ? __('Active', 'simple-smtp-dkim') : '',
    ),
    'oauth' => array(
        'label'   => $type_labels['oauth'],
        'enabled' => false,
        'badge'   => __('Coming soon', 'simple-smtp-dkim'),
    ),
);
?>

<div class="simple-smtp-dkim-section">
    <!-- Mailer Sub-tab Navigation -->
    <nav class="smtp-mailer-nav" aria-label="<?php esc_attr_e('Mailer type', 'simple-smtp-dkim'); ?>">
        <?php foreach ($mailer_tabs as $slug => $tab_info):
            $is_enabled = !isset($tab_info['enabled']) || $tab_info['enabled'] !== false;
        ?>
            <?php if ($is_enabled): ?>
                <a href="<?php echo esc_url(add_query_arg(array('page' => 'simple-smtp-dkim', 'tab' => 'mailer', 'mailer' => $slug), admin_url('options-general.php'))); ?>"
                   class="smtp-mailer-tab <?php echo esc_attr($sub_tab === $slug ? 'smtp-mailer-tab-active' : ''); ?>"
                   <?php echo $sub_tab === $slug ? 'aria-current="page"' : ''; ?>>
                    <?php echo esc_html($tab_info['label']); ?>
                    <?php if ($tab_info['badge']): ?>
                        <span class="smtp-mailer-badge smtp-mailer-badge-active"><?php echo esc_html($tab_info['badge']); ?></span>
                    <?php endif; ?>
                </a>
            <?php else: ?>
                <span class="smtp-mailer-tab smtp-mailer-tab-disabled" aria-disabled="true" title="<?php echo esc_attr($tab_info['badge']); ?>">
                    <?php echo esc_html($tab_info['label']); ?>
                    <?php if ($tab_info['badge']): ?>
                        <span class="smtp-mailer-badge smtp-mailer-badge-soon"><?php echo esc_html($tab_info['badge']); ?></span>
                    <?php endif; ?>
                </span>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <!-- Info: other mailer is active -->
    <?php if ($enabled && $mailer_type !== $sub_tab): ?>
        <div class="smtp-notice-banner smtp-notice-warning" style="margin-bottom:20px;">
            <span class="dashicons dashicons-info" aria-hidden="true"></span>
            <div>
                <?php printf(
                    __('The <strong>%s</strong> mailer is currently active. Enabling <strong>%s</strong> below will deactivate it.', 'simple-smtp-dkim'),
                    esc_html($type_labels[$mailer_type]),
                    esc_html($type_labels[$sub_tab])
                ); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Status Banner -->
    <div class="smtp-status-banner smtp-status-<?php echo esc_attr($status['class']); ?>" role="status">
        <span class="dashicons dashicons-<?php echo esc_attr($status['status'] === 'configured' ? 'yes-alt' : ($status['status'] === 'disabled' ? 'warning' : 'info')); ?>" aria-hidden="true"></span>
        <span><?php echo esc_html($status['message']); ?></span>
    </div>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="simple-smtp-dkim-form" id="smtp-settings-form" novalidate>
        <?php wp_nonce_field('simple_smtp_dkim_save_settings', 'simple_smtp_dkim_nonce'); ?>
        <input type="hidden" name="action" value="simple_smtp_dkim_save_settings">
        <input type="hidden" name="tab" value="mailer">
        <input type="hidden" name="mailer_sub" value="<?php echo esc_attr($sub_tab); ?>">

        <!-- Enable THIS Mailer -->
        <div class="simple-smtp-dkim-card">
            <h2><?php printf(__('Enable %s Mailer', 'simple-smtp-dkim'), esc_html($type_labels[$sub_tab])); ?></h2>
            <table class="form-table"><tr>
                <th scope="row">
                    <label for="simple_smtp_dkim_enabled">
                        <?php printf(__('Enable %s Mailer', 'simple-smtp-dkim'), esc_html($type_labels[$sub_tab])); ?>
                    </label>
                    <?php Simple_SMTP_DKIM_Helpers::render_info_icon(
                        sprintf(__('Activate the %s mailer. Only one mailer type can be active at a time.', 'simple-smtp-dkim'), $type_labels[$sub_tab])
                    ); ?>
                </th>
                <td><?php Simple_SMTP_DKIM_Helpers::render_toggle('simple_smtp_dkim_enabled', 'simple_smtp_dkim_enabled', $is_this_active); ?></td>
            </tr></table>
        </div>

        <!-- Sub-tab Content -->
        <?php
        $sub_partial = SIMPLE_SMTP_DKIM_PATH . 'includes/admin/tab-mailer-' . $sub_tab . '.php';
        if (file_exists($sub_partial)) {
            include $sub_partial;
        } else {
            include SIMPLE_SMTP_DKIM_PATH . 'includes/admin/tab-mailer-smtp.php';
        }
        ?>

        <!-- From Address (common to all mailer types) -->
        <div class="simple-smtp-dkim-card">
            <h2><?php _e('From Address', 'simple-smtp-dkim'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="simple_smtp_dkim_from_email"><?php _e('Email', 'simple-smtp-dkim'); ?></label></th>
                    <td>
                        <input type="email" name="simple_smtp_dkim_from_email" id="simple_smtp_dkim_from_email" value="<?php echo esc_attr($from_email); ?>" class="regular-text" data-validate="email" aria-describedby="from-email-feedback">
                        <span class="smtp-field-feedback" id="from-email-feedback" aria-live="polite"></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="simple_smtp_dkim_from_name"><?php _e('Name', 'simple-smtp-dkim'); ?></label></th>
                    <td><input type="text" name="simple_smtp_dkim_from_name" id="simple_smtp_dkim_from_name" value="<?php echo esc_attr($from_name); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="simple_smtp_dkim_force_from"><?php _e('Force From', 'simple-smtp-dkim'); ?></label>
                        <?php Simple_SMTP_DKIM_Helpers::render_info_icon(__('Override the From address set by other plugins/themes.', 'simple-smtp-dkim')); ?>
                    </th>
                    <td><?php Simple_SMTP_DKIM_Helpers::render_toggle('simple_smtp_dkim_force_from', 'simple_smtp_dkim_force_from', $force_from); ?></td>
                </tr>
            </table>
        </div>

        <!-- Test Area (common to all mailer types) -->
        <div class="simple-smtp-dkim-card">
            <h2><?php _e('Test Your Configuration', 'simple-smtp-dkim'); ?></h2>
            <div class="smtp-test-row">
                <button type="button" id="smtp-test-connection" class="button button-secondary">
                    <span class="dashicons dashicons-update-alt" aria-hidden="true"></span> <?php _e('Test Connection', 'simple-smtp-dkim'); ?>
                </button>
                <div class="smtp-test-email-inline">
                    <label for="smtp_test_email_to" class="screen-reader-text"><?php _e('Test email recipient', 'simple-smtp-dkim'); ?></label>
                    <input type="email" id="smtp_test_email_to" class="regular-text" value="<?php echo esc_attr(get_option('admin_email')); ?>" placeholder="email@example.com" aria-label="<?php esc_attr_e('Test email recipient', 'simple-smtp-dkim'); ?>">
                    <button type="button" id="smtp-send-test-email" class="button button-primary">
                        <span class="dashicons dashicons-email-alt" aria-hidden="true"></span> <?php _e('Send Test Email', 'simple-smtp-dkim'); ?>
                    </button>
                </div>
            </div>
            <div id="smtp-test-result" class="smtp-test-result" style="display:none;" role="alert" aria-live="assertive"></div>
            <div id="smtp-test-debug" class="smtp-test-debug" style="display:none;">
                <button type="button" class="smtp-debug-toggle" aria-expanded="false"><?php _e('Show Debug Info', 'simple-smtp-dkim'); ?></button>
                <pre class="smtp-debug-content" role="log"></pre>
            </div>
        </div>

        <p class="submit">
            <button type="submit" class="button button-primary button-large">
                <?php printf(__('Save %s Settings', 'simple-smtp-dkim'), esc_html($type_labels[$sub_tab])); ?>
            </button>
        </p>
    </form>
</div>
