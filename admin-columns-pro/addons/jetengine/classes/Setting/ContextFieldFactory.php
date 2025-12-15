<?php

declare(strict_types=1);

namespace ACA\JetEngine\Setting;

use AC\Column;
use AC\Setting\ConditionalContextFactory;
use AC\Setting\ConfigFactory;
use AC\Setting\Context;
use AC\TableScreen;
use ACA\JetEngine\FieldRepository;
use ACA\JetEngine\Service\ColumnGroups;
use ACA\JetEngine\Setting\Context\Field;

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
            $this->field_repository->find($column->get_type(), $table_screen)
        );
    }

    public function supports(Column $column, TableScreen $table_screen): bool
    {
        if (ColumnGroups::JET_ENGINE !== $column->get_group()) {
            return false;
        }

        $field = $this->field_repository->find($column->get_type(), $table_screen);

        return $field !== null;
    }

}