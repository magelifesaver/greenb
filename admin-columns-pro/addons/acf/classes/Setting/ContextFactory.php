<?php

declare(strict_types=1);

namespace ACA\ACF\Setting;

use AC\Column;
use AC\Setting;
use AC\Setting\ConditionalContextFactory;
use AC\Setting\Context;
use AC\TableScreen;
use ACA\ACF\Setting\Context\Field;

class ContextFactory implements ConditionalContextFactory
{

    private Setting\ConfigFactory $config_factory;

    public function __construct(Setting\ConfigFactory $config_factory)
    {
        $this->config_factory = $config_factory;
    }

    public function create(Column $column, TableScreen $table_screen): Context
    {
        return new Field(
            $this->config_factory->create($column),
            $this->get_field_config($column)
        );
    }

    private function get_field_config(Column $column): ?array
    {
        return acf_get_field($column->get_type()) ?: null;
    }

    public function supports(Column $column, TableScreen $table_screen): bool
    {
        if ( ! str_starts_with($column->get_group(), 'acf')) {
            return false;
        }

        return (bool)$this->get_field_config($column);
    }

}