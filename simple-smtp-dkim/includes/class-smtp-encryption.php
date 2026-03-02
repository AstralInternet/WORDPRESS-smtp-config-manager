<?php
/**
 * Encryption handler for sensitive data
 *
 * This class handles encryption and decryption of sensitive data like
 * SMTP passwords, DKIM passphrases, and private keys.
 *
 * @package Simple_SMTP_DKIM_Manager
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * AES-256-CBC encryption layer for sensitive plugin data.
 *  *
 *  * Encrypts and decrypts passwords, private keys, and other secrets
 *  * stored in the WordPress options table. Supports both wp-config.php
 *  * constant and database-stored encryption keys.
 *  *
 *  * @since 1.0.0
 */
class Simple_SMTP_DKIM_Encryption {
    
    /**
     * Encryption method
     */
    private static $cipher_method = 'AES-256-CBC';
    
    /**
     * Get the encryption key
     */
    private static function get_key() {
        $key = get_option('simple_smtp_dkim_encryption_key', '');
        
        if (empty($key)) {
            // Generate a new key if one doesn't exist
            $key = self::generate_key();
            update_option('simple_smtp_dkim_encryption_key', $key);
        }
        
        return base64_decode($key);
    }
    
    /**
     * Generate a new encryption key
     */
    private static function generate_key() {
        if (function_exists('random_bytes')) {
            return base64_encode(random_bytes(32));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            return base64_encode(openssl_random_pseudo_bytes(32));
        } else {
            // Fallback (less secure)
            return base64_encode(wp_generate_password(32, true, true));
        }
    }
    
    /**
     * Encrypt data
     *
     * @param string $data The data to encrypt
     * @return string|false The encrypted data or false on failure
     */
    public static function encrypt($data) {
        if (empty($data)) {
            return '';
        }
        
        // Check if OpenSSL is available
        if (!function_exists('openssl_encrypt')) {
            error_log('SMTP Config Manager: OpenSSL not available for encryption');
            return false;
        }
        
        try {
            $key = self::get_key();
            
            // Derive separate keys for encryption and HMAC
            $enc_key = hash('sha256', $key . 'encryption', true);
            $hmac_key = hash('sha256', $key . 'authentication', true);
            
            // Generate a random IV (Initialization Vector)
            $iv_length = openssl_cipher_iv_length(self::$cipher_method);
            $iv = openssl_random_pseudo_bytes($iv_length);
            
            // Encrypt the data
            $encrypted = openssl_encrypt(
                $data,
                self::$cipher_method,
                $enc_key,
                OPENSSL_RAW_DATA,
                $iv
            );
            
            if ($encrypted === false) {
                error_log('SMTP Config Manager: Encryption failed');
                return false;
            }
            
            // Combine IV and encrypted data
            $payload = $iv . $encrypted;
            
            // Generate HMAC for authentication (Encrypt-then-MAC)
            $hmac = hash_hmac('sha256', $payload, $hmac_key, true);
            
            // Combine HMAC + payload, then base64 encode
            $result = base64_encode($hmac . $payload);
            
            return $result;
            
        } catch (Exception $e) {
            error_log('SMTP Config Manager: Encryption error - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Decrypt data
     *
     * @param string $encrypted_data The encrypted data
     * @return string|false The decrypted data or false on failure
     */
    public static function decrypt($encrypted_data) {
        if (empty($encrypted_data)) {
            return '';
        }
        
        // Check if OpenSSL is available
        if (!function_exists('openssl_decrypt')) {
            error_log('SMTP Config Manager: OpenSSL not available for decryption');
            return false;
        }
        
        try {
            $key = self::get_key();
            
            // Derive separate keys for encryption and HMAC
            $enc_key = hash('sha256', $key . 'encryption', true);
            $hmac_key = hash('sha256', $key . 'authentication', true);
            
            // Decode the base64 encoded data
            $raw = base64_decode($encrypted_data);
            
            if ($raw === false) {
                error_log('SMTP Config Manager: Invalid encrypted data format');
                return false;
            }
            
            $iv_length = openssl_cipher_iv_length(self::$cipher_method);
            $hmac_length = 32; // SHA-256 produces 32 bytes
            
            // Check if data is long enough to contain HMAC + IV + ciphertext
            if (strlen($raw) > $hmac_length + $iv_length) {
                // New format: HMAC + IV + ciphertext
                $hmac = substr($raw, 0, $hmac_length);
                $payload = substr($raw, $hmac_length);
                
                // Verify HMAC
                $expected_hmac = hash_hmac('sha256', $payload, $hmac_key, true);
                if (hash_equals($expected_hmac, $hmac)) {
                    // HMAC valid — extract IV and decrypt
                    $iv = substr($payload, 0, $iv_length);
                    $encrypted = substr($payload, $iv_length);
                    
                    $decrypted = openssl_decrypt(
                        $encrypted,
                        self::$cipher_method,
                        $enc_key,
                        OPENSSL_RAW_DATA,
                        $iv
                    );
                    
                    if ($decrypted === false) {
                        error_log('SMTP Config Manager: Decryption failed');
                        return false;
                    }
                    
                    return $decrypted;
                }
            }
            
            // Fallback: try legacy format without HMAC (IV + ciphertext)
            // This allows reading data encrypted before the HMAC update
            $iv = substr($raw, 0, $iv_length);
            $encrypted = substr($raw, $iv_length);
            
            $decrypted = openssl_decrypt(
                $encrypted,
                self::$cipher_method,
                $key, // Use original key for legacy data
                OPENSSL_RAW_DATA,
                $iv
            );
            
            if ($decrypted !== false) {
                return $decrypted;
            }
            
            error_log('SMTP Config Manager: Decryption failed for both new and legacy formats');
            return false;
            
        } catch (Exception $e) {
            error_log('SMTP Config Manager: Decryption error - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Encrypt and save an option
     *
     * @param string $option_name The option name
     * @param string $value The value to encrypt and save
     * @return bool True on success, false on failure
     */
    public static function save_encrypted_option($option_name, $value) {
        if (empty($value)) {
            // If empty, just save empty string (no need to encrypt)
            return update_option($option_name, '');
        }
        
        $encrypted = self::encrypt($value);
        
        if ($encrypted === false) {
            return false;
        }
        
        return update_option($option_name, $encrypted);
    }
    
    /**
     * Get and decrypt an option
     *
     * @param string $option_name The option name
     * @param mixed $default Default value if option doesn't exist
     * @return string The decrypted value or default
     */
    public static function get_decrypted_option($option_name, $default = '') {
        $encrypted_value = get_option($option_name, $default);
        
        if (empty($encrypted_value) || $encrypted_value === $default) {
            return $default;
        }
        
        $decrypted = self::decrypt($encrypted_value);
        
        if ($decrypted === false) {
            error_log("SMTP Config Manager: Failed to decrypt option: $option_name");
            return $default;
        }
        
        return $decrypted;
    }
    
    /**
     * Check if encryption is available
     *
     * @return bool True if encryption is available, false otherwise
     */
    public static function is_encryption_available() {
        return function_exists('openssl_encrypt') && function_exists('openssl_decrypt');
    }
    
    /**
     * Get encryption status and information
     *
     * @return array Status information
     */
    public static function get_encryption_info() {
        $info = array(
            'available' => self::is_encryption_available(),
            'method' => self::$cipher_method,
            'key_exists' => !empty(get_option('simple_smtp_dkim_encryption_key')),
        );
        
        if ($info['available']) {
            $info['status'] = __('Encryption is available and active', 'simple-smtp-dkim');
            $info['status_class'] = 'success';
        } else {
            $info['status'] = __('Encryption is not available. OpenSSL extension required.', 'simple-smtp-dkim');
            $info['status_class'] = 'error';
        }
        
        return $info;
    }
    
    /**
     * Securely delete a file
     *
     * @param string $file_path Path to the file
     * @return bool True on success, false on failure
     */
    public static function secure_delete_file($file_path) {
        if (!file_exists($file_path)) {
            return true;
        }
        
        try {
            // Overwrite the file with random data before deletion
            $file_size = filesize($file_path);
            
            if ($file_size > 0) {
                $handle = fopen($file_path, 'w');
                if ($handle) {
                    // Write random data
                    if (function_exists('random_bytes')) {
                        $random_data = random_bytes(min($file_size, 1024 * 1024)); // Max 1MB
                    } else {
                        $random_data = wp_generate_password($file_size, true, true);
                    }
                    fwrite($handle, $random_data);
                    fclose($handle);
                }
            }
            
            // Now delete the file
            return unlink($file_path);
            
        } catch (Exception $e) {
            error_log('SMTP Config Manager: Secure file deletion failed - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Hash a value (one-way, for comparison purposes)
     *
     * @param string $value The value to hash
     * @return string The hashed value
     */
    public static function hash_value($value) {
        return hash('sha256', $value . wp_salt());
    }
    
    /**
     * Verify a hashed value
     *
     * @param string $value The plain value
     * @param string $hash The hash to compare against
     * @return bool True if they match, false otherwise
     */
    public static function verify_hash($value, $hash) {
        return hash_equals($hash, self::hash_value($value));
    }
}
