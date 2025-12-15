<?php

declare(strict_types=1);

namespace ACA\MetaBox\Setting;

use AC\Column;
use AC\Setting\ConditionalContextFactory;
use AC\Setting\ConfigFactory;
use AC\Setting\Context;
use AC\TableScreen;
use ACA\MetaBox\FieldRepository;
use ACA\MetaBox\Setting\Context\Field;

class ContextFieldFactory implements ConditionalContextFactory
{

    private ConfigFactory $config_factory;

    private FieldRepository $repository;

    public function __construct(ConfigFactory $config_factory, FieldRepository $repository)
    {
        $this->config_factory = $config_factory;
        $this->repository = $repository;
    }

    private function get_field_key_from_column(Column $column): string
    {
        return ac_helper()->string->remove_prefix($column->get_type(), 'column-metabox-');
    }

    public function create(Column $column, TableScreen $table_screen): Context
    {
        $field = $this->repository->find($this->get_field_key_from_column($column), $table_screen);

        return new Field(
            $this->config_factory->create($column),
            $field
        );
    }

    public function supports(Column $column, TableScreen $table_screen): bool
    {
        if ( ! str_starts_with($column->get_group(), 'metabox')) {
            return false;
        }

        return (bool)$this->repository->find($this->get_field_key_from_column($column), $table_screen);
    }

}