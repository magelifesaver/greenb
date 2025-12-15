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
use ACA\JetEngine\Setting\Context\Relation;
use ACA\JetEngine\Utils\Api;
use Jet_Engine;

class ContextRelationFactory implements ConditionalContextFactory
{

    private ConfigFactory $config_factory;

    private FieldRepository $field_repository;

    public function __construct(ConfigFactory $config_factory, FieldRepository $field_repository)
    {
        $this->config_factory = $config_factory;
    }

    public function create(Column $column, TableScreen $table_screen): Context
    {
        return new Relation(
            $this->config_factory->create($column),
            Api::relations()->get_active_relations(str_replace('je_relation', '', $column->get_type()))
        );
    }

    public function supports(Column $column, TableScreen $table_screen): bool
    {
        if (ColumnGroups::JET_ENGINE_RELATION !== $column->get_group()) {
            return false;
        }

        $relation = Api::relations()->get_active_relations(str_replace('je_relation', '', $column->get_type()));

        return $relation instanceof Jet_Engine\Relations\Relation;
    }

}