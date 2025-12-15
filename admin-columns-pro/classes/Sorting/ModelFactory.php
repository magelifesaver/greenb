<?php

namespace ACP\Sorting;

use AC\Column;
use AC\Setting\ContextFactory;
use AC\TableScreen;
use ACP;
use ACP\Sorting\Model\QueryBindings;

class ModelFactory
{

    private ContextFactory $context_factory;

    public function __construct(ContextFactory $context_factory)
    {
        $this->context_factory = $context_factory;
    }

    public function create(Column $column, TableScreen $table_screen): ?QueryBindings
    {
        if ( ! $column instanceof ACP\Column) {
            return null;
        }

        $bindings = apply_filters(
            'ac/sorting/model',
            $column->sorting(),
            $this->context_factory->create($column, $table_screen),
            $table_screen
        );

        return $bindings instanceof QueryBindings
            ? $bindings
            : null;
    }

}