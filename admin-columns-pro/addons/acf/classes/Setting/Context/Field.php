<?php

declare(strict_types=1);

namespace ACA\ACF\Setting\Context;

use AC\Setting\Config;
use AC\Setting\Context;

class Field extends Context\CustomField
{

    private array $field_config;

    public function __construct(Config $config, array $field_config)
    {
        parent::__construct($config, $field_config['type'], $field_config['name']);

        $this->field_config = $field_config;
    }

    public function get_field(): array
    {
        return $this->field_config;
    }

    public function get_field_key(): string
    {
        return $this->field_config['key'];
    }

    public function get_field_label(): string
    {
        return $this->field_config['label'];
    }

    public function get_field_id(): int
    {
        return $this->field_config['ID'];
    }

    public function get_field_parent(): ?int
    {
        return $this->field_config['parent'] ?: null;
    }

}