<?php
/**
 * Admin interface — initialisation, menu, asset loading, routing
 *
 * Tab rendering is in includes/admin/tab-*.php
 * Save logic is in includes/admin/save-handlers.php
 *
 * @package Simple_SMTP_DKIM
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Admin interface controller.
 *  *
 *  * Registers the settings page, enqueues assets, renders tabs,
 *  * and routes form submissions to the appropriate save handler.
 *  *
 *  * @since 1.0.0
 */
class Simple_SMTP_DKIM_Admin {

    /**
     * Register admin hooks.
     *  *
     *  * @since 1.0.0
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_settings_page'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_assets'));
        add_action('admin_post_simple_smtp_dkim_save_settings', array(__CLASS__, 'save_settings'));
        add_action('admin_post_simple_smtp_dkim_export_logs', array(__CLASS__, 'export_logs'));
        add_filter('admin_body_class', array(__CLASS__, 'add_body_class'));
        add_filter(
            'plugin_action_links_' . plugin_basename(SIMPLE_SMTP_DKIM_PATH . 'simple-smtp-dkim.php'),
            array(__CLASS__, 'add_plugin_action_links')
        );
    }

    /**
     * Register the plugin settings page under the WordPress Settings menu.
     *  *
     *  * @since 1.0.0
     */
    public static function add_settings_page() {
        add_options_page(
            __('SMTP & DKIM', 'simple-smtp-dkim'),
            __('SMTP & DKIM', 'simple-smtp-dkim'),
            'manage_options',
            'simple-smtp-dkim',
            array(__CLASS__, 'render_settings_page')
        );
    }

    /**
     * Add a page identifier class on <body> so the stylesheet can repaint
     * the admin content background on this screen only.
     *  *
     *  * @since 1.1.0
     *  *
     *  * @param string $classes Space-separated body classes.
     *  * @return string Modified classes.
     */
    public static function add_body_class($classes) {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && $screen->id === 'settings_page_simple-smtp-dkim') {
            $classes .= ' simple-smtp-dkim-page';
        }
        return $classes;
    }

    /**
     * Enqueue CSS and JavaScript on the plugin settings page only.
     *  *
     *  * @since 1.0.0
     *  *
     *  * @param string $hook The current admin page hook suffix.
     */
    public static function enqueue_admin_assets($hook) {
        if ($hook !== 'settings_page_simple-smtp-dkim') {
            return;
        }

        wp_enqueue_style(
            'simple-smtp-dkim-admin',
            SIMPLE_SMTP_DKIM_URL . 'assets/css/admin-style.css',
            array(),
            SIMPLE_SMTP_DKIM_VERSION
        );

        wp_enqueue_script(
            'simple-smtp-dkim-admin',
            SIMPLE_SMTP_DKIM_URL . 'assets/js/admin-script.js',
            array('jquery'),
            SIMPLE_SMTP_DKIM_VERSION,
            true
        );

        $dkim_ready = (bool) get_option('simple_smtp_dkim_dkim_enabled', false);
        $dns_ok     = (bool) get_option('simple_smtp_dkim_dns_verified', false);

        wp_localize_script('simple-smtp-dkim-admin', 'simpleSMTPDKIM', array(
            'ajaxUrl'    => admin_url('admin-ajax.php'),
            'adminEmail' => get_option('admin_email'),
            'nonces'  => array(
                'test_connection' => wp_create_nonce('simple_smtp_dkim_test_connection'),
                'send_test_email' => wp_create_nonce('simple_smtp_dkim_send_test_email'),
                'validate_dkim'   => wp_create_nonce('simple_smtp_dkim_validate_dkim'),
                'delete_logs'     => wp_create_nonce('simple_smtp_dkim_delete_logs'),
                'generate_dkim'   => wp_create_nonce('simple_smtp_dkim_generate_dkim'),
                'view_email'      => wp_create_nonce('simple_smtp_dkim_view_email'),
            ),
            'dkim' => array(
                'enabled'  => $dkim_ready,
                'verified' => $dns_ok,
                'selector' => get_option('simple_smtp_dkim_dkim_selector', ''),
                'domain'   => get_option('simple_smtp_dkim_dkim_domain', ''),
            ),
            'strings' => array(
                'testing'        => __('Testing…', 'simple-smtp-dkim'),
                'sending'        => __('Sending…', 'simple-smtp-dkim'),
                'validating'     => __('Validating…', 'simple-smtp-dkim'),
                'generating'     => __('Generating…', 'simple-smtp-dkim'),
                'deleting'       => __('Deleting…', 'simple-smtp-dkim'),
                'error'          => __('Error', 'simple-smtp-dkim'),
                'networkError'   => __('Network error. Please try again.', 'simple-smtp-dkim'),
                'confirmDelete'  => __('Delete all email logs? This cannot be undone.', 'simple-smtp-dkim'),
                'invalidEmail'   => __('Enter a valid email address.', 'simple-smtp-dkim'),
                'enterDomain'    => __('Enter a domain and selector first.', 'simple-smtp-dkim'),
                'waiting'        => __('Waiting…', 'simple-smtp-dkim'),
                'checking'       => __('Checking…', 'simple-smtp-dkim'),
                'fieldRequired'  => __('Required', 'simple-smtp-dkim'),
                'invalidHost'    => __('Invalid hostname', 'simple-smtp-dkim'),
                'portRange'      => __('1–65535', 'simple-smtp-dkim'),
                'diagAllOk'      => __('Everything works — the test email was delivered successfully.', 'simple-smtp-dkim'),
                'diagFailed'     => __('The diagnostic found a problem. Review the failing step below.', 'simple-smtp-dkim'),
                'dnsResolved'    => __('Server name resolved', 'simple-smtp-dkim'),
                'connOk'         => __('Connected on port %s', 'simple-smtp-dkim'),
                'tlsOk'          => __('Encrypted connection established', 'simple-smtp-dkim'),
                'tlsNone'        => __('No encryption selected', 'simple-smtp-dkim'),
                'authOk'         => __('Credentials accepted', 'simple-smtp-dkim'),
                'authSkipped'    => __('Authentication disabled', 'simple-smtp-dkim'),
                'spfMissing'     => __('No SPF record found — recommended for deliverability', 'simple-smtp-dkim'),
                'dkimActive'     => __('Key valid — DNS verified (%s)', 'simple-smtp-dkim'),
                'dkimPending'    => __('DKIM enabled but DNS not verified yet', 'simple-smtp-dkim'),
                'dkimOff'        => __('DKIM signing is disabled', 'simple-smtp-dkim'),
                'sendOk'         => __('Message accepted for delivery', 'simple-smtp-dkim'),
                'stepFailed'     => __('Step failed', 'simple-smtp-dkim'),
                'notReached'     => __('Not reached', 'simple-smtp-dkim'),
                'wizTestFirst'   => __('Run the test to continue', 'simple-smtp-dkim'),
                'wizContinue'    => __('Continue', 'simple-smtp-dkim'),
            ),
        ));
    }

    /**
     * Add a Settings link to the plugin row on the Plugins page.
     *  *
     *  * @since 1.0.0
     *  *
     *  * @param array $links Existing plugin action links.
     *  * @return array Modified action links.
     */
    public static function add_plugin_action_links($links) {
        array_unshift($links, '<a href="' . esc_url(admin_url('options-general.php?page=simple-smtp-dkim')) . '">' . esc_html__('Settings', 'simple-smtp-dkim') . '</a>');
        return $links;
    }

    /* ------------------------------------------------------------------
       Page Rendering — header + pill tabs, delegates to tab partials
       ------------------------------------------------------------------ */

    public static function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html(__('Insufficient permissions.', 'simple-smtp-dkim')));
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $active_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'dashboard';

        $tabs = array(
            'dashboard' => array('label' => __('Dashboard', 'simple-smtp-dkim'), 'icon' => 'gauge'),
            'mailer'    => array('label' => __('Mailer', 'simple-smtp-dkim'),    'icon' => 'server'),
            'dkim'      => array('label' => __('DKIM', 'simple-smtp-dkim'),      'icon' => 'shield'),
            'logs'      => array('label' => __('Email Logs', 'simple-smtp-dkim'), 'icon' => 'inbox'),
            'advanced'  => array('label' => __('Advanced', 'simple-smtp-dkim'),  'icon' => 'settings'),
        );
        if (!isset($tabs[$active_tab])) {
            $active_tab = 'dashboard';
        }

        $sending_on   = (bool) get_option('simple_smtp_dkim_enabled', false);
        $failed_count = 0;
        if (get_option('simple_smtp_dkim_logging_enabled', false)) {
            $stats        = Simple_SMTP_DKIM_Logger::get_statistics(30);
            $failed_count = (int) $stats['failed'];
        }
        ?>
        <div class="wrap simple-smtp-dkim-wrap">
            <?php /* The hidden heading is the anchor WordPress uses to position notices. */ ?>
            <h1 class="ssd-screen-reader-text"><?php echo esc_html(get_admin_page_title()); ?></h1>
            <?php
            // Renders messages saved by the admin-post handler; with
            // settings-updated=true, settings_errors() pulls them from the
            // transient set in save_settings().
            settings_errors('simple_smtp_dkim_messages');
            ?>

            <div class="ssd-app">
                <header class="ssd-head">
                    <div class="ssd-brand"><?php Simple_SMTP_DKIM_Helpers::icon('send', 24); ?></div>
                    <div class="ssd-titles">
                        <span class="ssd-title">Simple SMTP &amp; DKIM</span>
                        <span class="ssd-sub"><?php esc_html_e('Reliable email delivery, logging and DKIM signing', 'simple-smtp-dkim'); ?></span>
                    </div>
                    <span class="ssd-spacer"></span>
                    <span class="ssd-master-pill <?php echo $sending_on ? 'on' : 'off'; ?>">
                        <span class="ssd-dot" aria-hidden="true"></span>
                        <?php $sending_on ? esc_html_e('Sending active', 'simple-smtp-dkim') : esc_html_e('Inactive', 'simple-smtp-dkim'); ?>
                    </span>
                </header>

                <nav class="ssd-tabs" aria-label="<?php esc_attr_e('Settings tabs', 'simple-smtp-dkim'); ?>">
                    <?php foreach ($tabs as $slug => $tab): ?>
                        <a href="<?php echo esc_url(add_query_arg(array('page' => 'simple-smtp-dkim', 'tab' => $slug), admin_url('options-general.php'))); ?>"
                           class="ssd-tab <?php echo $active_tab === $slug ? 'active' : ''; ?>"
                           <?php echo $active_tab === $slug ? 'aria-current="page"' : ''; ?>>
                            <?php Simple_SMTP_DKIM_Helpers::icon($tab['icon'], 16); ?>
                            <span><?php echo esc_html($tab['label']); ?></span>
                            <?php if ($slug === 'logs' && $failed_count > 0): ?>
                                <span class="ssd-count"><?php echo esc_html(number_format_i18n($failed_count)); ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <div class="ssd-tab-content" role="tabpanel">
                    <?php
                    $partial = SIMPLE_SMTP_DKIM_PATH . 'includes/admin/tab-' . $active_tab . '.php';
                    if (file_exists($partial)) {
                        include $partial;
                    } else {
                        include SIMPLE_SMTP_DKIM_PATH . 'includes/admin/tab-dashboard.php';
                    }
                    ?>
                </div>
            </div>

            <div class="ssd-toast-wrap ssd-hidden" id="ssd-toast-wrap" aria-live="polite">
                <div class="ssd-toast"><?php Simple_SMTP_DKIM_Helpers::icon('check', 16); ?><span id="ssd-toast-msg"></span></div>
            </div>
        </div>
        <?php
    }

    /* ------------------------------------------------------------------
       Save Handler — delegates to save-handlers.php
       ------------------------------------------------------------------ */

    public static function save_settings() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html(__('Insufficient permissions.', 'simple-smtp-dkim')));
        }
        if (!isset($_POST['simple_smtp_dkim_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['simple_smtp_dkim_nonce'])), 'simple_smtp_dkim_save_settings')) {
            wp_die(esc_html(__('Security check failed.', 'simple-smtp-dkim')));
        }

        require_once SIMPLE_SMTP_DKIM_PATH . 'includes/admin/save-handlers.php';

        $tab = isset($_POST['tab']) ? sanitize_text_field(wp_unslash($_POST['tab'])) : 'mailer';
        if (!in_array($tab, array('mailer', 'dkim', 'logs', 'advanced'), true)) {
            $tab = 'mailer';
        }

        switch ($tab) {
            case 'mailer':   simple_smtp_dkim_save_mailer(); break;
            case 'dkim':     simple_smtp_dkim_save_dkim(); break;
            case 'logs':     simple_smtp_dkim_save_logging(); break;
            case 'advanced': simple_smtp_dkim_save_advanced(); break;
        }

        // Persist messages across the redirect so settings_errors() (called by
        // options-head.php) can display them — admin-post requests lose the
        // in-memory $wp_settings_errors otherwise (e.g. DKIM upload errors).
        $messages = get_settings_errors();
        $redirect_args = array('page' => 'simple-smtp-dkim', 'tab' => $tab);
        if (!empty($messages)) {
            set_transient('settings_errors', $messages, 30);
            $redirect_args['settings-updated'] = 'true';
        }
        if ($tab === 'mailer' && isset($_POST['mailer_sub'])) {
            $sub = sanitize_text_field(wp_unslash($_POST['mailer_sub']));
            if (in_array($sub, array('smtp', 'oauth'), true)) {
                $redirect_args['mailer'] = $sub;
            }
        }
        // Land back on the dashboard when the guided wizard completes.
        if (isset($_POST['ssd_wizard']) && $_POST['ssd_wizard'] === '1') {
            $redirect_args['tab'] = 'dashboard';
        }
        wp_safe_redirect(add_query_arg($redirect_args, admin_url('options-general.php')));
        exit;
    }

    /* ------------------------------------------------------------------
       CSV export — streams ALL logs matching the current filters
       ------------------------------------------------------------------ */

    public static function export_logs() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html(__('Insufficient permissions.', 'simple-smtp-dkim')));
        }
        check_admin_referer('simple_smtp_dkim_export_logs');

        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
        if (!in_array($status, array('', 'success', 'failed'), true)) {
            $status = '';
        }

        $csv = Simple_SMTP_DKIM_Logger::export_logs_csv(array('search' => $search, 'status' => $status));
        if ($csv === false) {
            $csv = "ID,Timestamp,To,From,Subject,Status,DKIM Signed,Error Message\n";
        }

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="email-logs-' . gmdate('Y-m-d') . '.csv"');
        echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV file download, not HTML.
        exit;
    }
}
