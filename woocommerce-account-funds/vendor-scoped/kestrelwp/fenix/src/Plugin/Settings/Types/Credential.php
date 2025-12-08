<?php

declare (strict_types=1);
namespace Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Types;

defined('ABSPATH') or exit;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Exceptions\Setting_Encryption_Exception;
use Kestrel\Account_Funds\Scoped\Kestrel\Fenix\Plugin\Settings\Field;
/**
 * A setting type for credentials, like passwords, etc.
 *
 * @since 1.1.0
 *
 * @method bool get_encrypted()
 * @method $this set_encrypted( bool $encrypted )
 */
class Credential extends Text
{
    /** @var string default field type */
    protected string $field = Field::PASSWORD;
    /** @var bool whether the credential should be encrypted (default true) */
    protected bool $encrypted = \true;
    /**
     * Determines if the credential should be encrypted.
     *
     * When this is true, the value will be encrypted before saving and decrypted when retrieved.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    public function is_encrypted(): bool
    {
        return $this->get_encrypted();
    }
    /**
     * Maybe decrypts the value when formatting.
     *
     * @since 1.1.0
     *
     * @param mixed $value
     * @return string
     * @throws Setting_Encryption_Exception
     */
    protected function format_subtype($value)
    {
        if ($this->is_encrypted() && is_string($value)) {
            return '' === trim($value) ? '' : $this->decrypt($value);
        }
        return parent::format_subtype($value);
    }
    /**
     * Maybe encrypts the value when sanitizing.
     *
     * @since 1.1.0
     *
     * @param mixed $value
     * @return string
     * @throws Setting_Encryption_Exception
     */
    protected function sanitize_subtype($value)
    {
        if ($this->is_encrypted() && is_string($value)) {
            return '' === trim($value) ? '' : $this->encrypt($value);
        }
        return parent::sanitize_subtype($value);
    }
    /**
     * Returns the encryption key.
     *
     * @since 1.1.0
     *
     * @return string
     */
    protected function get_encryption_key(): string
    {
        return hash_hkdf('sha256', wp_salt(), 32);
    }
    /**
     * Gets the encryption algorithm.
     *
     * @since 1.1.0
     *
     * @return string
     */
    protected function get_encryption_algo(): string
    {
        return 'aes-256-gcm';
    }
    /**
     * Encrypts the value using the AES-256-GCM algorithm.
     *
     * @since 1.1.0
     *
     * @param string $value
     * @return string
     * @throws Setting_Encryption_Exception
     */
    protected function encrypt(string $value): string
    {
        if ('' === trim($value)) {
            return '';
        }
        if (!function_exists('openssl_encrypt')) {
            throw new Setting_Encryption_Exception(esc_html__('OpenSSL is required for credential encryption.', static::plugin()->textdomain()));
        }
        $algo = $this->get_encryption_algo();
        $key = $this->get_encryption_key();
        $iv_len = openssl_cipher_iv_length($algo);
        $iv = openssl_random_pseudo_bytes($iv_len);
        $tag = '';
        // variable to hold the authentication tag passed by reference which will be filled by openssl_encrypt
        $tag_len = 16;
        $encrypted_value = openssl_encrypt($value, $algo, $key, \OPENSSL_RAW_DATA, $iv, $tag, '', $tag_len);
        if (\false === $encrypted_value) {
            throw new Setting_Encryption_Exception(esc_html__('Could not encrypt credential.', static::plugin()->textdomain()));
        }
        // prepend a version marker for backward compatibility or in case we make further changes to the encryption format (this would be bumped to `v2::` and so on)
        return 'v1::' . base64_encode($iv . $tag . $encrypted_value);
        // phpcs:ignore
    }
    /**
     * Decrypts the value using the appropriate algorithm.
     *
     * This method can handle three types of values:
     * 1. Newer AES-256-GCM encrypted values (prefixed with `v1::`) introduced in v1.6.0
     * 2. Legacy AES-256-CBC encrypted values (base64 encoded with no prefix and containing a `::` separator) before v1.6.0
     * 3. Fallback to raw value if unable to decrypt or if the value is somehow not encrypted
     *
     * @since 1.1.0
     *
     * @param string $value
     * @return string
     * @throws Setting_Encryption_Exception
     */
    protected function decrypt(string $value): string
    {
        if ('' === trim($value)) {
            return '';
        }
        if (!function_exists('openssl_decrypt')) {
            throw new Setting_Encryption_Exception(esc_html__('OpenSSL is required for credential decryption.', static::plugin()->textdomain()));
        }
        // current AES-256-GCM format
        if (str_starts_with($value, 'v1::')) {
            $data = base64_decode(substr($value, 4), \true);
            // phpcs:ignore
            $algo = $this->get_encryption_algo();
            $key = $this->get_encryption_key();
            $iv_len = openssl_cipher_iv_length($algo);
            $tag_len = 16;
            if (\false === $data || mb_strlen($data, '8bit') < $iv_len + $tag_len) {
                return $value;
                // malformed or invalid data, return as-is
            }
            $iv = mb_substr($data, 0, $iv_len, '8bit');
            $tag = mb_substr($data, $iv_len, $tag_len, '8bit');
            $encrypted_data = mb_substr($data, $iv_len + $tag_len, null, '8bit');
            $decrypted = openssl_decrypt($encrypted_data, $algo, $key, \OPENSSL_RAW_DATA, $iv, $tag);
            return \false === $decrypted ? $value : $decrypted;
        }
        // legacy AES-256-CBC format
        $decoded = base64_decode($value);
        // phpcs:ignore
        if ($decoded && str_contains($decoded, '::')) {
            $raw_data = explode('::', $decoded, 2);
            $encrypted_data = $raw_data[0] ?: '';
            $iv = $raw_data[1] ?? '';
            if ($encrypted_data && $iv) {
                $legacy_key = wp_salt();
                $legacy_algo = 'aes-256-cbc';
                $decrypted = openssl_decrypt($encrypted_data, $legacy_algo, $legacy_key, 0, $iv);
                if (\false !== $decrypted) {
                    return $decrypted;
                }
            }
        }
        return $value;
        // malformed or invalid data, return as-is
    }
}
