<?php

declare (strict_types=1);
namespace Kestrel\Store_Credit\Scoped\Kestrel\Fenix\Plugin\Settings\Contracts;

defined('ABSPATH') or exit;
/**
 * Contract for storing settings.
 *
 * @since 1.1.0
 */
interface Setting_Store
{
    /**
     * Gets the type of store.
     *
     * @since 1.1.0
     *
     * @return string
     */
    public function get_type(): string;
    /**
     * Reads the setting from storage.
     *
     * @return array<mixed>|scalar|null
     */
    public function read();
    /**
     * Saves the setting to storage.
     *
     * @since 1.1.0
     *
     * @param array<mixed>|scalar|null $value
     * @return void
     */
    public function save($value): void;
    /**
     * Deletes the setting from storage.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    public function delete(): bool;
    /**
     * Checks if the setting exists in storage.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    public function exists(): bool;
}
