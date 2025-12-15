<?php

declare(strict_types=1);

namespace ACA\JetEngine\Field;

class Field
{

    protected $settings;

    public function __construct($settings)
    {
        $this->settings = (array)$settings;
    }

    public function get_type(): string
    {
        return (string)$this->settings['type'];
    }

    public function get_title(): string
    {
        return (string)$this->settings['title'];
    }

    public function get_name(): string
    {
        return (string)$this->settings['name'];
    }

    public function is_required(): bool
    {
        return isset($this->settings['is_required']) && $this->settings['is_required'];
    }

}