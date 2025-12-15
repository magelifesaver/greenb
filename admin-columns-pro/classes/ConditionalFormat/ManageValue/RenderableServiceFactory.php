<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat\ManageValue;

use AC;
use AC\ListScreen;
use AC\Registerable;
use AC\Table\ManageValueServiceFactory;
use AC\TableScreen;
use ACP\ConditionalFormat\ActiveRulesResolver;
use ACP\ConditionalFormat\Operators;

class RenderableServiceFactory implements ManageValueServiceFactory
{

    private TableScreen\ManageValueServiceFactory $manage_value_factory;

    private Operators $operators;

    private AC\Table\ManageValue\ListScreenRenderableFactory $renderable_factory;

    private ActiveRulesResolver $active_rules_resolver;

    public function __construct(
        TableScreen\ManageValueServiceFactory $manage_value_factory,
        Operators $operators,
        AC\Table\ManageValue\ListScreenRenderableFactory $renderable_factory,
        ActiveRulesResolver $active_rules_resolver
    ) {
        $this->manage_value_factory = $manage_value_factory;
        $this->operators = $operators;
        $this->renderable_factory = $renderable_factory;
        $this->active_rules_resolver = $active_rules_resolver;
    }

    public function create(TableScreen $table_screen, ListScreen $list_screen): ?Registerable
    {
        if ( ! $this->manage_value_factory->can_create($table_screen)) {
            return null;
        }

        $rules = $this->active_rules_resolver->find($list_screen);

        if ( ! $rules) {
            return null;
        }

        return $this->manage_value_factory->create(
            $table_screen,
            new Renderable(
                $list_screen,
                $this->operators,
                $rules,
                $this->renderable_factory->create($list_screen)
            )
        );
    }

}