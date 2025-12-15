<?php

declare(strict_types=1);

namespace ACA\MetaBox\Setting;

use AC\Column;
use AC\Setting\ConditionalContextFactory;
use AC\Setting\ConfigFactory;
use AC\Setting\Context;
use AC\TableScreen;
use ACA\MetaBox\RelationshipRepository;
use ACA\MetaBox\Setting\Context\Relation;

class ContextRelationFactory implements ConditionalContextFactory
{

    private ConfigFactory $config_factory;

    private RelationshipRepository $repository;

    public function __construct(ConfigFactory $config_factory, RelationshipRepository $repository)
    {
        $this->config_factory = $config_factory;
        $this->repository = $repository;
    }

    public function create(Column $column, TableScreen $table_screen): Context
    {
        return new Relation(
            $this->config_factory->create($column),
            $this->repository->find($column->get_type(), $table_screen)
        );
    }

    public function supports(Column $column, TableScreen $table_screen): bool
    {
        if ( ! str_starts_with($column->get_group(), 'metabox_relation')) {
            return false;
        }

        return (bool)$this->repository->find($column->get_type(), $table_screen);
    }

}