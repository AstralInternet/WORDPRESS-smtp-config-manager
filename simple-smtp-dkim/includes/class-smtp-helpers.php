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
 *  * (design-system primitives: icons, toggles, badges, tooltips)
 *  * used across the admin interface.
 *  *
 *  * @since 1.0.0
 */
class Simple_SMTP_DKIM_Helpers {

    /**
     * Lucide-style icon paths (stroke, 24x24). Multiple paths separated by "|".
     *
     * Mirrors the ICON_PATHS map from the design handoff so the admin UI
     * matches the mockups pixel-for-pixel without any external icon font.
     *
     * @var array<string,string>
     */
    private static $icon_paths = array(
        'check'       => 'M20 6 9 17l-5-5',
        'x'           => 'M18 6 6 18M6 6l12 12',
        'mail'        => 'M2 6.5A2.5 2.5 0 0 1 4.5 4h15A2.5 2.5 0 0 1 22 6.5v11a2.5 2.5 0 0 1-2.5 2.5h-15A2.5 2.5 0 0 1 2 17.5zM2.5 6l9.5 7 9.5-7',
        'send'        => 'M14.5 9.5 21 3m0 0-6.5 18-4-9-9-4z',
        'server'      => 'M3 4.5h18v6H3zM3 13.5h18v6H3zM7 7.5h.01M7 16.5h.01',
        'shield'      => 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z',
        'shieldcheck' => 'M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z|M9 12l2 2 4-4',
        'key'         => 'M15.5 8.5a4.5 4.5 0 1 0-4.9 4.48L4 20v0h3v-2h2v-2l1.96-1.96A4.5 4.5 0 0 0 15.5 8.5zM16.5 7.5h.01',
        'list'        => 'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01',
        'gauge'       => 'M12 14l4-4M3.5 18a9 9 0 1 1 17 0z',
        'settings'    => 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z|M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-2.81 1.17V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 7 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15H4.5a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 6 9.4l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 12 4.6V4.5a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 2.82 1.18l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9H21a2 2 0 0 1 0 4h-.09A1.65 1.65 0 0 0 19.4 15z',
        'search'      => 'M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM21 21l-4.3-4.3',
        'copy'        => 'M9 9h10a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H9a1 1 0 0 1-1-1V10a1 1 0 0 1 1-1zM5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1',
        'info'        => 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20zM12 16v-4M12 8h.01',
        'lock'        => 'M5 11h14a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-8a1 1 0 0 1 1-1zM8 11V7a4 4 0 0 1 8 0v4',
        'refresh'     => 'M3 12a9 9 0 0 1 15-6.7L21 8M21 3v5h-5M21 12a9 9 0 0 1-15 6.7L3 16M3 21v-5h5',
        'plug'        => 'M12 22v-5M9 7V2M15 7V2M6 7h12v3a6 6 0 0 1-12 0z',
        'globe'       => 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20zM2 12h20M12 2a15 15 0 0 1 0 20 15 15 0 0 1 0-20z',
        'alert'       => 'M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z',
        'alertc'      => 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20zM12 8v4M12 16h.01',
        'eye'         => 'M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7zM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z',
        'download'    => 'M12 3v12M7 10l5 5 5-5M5 21h14',
        'trash'       => 'M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1L5 6',
        'arrow'       => 'M5 12h14M13 6l6 6-6 6',
        'wand'        => 'M15 4V2M15 10V8M12.5 6.5h-2M19.5 6.5h-2M9 22 21 10l-3-3L6 19zM3 13l2 2',
        'spark'       => 'M12 3l1.9 5.6L19.5 10l-5.6 1.9L12 17.5l-1.9-5.6L4.5 10l5.6-1.4z',
        'chevron'     => 'M9 6l6 6-6 6',
        'bug'         => 'M9 9V6a3 3 0 0 1 6 0v3M8 9h8a3 3 0 0 1 3 3v3a7 7 0 0 1-14 0v-3a3 3 0 0 1 3-3zM2 13h3M19 13h3M3 7l3 2M21 7l-3 2M2 19l3-1M22 19l-3-1',
        'inbox'       => 'M22 12h-6l-2 3h-4l-2-3H2M5.5 5h13l3.5 7v6a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1v-6z',
        'rocket'      => 'M4.5 16.5c-1.5 1.3-2 5-2 5s3.7-.5 5-2c.7-.8.7-2 0-2.8a2 2 0 0 0-3 0zM12 15l-3-3a22 22 0 0 1 8-10c2 0 5 3 5 5a22 22 0 0 1-10 8zM9 12H4s.5-2.8 2-4 4-1 4-1M12 15v5s2.8-.5 4-2 1-4 1-4',
    );

    /**
     * Render an inline SVG icon from the design-system set.
     *  *
     *  * @since 1.1.0
     *  *
     *  * @param string $name  Icon key in the ICON_PATHS map.
     *  * @param int    $size  Square size in pixels.
     *  * @param string $class Optional extra CSS class.
     */
    public static function icon($name, $size = 18, $class = '') {
        if (!isset(self::$icon_paths[$name])) {
            return;
        }
        $paths = explode('|', self::$icon_paths[$name]);
        printf(
            '<svg viewBox="0 0 24 24" width="%1$d" height="%1$d" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" class="ssd-ico%2$s">',
            (int) $size,
            $class !== '' ? ' ' . esc_attr($class) : ''
        );
        foreach ($paths as $path) {
            echo '<path d="' . esc_attr($path) . '"></path>';
        }
        echo '</svg>';
    }

    /**
     * Update an option without autoload (saves memory)
     *
     * @param string $option Option name
     * @param mixed  $value  Option value
     */
    public static function update_option_no_autoload($option, $value) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option)
        );

        if ($row !== null) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->update(
                $wpdb->options,
                array('option_value' => maybe_serialize($value)),
                array('option_name' => $option)
            );
            wp_cache_delete($option, 'options');
            wp_cache_delete('alloptions', 'options');
        } else {
            add_option($option, $value, '', 'no');
        }
    }

    /**
     * Update an option and make sure it IS autoloaded.
     *  *
     *  * Reserved for the few tiny flags read on every request
     *  * (e.g. simple_smtp_dkim_enabled) so the frontend never pays
     *  * an extra DB query per page load.
     *  *
     *  * @since 1.1.0
     *  *
     *  * @param string $option Option name.
     *  * @param mixed  $value  Option value.
     */
    public static function update_option_autoloaded($option, $value) {
        if (get_option($option) === false) {
            add_option($option, $value, '', 'yes');
            return;
        }
        update_option($option, $value);
        if (function_exists('wp_set_option_autoload')) {
            wp_set_option_autoload($option, true);
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
                /* translators: %s: allowed file extensions */
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
     * Render an accessible info tooltip (hover/focus)
     *
     * @param string $text Tooltip text
     */
    public static function render_info_icon($text) {
        ?>
        <span class="ssd-info-tip" tabindex="0" aria-label="<?php echo esc_attr($text); ?>">
            <?php self::icon('info', 15); ?>
            <span class="ssd-tip" role="tooltip"><?php echo esc_html($text); ?></span>
        </span>
        <?php
    }

    /**
     * Render an enabled/disabled badge
     *
     * @param bool $value
     */
    public static function render_badge($value) {
        if ($value) {
            echo '<span class="ssd-badge ok"><span class="ssd-bd"></span>' . esc_html__('Enabled', 'simple-smtp-dkim') . '</span>';
        } else {
            echo '<span class="ssd-badge muted"><span class="ssd-bd"></span>' . esc_html__('Disabled', 'simple-smtp-dkim') . '</span>';
        }
    }

    /**
     * Render an accessible toggle switch
     *
     * @param string $name     Input name attribute
     * @param string $id       Input id attribute
     * @param bool   $checked  Whether checked
     * @param bool   $disabled Whether the control is disabled
     */
    public static function render_toggle($name, $id, $checked, $disabled = false) {
        ?>
        <span class="ssd-toggle">
            <input type="checkbox" name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($id); ?>" value="1" <?php checked($checked, true); ?> <?php disabled($disabled, true); ?>>
            <span class="ssd-track" aria-hidden="true"></span>
        </span>
        <?php
    }

    /**
     * Render a toggle row (title + description + switch), per the design system.
     *  *
     *  * @since 1.1.0
     *  *
     *  * @param array $args {
     *  *     @type string $name     Input name.
     *  *     @type string $id       Input id.
     *  *     @type bool   $checked  Checked state.
     *  *     @type string $title    Row title.
     *  *     @type string $desc     Optional description.
     *  *     @type string $tip      Optional tooltip text.
     *  *     @type bool   $disabled Optional disabled state.
     *  * }
     */
    public static function render_toggle_row($args) {
        $args = wp_parse_args($args, array(
            'name' => '', 'id' => '', 'checked' => false,
            'title' => '', 'desc' => '', 'tip' => '', 'disabled' => false,
        ));
        ?>
        <div class="ssd-trow">
            <div class="ssd-t-main">
                <label class="ssd-t-title" for="<?php echo esc_attr($args['id']); ?>">
                    <?php echo esc_html($args['title']); ?>
                    <?php if ($args['tip'] !== '') { self::render_info_icon($args['tip']); } ?>
                </label>
                <?php if ($args['desc'] !== ''): ?>
                    <div class="ssd-t-desc"><?php echo esc_html($args['desc']); ?></div>
                <?php endif; ?>
            </div>
            <?php self::render_toggle($args['name'], $args['id'], $args['checked'], $args['disabled']); ?>
        </div>
        <?php
    }

    /**
     * Render a copy-to-clipboard button for a target element id.
     *  *
     *  * The "Copied" state is pre-rendered and toggled by CSS via the
     *  * .copied class, so no strings live in JavaScript.
     *  *
     *  * @since 1.1.0
     *  *
     *  * @param string $target_id DOM id of the element whose text is copied.
     *  * @param string $label     Accessible label for the button.
     */
    public static function render_copy_button($target_id, $label = '') {
        if ($label === '') {
            $label = __('Copy', 'simple-smtp-dkim');
        }
        ?>
        <button type="button" class="ssd-copy" data-copy-target="<?php echo esc_attr($target_id); ?>" aria-label="<?php echo esc_attr($label); ?>">
            <?php self::icon('copy', 13, 'ssd-ico-copy'); ?>
            <?php self::icon('check', 13, 'ssd-ico-done'); ?>
            <span class="ssd-copy-label"><?php esc_html_e('Copy', 'simple-smtp-dkim'); ?></span>
            <span class="ssd-copy-done"><?php esc_html_e('Copied', 'simple-smtp-dkim'); ?></span>
        </button>
        <?php
    }
}
