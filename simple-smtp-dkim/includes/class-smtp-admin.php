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
            __('SMTP Configuration', 'simple-smtp-dkim'),
            __('SMTP', 'simple-smtp-dkim'),
            'manage_options',
            'simple-smtp-dkim',
            array(__CLASS__, 'render_settings_page')
        );
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

        wp_localize_script('simple-smtp-dkim-admin', 'simpleSMTPDKIM', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonces'  => array(
                'test_connection' => wp_create_nonce('simple_smtp_dkim_test_connection'),
                'send_test_email' => wp_create_nonce('simple_smtp_dkim_send_test_email'),
                'validate_dkim'   => wp_create_nonce('simple_smtp_dkim_validate_dkim'),
                'delete_logs'     => wp_create_nonce('simple_smtp_dkim_delete_logs'),
                'generate_dkim'   => wp_create_nonce('simple_smtp_dkim_generate_dkim'),
                'view_email'      => wp_create_nonce('simple_smtp_dkim_view_email'),
            ),
            'strings' => array(
                'testing'       => __('Testing...', 'simple-smtp-dkim'),
                'sending'       => __('Sending...', 'simple-smtp-dkim'),
                'validating'    => __('Validating...', 'simple-smtp-dkim'),
                'success'       => __('Success!', 'simple-smtp-dkim'),
                'error'         => __('Error', 'simple-smtp-dkim'),
                'confirmDelete' => __('Are you sure? This cannot be undone.', 'simple-smtp-dkim'),
                'copied'        => __('Copied!', 'simple-smtp-dkim'),
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
        array_unshift($links, '<a href="' . admin_url('options-general.php?page=simple-smtp-dkim') . '">' . __('Settings', 'simple-smtp-dkim') . '</a>');
        return $links;
    }

    /* ------------------------------------------------------------------
       Page Rendering — delegates to tab partials
       ------------------------------------------------------------------ */

    public static function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html(__('Insufficient permissions.', 'simple-smtp-dkim')));
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $active_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'dashboard';

        $tabs = array(
            'dashboard' => __('Dashboard', 'simple-smtp-dkim'),
            'mailer'    => __('Mailer', 'simple-smtp-dkim'),
            'dkim'      => __('DKIM', 'simple-smtp-dkim'),
            'logs'      => __('Email Logs', 'simple-smtp-dkim'),
            'advanced'  => __('Advanced', 'simple-smtp-dkim'),
        );
        ?>
        <div class="wrap simple-smtp-dkim-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <?php settings_errors('simple_smtp_dkim_messages'); ?>

            <nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e('Settings tabs', 'simple-smtp-dkim'); ?>">
                <?php foreach ($tabs as $slug => $label): ?>
                    <a href="?page=simple-smtp-dkim&tab=<?php echo esc_attr($slug); ?>"
                       class="nav-tab <?php echo $active_tab === $slug ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($label); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="simple-smtp-dkim-tab-content" role="tabpanel">
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

        $tab = isset($_POST['tab']) ? sanitize_text_field(wp_unslash($_POST['tab'])) : 'smtp';

        switch ($tab) {
            case 'mailer':   simple_smtp_dkim_save_mailer(); break;
            case 'dkim':     simple_smtp_dkim_save_dkim(); break;
            case 'logs':     simple_smtp_dkim_save_logging(); break;
            case 'advanced': simple_smtp_dkim_save_advanced(); break;
        }

        $redirect_args = array('page' => 'simple-smtp-dkim', 'tab' => $tab, 'updated' => 'true');
        if ($tab === 'mailer' && isset($_POST['mailer_sub'])) {
            $redirect_args['mailer'] = sanitize_text_field(wp_unslash($_POST['mailer_sub']));
        }
        wp_safe_redirect(add_query_arg($redirect_args, admin_url('options-general.php')));
        exit;
    }
}
