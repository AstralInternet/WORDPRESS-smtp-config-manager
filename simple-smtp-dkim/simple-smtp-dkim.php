<?php
/**
 * Plugin Name: Simple SMTP & DKIM
 * Plugin URI: https://github.com/astralinternet/simple-smtp-dkim
 * Description: A secure SMTP configuration manager with DKIM support for WordPress
 * Version: 1.0.1
 * Author: Astral Internet
 * Author URI: https://astralinternet.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simple-smtp-dkim
 * Domain Path: /languages
 */

if (!defined('WPINC')) {
    die;
}

/* =========================================================================
   Constants
   ========================================================================= */

define('SIMPLE_SMTP_DKIM_VERSION',    '1.0.1');
define('SIMPLE_SMTP_DKIM_PATH',       plugin_dir_path(__FILE__));
define('SIMPLE_SMTP_DKIM_URL',        plugin_dir_url(__FILE__));
define('SIMPLE_SMTP_DKIM_UPLOAD_DIR', WP_CONTENT_DIR . '/simple-smtp-dkim/');

/* =========================================================================
   Encryption key — prefer wp-config.php constant, fall back to database
   The DB fallback only runs in admin to avoid a get_option on every frontend page.
   ========================================================================= */

if (!defined('SIMPLE_SMTP_DKIM_ENCRYPTION_KEY')) {
    if (is_admin() || wp_doing_ajax() || (defined('WP_CLI') && WP_CLI)) {
        $simple_smtp_dkim_db_key = get_option('simple_smtp_dkim_encryption_key', '');
        if (!empty($simple_smtp_dkim_db_key)) {
            define('SIMPLE_SMTP_DKIM_ENCRYPTION_KEY', $simple_smtp_dkim_db_key);
            define('SIMPLE_SMTP_DKIM_KEY_IN_DB', true);
        } else {
            define('SIMPLE_SMTP_DKIM_ENCRYPTION_KEY', '');
            define('SIMPLE_SMTP_DKIM_KEY_IN_DB', false);
        }
    } else {
        // Frontend: if not in wp-config.php, encryption simply not available
        // The mailer can still function; passwords were encrypted at save time
        define('SIMPLE_SMTP_DKIM_ENCRYPTION_KEY', '');
        define('SIMPLE_SMTP_DKIM_KEY_IN_DB', false);
    }
} else {
    define('SIMPLE_SMTP_DKIM_KEY_IN_DB', false);
}

/* =========================================================================
   Activation / Deactivation
   ========================================================================= */

function simple_smtp_dkim_activate() {
    require_once SIMPLE_SMTP_DKIM_PATH . 'includes/class-smtp-activator.php';
    Simple_SMTP_DKIM_Activator::activate();
}
/**
 * Run deactivation routine.
 *  *
 *  * @since 1.0.0
 *  *
 *  * @see Simple_SMTP_DKIM_Activator::deactivate()
 */
function simple_smtp_dkim_deactivate() {
    require_once SIMPLE_SMTP_DKIM_PATH . 'includes/class-smtp-activator.php';
    Simple_SMTP_DKIM_Activator::deactivate();
}
register_activation_hook(__FILE__, 'simple_smtp_dkim_activate');
register_deactivation_hook(__FILE__, 'simple_smtp_dkim_deactivate');

/* =========================================================================
   Autoload classes (on plugins_loaded)
   ========================================================================= */

function simple_smtp_dkim_load_classes() {
    // Always needed: encryption (for mailer password decryption) and logger (for email logging)
    require_once SIMPLE_SMTP_DKIM_PATH . 'includes/class-smtp-encryption.php';
    require_once SIMPLE_SMTP_DKIM_PATH . 'includes/class-smtp-logger.php';
    require_once SIMPLE_SMTP_DKIM_PATH . 'includes/class-smtp-mailer.php';

    // Admin & AJAX only: helpers, validator, ajax handlers, admin UI
    if (is_admin() || wp_doing_ajax()) {
        require_once SIMPLE_SMTP_DKIM_PATH . 'includes/class-smtp-helpers.php';
        require_once SIMPLE_SMTP_DKIM_PATH . 'includes/class-smtp-validator.php';
        require_once SIMPLE_SMTP_DKIM_PATH . 'includes/class-smtp-ajax.php';
    }
    if (is_admin()) {
        require_once SIMPLE_SMTP_DKIM_PATH . 'includes/class-smtp-admin.php';
    }
}
add_action('plugins_loaded', 'simple_smtp_dkim_load_classes');

/* =========================================================================
   Initialise (on init)
   ========================================================================= */

function simple_smtp_dkim_init() {
    // SMTP mail interception (frontend + admin — this is the core function)
    if (get_option('simple_smtp_dkim_enabled', false)) {
        Simple_SMTP_DKIM_Mailer::init();
    }

    // AJAX handlers + admin UI (admin only)
    if (is_admin() || wp_doing_ajax()) {
        Simple_SMTP_DKIM_Ajax::init();
    }
    if (is_admin()) {
        Simple_SMTP_DKIM_Admin::init();
    }
}
add_action('init', 'simple_smtp_dkim_init');

/* =========================================================================
   Admin notice — encryption key in database
   ========================================================================= */

function simple_smtp_dkim_encryption_key_notice() {
    if (!defined('SIMPLE_SMTP_DKIM_KEY_IN_DB') || !SIMPLE_SMTP_DKIM_KEY_IN_DB) {
        return;
    }
    if (!current_user_can('manage_options')) {
        return;
    }
    $key_value = get_option('simple_smtp_dkim_encryption_key', '');
    if (empty($key_value)) {
        return;
    }
    $nonce = wp_create_nonce('simple_smtp_dkim_migrate_key');
    ?>
    <div class="notice notice-warning is-dismissible" id="smtp-dkim-key-notice">
        <p><strong><?php esc_html_e('Simple SMTP & DKIM — Security Recommendation:', 'simple-smtp-dkim'); ?></strong></p>
        <p><?php esc_html_e('Your encryption key is currently stored in the database. For better security, it should be moved to your <code>wp-config.php</code> file.', 'simple-smtp-dkim'); ?></p>
        <p>
            <button type="button" id="smtp-dkim-migrate-key-btn" class="button button-primary">
                <?php esc_html_e('Move key to wp-config.php automatically', 'simple-smtp-dkim'); ?>
            </button>
            <span id="smtp-dkim-migrate-status" style="margin-left: 12px;"></span>
        </p>
        <details style="margin-top: 8px; margin-bottom: 8px;">
            <summary style="cursor: pointer; color: #787c82; font-size: 13px;"><?php esc_html_e('Or add it manually', 'simple-smtp-dkim'); ?></summary>
            <p style="margin-top: 8px;"><?php esc_html_e('Add this line to your <code>wp-config.php</code>, just before <code>/* That\'s all, stop editing! */</code>:', 'simple-smtp-dkim'); ?></p>
            <p><code>define('SIMPLE_SMTP_DKIM_ENCRYPTION_KEY', '<?php echo esc_html($key_value); ?>');</code></p>
        </details>
    </div>
    <script>
    (function() {
        var btn = document.getElementById('smtp-dkim-migrate-key-btn');
        var status = document.getElementById('smtp-dkim-migrate-status');
        if (!btn) return;
        btn.addEventListener('click', function() {
            btn.disabled = true;
            btn.textContent = '<?php echo esc_js(__('Migrating...', 'simple-smtp-dkim')); ?>';
            status.textContent = '';
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo esc_js(admin_url('admin-ajax.php')); ?>');
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                try {
                    var resp = JSON.parse(xhr.responseText);
                    if (resp.success) {
                        status.style.color = '#00a32a';
                        status.innerHTML = '&#10003; ' + resp.data.message;
                        btn.style.display = 'none';
                        var notice = document.getElementById('smtp-dkim-key-notice');
                        if (notice) { notice.className = 'notice notice-success is-dismissible'; }
                    } else {
                        status.style.color = '#d63638';
                        status.innerHTML = '&#10007; ' + (resp.data && resp.data.message ? resp.data.message : 'Error');
                        btn.disabled = false;
                        btn.textContent = '<?php echo esc_js(__('Move key to wp-config.php automatically', 'simple-smtp-dkim')); ?>';
                    }
                } catch(e) {
                    status.style.color = '#d63638';
                    status.textContent = '<?php echo esc_js(__('Unexpected error.', 'simple-smtp-dkim')); ?>';
                    btn.disabled = false;
                    btn.textContent = '<?php echo esc_js(__('Move key to wp-config.php automatically', 'simple-smtp-dkim')); ?>';
                }
            };
            xhr.onerror = function() {
                status.style.color = '#d63638';
                status.textContent = '<?php echo esc_js(__('Network error.', 'simple-smtp-dkim')); ?>';
                btn.disabled = false;
            };
            xhr.send('action=simple_smtp_dkim_migrate_key&nonce=<?php echo esc_js($nonce); ?>');
        });
    })();
    </script>
    <?php
}
add_action('admin_notices', 'simple_smtp_dkim_encryption_key_notice');

/* =========================================================================
   AJAX: migrate encryption key to wp-config.php
   ========================================================================= */

function simple_smtp_dkim_ajax_migrate_key() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Insufficient permissions.', 'simple-smtp-dkim')));
    }
    check_ajax_referer('simple_smtp_dkim_migrate_key', 'nonce');

    $key_value = get_option('simple_smtp_dkim_encryption_key', '');
    if (empty($key_value)) {
        wp_send_json_error(array('message' => __('No key in database.', 'simple-smtp-dkim')));
    }

    $wp_config = simple_smtp_dkim_locate_wp_config();
    if ($wp_config === false) {
        wp_send_json_error(array('message' => __('Could not locate wp-config.php.', 'simple-smtp-dkim')));
    }
    if (!wp_is_writable($wp_config)) {
        wp_send_json_error(array('message' => __('wp-config.php is not writable.', 'simple-smtp-dkim')));
    }

    $content = file_get_contents($wp_config);
    if ($content === false) {
        wp_send_json_error(array('message' => __('Could not read wp-config.php.', 'simple-smtp-dkim')));
    }

    // Already present?
    if (preg_match('/define\s*\(\s*[\'"](SIMPLE_SMTP_DKIM_ENCRYPTION_KEY)[\'"]/', $content)) {
        delete_option('simple_smtp_dkim_encryption_key');
        wp_send_json_success(array('message' => __('Key already in wp-config.php. Database copy removed.', 'simple-smtp-dkim')));
    }

    if (!preg_match('/^[A-Za-z0-9+\/=]+$/', $key_value)) {
        wp_send_json_error(array('message' => __('Key contains unexpected characters.', 'simple-smtp-dkim')));
    }

    $line = "\n/** Simple SMTP & DKIM encryption key */\ndefine('SIMPLE_SMTP_DKIM_ENCRYPTION_KEY', '" . $key_value . "');\n";

    // Find insertion point
    $anchors = array("/* That's all, stop editing!", "/* That\xe2\x80\x99s all, stop editing!");
    $pos = false;
    foreach ($anchors as $a) {
        $pos = strpos($content, $a);
        if ($pos !== false) break;
    }
    if ($pos !== false) {
        $new = substr($content, 0, $pos) . $line . "\n" . substr($content, $pos);
    } elseif (preg_match('/^.*require.*wp-settings\.php.*$/m', $content, $m, PREG_OFFSET_CAPTURE)) {
        $pos = $m[0][1];
        $new = substr($content, 0, $pos) . $line . "\n" . substr($content, $pos);
    } else {
        $new = $content . "\n" . $line;
    }

    // Backup
    $backup = $wp_config . '.smtp-dkim-backup-' . gmdate('YmdHis');
    if (!copy($wp_config, $backup)) {
        wp_send_json_error(array('message' => __('Could not create backup.', 'simple-smtp-dkim')));
    }

    if (file_put_contents($wp_config, $new) === false) {
        copy($backup, $wp_config);
        wp_delete_file($backup);
        wp_send_json_error(array('message' => __('Write failed. Original restored.', 'simple-smtp-dkim')));
    }

    // Verify
    if (strpos(file_get_contents($wp_config), "SIMPLE_SMTP_DKIM_ENCRYPTION_KEY") === false) {
        copy($backup, $wp_config);
        wp_delete_file($backup);
        wp_send_json_error(array('message' => __('Verification failed. Original restored.', 'simple-smtp-dkim')));
    }

    delete_option('simple_smtp_dkim_encryption_key');
    wp_delete_file($backup);

    wp_send_json_success(array('message' => __('Key moved to wp-config.php successfully.', 'simple-smtp-dkim')));
}
add_action('wp_ajax_simple_smtp_dkim_migrate_key', 'simple_smtp_dkim_ajax_migrate_key');

/**
 * Locate the wp-config.php file path.
 *  *
 *  * Checks ABSPATH first, then one directory above.
 *  *
 *  * @since 1.0.0
 *  *
 *  * @return string|false Path to wp-config.php or false if not found.
 */
function simple_smtp_dkim_locate_wp_config() {
    if (file_exists(ABSPATH . 'wp-config.php')) return ABSPATH . 'wp-config.php';
    if (file_exists(dirname(ABSPATH) . '/wp-config.php')) return dirname(ABSPATH) . '/wp-config.php';
    return false;
}

/* =========================================================================
   Translations
   ========================================================================= */

// Note: load_plugin_textdomain() is no longer needed since WordPress 4.6+.
// Translations are automatically loaded from translate.wordpress.org.
