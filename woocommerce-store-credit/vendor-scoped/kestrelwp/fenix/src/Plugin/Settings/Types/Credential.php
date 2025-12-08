<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings\Types;

defined('ABSPATH') or exit;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings\Exceptions\Setting_Encryption_Exception;
use Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings\Field;
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
    /** @var bool whether the credential should be encrypted */
    protected bool $encrypted = \false;
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
            return '' === trim($value) ? '' : $this->decrypt($value);
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
        return wp_salt();
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
        return 'aes-256-cbc';
    }
    /**
     * Encrypts the value.
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
            throw new Setting_Encryption_Exception(esc_html__('OpenSSL is required for credential encryption', static::plugin()->textdomain()));
        }
        $algo = $this->get_encryption_algo();
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($algo));
        $encrypted_value = openssl_encrypt($value, $algo, $this->get_encryption_key(), 0, $iv);
        return base64_encode($encrypted_value . '::' . $iv);
        // phpcs:ignore
    }
    /**
     * Decrypts the value.
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
            throw new Setting_Encryption_Exception(esc_html__('OpenSSL is required for credential decryption', static::plugin()->textdomain()));
        }
        $raw_data = explode('::', base64_decode($value), 2);
        // phpcs:ignore
        $encrypted_data = $raw_data[0] ?: '';
        $iv = $raw_data[1] ?? '';
        if (!$encrypted_data || !$iv) {
            return $value;
        }
        return openssl_decrypt($encrypted_data, $this->get_encryption_algo(), $this->get_encryption_key(), 0, $iv) ?: $value;
    }
}
