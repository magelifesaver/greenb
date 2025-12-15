<?php

declare(strict_types=1);

namespace ACP\ColumnFactories\Original;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\Storage\Repository\DefaultColumnsRepository;
use AC\TableScreen;
use AC\Type\ColumnFactoryDefinition;
use AC\Vendor\DI\Container;
use ACP;

final class DefaultColumnFactory extends AC\ColumnFactories\BaseFactory
{

    private DefaultColumnsRepository $default_columns_repository;

    public function __construct(
        Container $container,
        DefaultColumnsRepository $default_columns_repository
    ) {
        parent::__construct($container);

        $this->default_columns_repository = $default_columns_repository;
    }

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        foreach ($this->default_columns_repository->find_all_cached($table_screen->get_id()) as $column) {
            $collection->add(
                new ColumnFactoryDefinition(
                    ACP\Column\DefaultColumnFactory::class,
                    [
                        'type'     => $column->get_name(),
                        'label'    => $column->get_label(),
                        'add_sort' => $column->is_sortable(),
                    ]
                )
            );
        }

        return $collection;
    }

}