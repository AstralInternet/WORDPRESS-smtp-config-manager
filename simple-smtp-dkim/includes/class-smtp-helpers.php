<?php
/**
 * Shared helper functions used across multiple classes
 *
 * @package Simple_SMTP_DKIM
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Utility functions for the plugin.
 *  *
 *  * Provides option management, file validation, and UI rendering helpers
 *  * used across the admin interface.
 *  *
 *  * @since 1.0.0
 */
class Simple_SMTP_DKIM_Helpers {

    /**
     * Update an option without autoload (saves memory)
     *
     * @param string $option Option name
     * @param mixed  $value  Option value
     */
    public static function update_option_no_autoload($option, $value) {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option)
        );

        if ($row !== null) {
            $wpdb->update(
                $wpdb->options,
                array('option_value' => maybe_serialize($value)),
                array('option_name' => $option)
            );
            wp_cache_delete($option, 'options');
        } else {
            add_option($option, $value, '', 'no');
        }
    }

    /**
     * Validate a DKIM file path to prevent path traversal attacks
     *
     * @param  string          $file_path Raw path from user input
     * @return string|WP_Error Validated real path or error
     */
    public static function validate_dkim_file_path($file_path) {
        $real_path = realpath($file_path);

        if ($real_path === false) {
            return new WP_Error('file_not_found', __('DKIM private key file not found at the specified path.', 'simple-smtp-dkim'));
        }

        // Extension whitelist
        $ext = strtolower(pathinfo($real_path, PATHINFO_EXTENSION));
        $allowed_extensions = array('pem', 'private', 'key');
        if (!in_array($ext, $allowed_extensions, true)) {
            return new WP_Error('invalid_extension', sprintf(
                __('Invalid file extension. Allowed: %s', 'simple-smtp-dkim'),
                implode(', ', $allowed_extensions)
            ));
        }

        // Block sensitive WordPress directories
        $forbidden_dirs = array(
            realpath(ABSPATH),
            realpath(WP_CONTENT_DIR . '/plugins/'),
            realpath(WP_CONTENT_DIR . '/themes/'),
            realpath(ABSPATH . 'wp-admin/'),
            realpath(ABSPATH . 'wp-includes/'),
        );
        $allowed_wp_dir = realpath(SIMPLE_SMTP_DKIM_UPLOAD_DIR);

        foreach ($forbidden_dirs as $dir) {
            if ($dir !== false && strpos($real_path, $dir) === 0) {
                if ($allowed_wp_dir !== false && strpos($real_path, $allowed_wp_dir) === 0) {
                    continue;
                }
                return new WP_Error('forbidden_path', __('For security, the DKIM key file should be stored outside the WordPress directory.', 'simple-smtp-dkim'));
            }
        }

        if (!is_readable($real_path)) {
            return new WP_Error('file_not_readable', __('DKIM private key file is not readable. Check file permissions.', 'simple-smtp-dkim'));
        }

        // Content validation
        $content = file_get_contents($real_path);
        if ($content === false || strpos($content, '-----BEGIN') === false || strpos($content, 'PRIVATE KEY-----') === false) {
            return new WP_Error('invalid_key', __('The file does not appear to contain a valid PEM private key.', 'simple-smtp-dkim'));
        }

        return $real_path;
    }

    /**
     * Validate uploaded file is a DKIM private key
     *
     * @param  array      $file  Entry from $_FILES
     * @return string|WP_Error   File content or error
     */
    public static function validate_dkim_upload($file) {
        if (empty($file['tmp_name'])) {
            return new WP_Error('empty', __('No file uploaded.', 'simple-smtp-dkim'));
        }

        // Max 10 KB
        if ($file['size'] > 10240) {
            return new WP_Error('too_large', __('File too large. A DKIM private key should be less than 10 KB.', 'simple-smtp-dkim'));
        }

        $content = file_get_contents($file['tmp_name']);
        if ($content === false || strpos($content, '-----BEGIN') === false || strpos($content, 'PRIVATE KEY-----') === false) {
            return new WP_Error('invalid_key', __('The uploaded file does not appear to be a valid PEM private key.', 'simple-smtp-dkim'));
        }

        return $content;
    }

    /**
     * Render an accessible info-icon tooltip
     *
     * @param string $text Tooltip text
     */
    public static function render_info_icon($text) {
        ?>
        <button type="button" class="smtp-info-icon" aria-label="<?php echo esc_attr($text); ?>">
            <span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
            <span class="smtp-info-tooltip" role="tooltip"><?php echo esc_html($text); ?></span>
        </button>
        <?php
    }

    /**
     * Render an enabled/disabled badge
     *
     * @param bool $value
     */
    public static function render_badge($value) {
        if ($value) {
            echo '<span class="smtp-status-badge smtp-status-success">' . esc_html__('Enabled', 'simple-smtp-dkim') . '</span>';
        } else {
            echo '<span class="smtp-status-badge smtp-status-failed">' . esc_html__('Disabled', 'simple-smtp-dkim') . '</span>';
        }
    }

    /**
     * Render an accessible toggle switch
     *
     * @param string $name    Input name attribute
     * @param string $id      Input id attribute
     * @param bool   $checked Whether checked
     */
    public static function render_toggle($name, $id, $checked) {
        ?>
        <label class="smtp-toggle-switch">
            <input type="checkbox" name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($id); ?>" value="1" <?php checked($checked, true); ?>>
            <span class="smtp-toggle-slider" aria-hidden="true"></span>
            <span class="smtp-sr-only"><?php echo $checked ? esc_html__('Enabled', 'simple-smtp-dkim') : esc_html__('Disabled', 'simple-smtp-dkim'); ?></span>
        </label>
        <?php
    }
}
