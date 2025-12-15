<?php

declare(strict_types=1);

namespace ACA\Types\Setting;

use AC\Column;
use AC\Setting\ConditionalContextFactory;
use AC\Setting\ConfigFactory;
use AC\Setting\Context;
use AC\TableScreen;
use ACA\Types;
use ACA\Types\FieldRepository;
use ACA\Types\Setting\Context\Field;

class ContextFieldFactory implements ConditionalContextFactory
{

    private ConfigFactory $config_factory;

    private FieldRepository $field_repository;

    public function __construct(ConfigFactory $config_factory, FieldRepository $field_repository)
    {
        $this->config_factory = $config_factory;
        $this->field_repository = $field_repository;
    }

    public function create(Column $column, TableScreen $table_screen): Context
    {
        return new Field(
            $this->config_factory->create($column),
            $this->get_field_by_column($column, $table_screen),
        );
    }

    private function get_field_by_column(Column $column, TableScreen $table_screen): ?Types\Field
    {
        return $this->field_repository->find(str_replace('column-types_', '', $column->get_type()), $table_screen);
    }

    public function supports(Column $column, TableScreen $table_screen): bool
    {
        if ( ! str_starts_with($column->get_type(), 'column-types_')) {
            return false;
        }

        $field = $this->field_repository->find(str_replace('column-types_', '', $column->get_type()), $table_screen);

        return $field !== null;
    }

}