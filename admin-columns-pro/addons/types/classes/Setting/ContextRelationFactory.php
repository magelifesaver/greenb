<?php

declare(strict_types=1);

namespace ACA\Types\Setting;

use AC\Column;
use AC\Setting\ConditionalContextFactory;
use AC\Setting\ConfigFactory;
use AC\Setting\Context;
use AC\TableScreen;
use ACA\Types\Setting\Context\Relationship;

class ContextRelationFactory implements ConditionalContextFactory
{

    private ConfigFactory $config_factory;

    public function __construct(ConfigFactory $config_factory)
    {
        $this->config_factory = $config_factory;
    }

    public function create(Column $column, TableScreen $table_screen): Context
    {
        return new Relationship(
            $this->config_factory->create($column),
            $this->get_relationship_by_column($column, $table_screen),
        );
    }

    private function get_relationship_by_column(Column $column, TableScreen $table_screen): ?array
    {
        return toolset_get_relationship(str_replace('column-types_relationship_', '', $column->get_type()));
    }

    public function supports(Column $column, TableScreen $table_screen): bool
    {
        if (str_starts_with($column->get_type(), 'column-types_relationship_intermediary_')) {
            return false;
        }

        if (str_starts_with($column->get_type(), 'column-types_relationship_')) {
            return $this->get_relationship_by_column($column, $table_screen) !== null;
        }

        return false;
    }

}