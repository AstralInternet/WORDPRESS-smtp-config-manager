<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template variables in included file scope.
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

$enabled     = (bool) get_option('simple_smtp_dkim_enabled', false);
$mailer_type = get_option('simple_smtp_dkim_mailer_type', 'smtp');
$from_email  = get_option('simple_smtp_dkim_from_email', get_option('admin_email'));
$from_name   = get_option('simple_smtp_dkim_from_name', get_option('blogname'));
$force_from  = (bool) get_option('simple_smtp_dkim_force_from', false);

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$sub_tab = isset($_GET['mailer']) ? sanitize_text_field(wp_unslash($_GET['mailer'])) : $mailer_type;
if (!in_array($sub_tab, array('smtp', 'oauth'), true)) {
    $sub_tab = 'smtp';
}

// OAuth2 transport is not wired into the mailer yet: settings can be
// prepared and saved, but the OAuth mailer cannot be activated.
$oauth_available = false;

$is_this_active = ($enabled && $mailer_type === $sub_tab);
$type_labels    = array(
    'smtp'  => __('SMTP', 'simple-smtp-dkim'),
    'oauth' => __('OAuth2', 'simple-smtp-dkim'),
);
$sub_label    = $type_labels[$sub_tab];
$mailer_base  = admin_url('options-general.php?page=simple-smtp-dkim&tab=mailer');
?>

<div class="ssd-section">
    <!-- Mailer sub-tabs -->
    <nav class="ssd-subtabs" aria-label="<?php esc_attr_e('Mailer type', 'simple-smtp-dkim'); ?>">
        <a href="<?php echo esc_url(add_query_arg('mailer', 'smtp', $mailer_base)); ?>"
           class="ssd-subtab <?php echo $sub_tab === 'smtp' ? 'active' : ''; ?>"
           <?php echo $sub_tab === 'smtp' ? 'aria-current="page"' : ''; ?>>
            <?php Simple_SMTP_DKIM_Helpers::icon('server', 15); ?>
            <?php echo esc_html($type_labels['smtp']); ?>
            <?php if ($enabled && $mailer_type === 'smtp'): ?>
                <span class="ssd-st-badge"><?php esc_html_e('Active', 'simple-smtp-dkim'); ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('mailer', 'oauth', $mailer_base)); ?>"
           class="ssd-subtab <?php echo $sub_tab === 'oauth' ? 'active' : ''; ?>"
           <?php echo $sub_tab === 'oauth' ? 'aria-current="page"' : ''; ?>>
            <?php Simple_SMTP_DKIM_Helpers::icon('shieldcheck', 15); ?>
            <?php echo esc_html($type_labels['oauth']); ?>
            <?php if ($enabled && $mailer_type === 'oauth'): ?>
                <span class="ssd-st-badge"><?php esc_html_e('Active', 'simple-smtp-dkim'); ?></span>
            <?php else: ?>
                <span class="ssd-st-badge new"><?php esc_html_e('Coming soon', 'simple-smtp-dkim'); ?></span>
            <?php endif; ?>
        </a>
    </nav>

    <!-- Status banner -->
    <?php if ($is_this_active): ?>
        <div class="ssd-banner ok">
            <?php Simple_SMTP_DKIM_Helpers::icon('check', 19); ?>
            <div>
                <?php
                echo wp_kses_post(sprintf(
                    /* translators: %s: mailer type name */
                    __('The <strong>%s</strong> mailer is active — every email WordPress sends goes through it.', 'simple-smtp-dkim'),
                    esc_html($sub_label)
                ));
                ?>
            </div>
        </div>
    <?php elseif ($enabled): ?>
        <div class="ssd-banner warn">
            <?php Simple_SMTP_DKIM_Helpers::icon('alert', 19); ?>
            <div>
                <?php
                echo wp_kses_post(sprintf(
                    /* translators: %1$s: active mailer type, %2$s: viewed mailer type */
                    __('The <strong>%1$s</strong> mailer is currently active. Enabling <strong>%2$s</strong> below will deactivate it.', 'simple-smtp-dkim'),
                    esc_html($type_labels[$mailer_type]),
                    esc_html($sub_label)
                ));
                ?>
            </div>
        </div>
    <?php else: ?>
        <div class="ssd-banner info">
            <?php Simple_SMTP_DKIM_Helpers::icon('info', 19); ?>
            <div>
                <?php
                echo esc_html(sprintf(
                    /* translators: %s: mailer type name */
                    __('No mailer is active. Enable %s below, then test the connection.', 'simple-smtp-dkim'),
                    $sub_label
                ));
                ?>
            </div>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="ssd-form" id="ssd-mailer-form" novalidate>
        <?php wp_nonce_field('simple_smtp_dkim_save_settings', 'simple_smtp_dkim_nonce'); ?>
        <input type="hidden" name="action" value="simple_smtp_dkim_save_settings">
        <input type="hidden" name="tab" value="mailer">
        <input type="hidden" name="mailer_sub" value="<?php echo esc_attr($sub_tab); ?>">

        <!-- Activation -->
        <div class="ssd-card">
            <div class="ssd-card-head">
                <?php Simple_SMTP_DKIM_Helpers::icon('plug', 18); ?>
                <h2><?php esc_html_e('Activation', 'simple-smtp-dkim'); ?></h2>
            </div>
            <?php
            $toggle_disabled = ($sub_tab === 'oauth' && !$oauth_available && !$is_this_active);
            Simple_SMTP_DKIM_Helpers::render_toggle_row(array(
                'name'    => 'simple_smtp_dkim_enabled',
                'id'      => 'simple_smtp_dkim_enabled',
                'checked' => $is_this_active,
                /* translators: %s: mailer type name */
                'title'   => sprintf(__('Enable the %s mailer', 'simple-smtp-dkim'), $sub_label),
                'desc'    => $toggle_disabled
                    ? __('OAuth2 sending is coming soon — you can already prepare and save the configuration below.', 'simple-smtp-dkim')
                    : __('Only one mailer can be active at a time. Enabling this one deactivates the other.', 'simple-smtp-dkim'),
                'disabled' => $toggle_disabled,
            ));
            ?>
        </div>

        <!-- Sub-tab content -->
        <?php
        $sub_partial = SIMPLE_SMTP_DKIM_PATH . 'includes/admin/tab-mailer-' . $sub_tab . '.php';
        if (file_exists($sub_partial)) {
            include $sub_partial;
        } else {
            include SIMPLE_SMTP_DKIM_PATH . 'includes/admin/tab-mailer-smtp.php';
        }
        ?>

        <!-- From address (common to all mailer types) -->
        <div class="ssd-card">
            <div class="ssd-card-head">
                <?php Simple_SMTP_DKIM_Helpers::icon('mail', 18); ?>
                <h2><?php esc_html_e('From address', 'simple-smtp-dkim'); ?></h2>
            </div>
            <div class="ssd-field-row">
                <div class="ssd-field">
                    <label for="simple_smtp_dkim_from_email"><?php esc_html_e('From email', 'simple-smtp-dkim'); ?></label>
                    <input type="email" name="simple_smtp_dkim_from_email" id="simple_smtp_dkim_from_email"
                           value="<?php echo esc_attr($from_email); ?>" class="ssd-inp" data-validate="email" aria-describedby="ssd-from-email-feedback">
                    <span class="ssd-inp-feedback" id="ssd-from-email-feedback" aria-live="polite"></span>
                </div>
                <div class="ssd-field">
                    <label for="simple_smtp_dkim_from_name"><?php esc_html_e('From name', 'simple-smtp-dkim'); ?></label>
                    <input type="text" name="simple_smtp_dkim_from_name" id="simple_smtp_dkim_from_name"
                           value="<?php echo esc_attr($from_name); ?>" class="ssd-inp">
                </div>
            </div>
            <?php
            Simple_SMTP_DKIM_Helpers::render_toggle_row(array(
                'name'    => 'simple_smtp_dkim_force_from',
                'id'      => 'simple_smtp_dkim_force_from',
                'checked' => $force_from,
                'title'   => __('Force the From address', 'simple-smtp-dkim'),
                'desc'    => __('Overrides the address set by other plugins or themes.', 'simple-smtp-dkim'),
            ));
            ?>
        </div>

        <?php if ($sub_tab === 'smtp'): ?>
        <!-- Test hero -->
        <div class="ssd-card ssd-hero">
            <div class="ssd-card-head">
                <?php Simple_SMTP_DKIM_Helpers::icon('rocket', 18); ?>
                <h2><?php esc_html_e('Test your configuration', 'simple-smtp-dkim'); ?></h2>
            </div>
            <p class="ssd-lead"><?php esc_html_e('One click runs a full diagnostic — connection, encryption, authentication, SPF, DKIM, then a real send — and shows you exactly where it fails, step by step.', 'simple-smtp-dkim'); ?></p>
            <div class="ssd-field-row" style="align-items:flex-end;gap:12px;">
                <div class="ssd-field">
                    <label for="ssd-diag-to"><?php esc_html_e('Send the test to', 'simple-smtp-dkim'); ?></label>
                    <input type="email" id="ssd-diag-to" class="ssd-inp" value="<?php echo esc_attr(get_option('admin_email')); ?>" placeholder="email@example.com">
                </div>
                <button type="button" class="ssd-btn ssd-btn-primary ssd-btn-lg" id="ssd-open-diagnostic" style="flex-shrink:0;margin-bottom:1px;">
                    <?php Simple_SMTP_DKIM_Helpers::icon('spark', 16); ?><?php esc_html_e('Run the full diagnostic', 'simple-smtp-dkim'); ?>
                </button>
            </div>
            <?php if (get_option('simple_smtp_dkim_last_test_success', false)): ?>
                <div class="ssd-muted-note" style="margin-top:12px;">
                    <?php Simple_SMTP_DKIM_Helpers::icon('check', 14); ?>
                    <span><?php esc_html_e('Last diagnostic passed — all checks green.', 'simple-smtp-dkim'); ?></span>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="ssd-submit-row">
            <button type="submit" class="ssd-btn ssd-btn-primary ssd-btn-lg">
                <?php Simple_SMTP_DKIM_Helpers::icon('check', 16); ?>
                <?php
                /* translators: %s: mailer type name */
                echo esc_html(sprintf(__('Save %s settings', 'simple-smtp-dkim'), $sub_label));
                ?>
            </button>
        </div>
    </form>
</div>

<?php
if ($sub_tab === 'smtp') {
    include SIMPLE_SMTP_DKIM_PATH . 'includes/admin/partial-diagnostic.php';
}
