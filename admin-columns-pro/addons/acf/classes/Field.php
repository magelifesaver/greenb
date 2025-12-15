<?php

declare(strict_types=1);

namespace ACA\ACF;

use InvalidArgumentException;

class Field
{

    protected array $settings;

    public function __construct(array $settings)
    {
        $this->settings = $settings;

        $this->validate();
    }

    public function validate(): void
    {
        if ( ! isset($this->settings['label'], $this->settings['type'], $this->settings['name'], $this->settings['key'])) {
            throw new InvalidArgumentException('Missing field argument.');
        }
    }

    public function is_required(): bool
    {
        return isset($this->settings['required']) && $this->settings['required'];
    }

    public function get_settings(): array
    {
        return $this->settings;
    }

    public function get_label()
    {
        return $this->settings['label'];
    }

    public function get_type()
    {
        return $this->settings['type'];
    }

    public function get_meta_key()
    {
        return $this->settings['name'];
    }

    public function get_hash(): string
    {
        return $this->settings['key'];
    }

    public function is_clone(): bool
    {
        return isset($this->settings['_clone']);
    }

    public function is_deferred_clone(): bool
    {
        return isset($this->settings['ac_clone']);
    }

}