<?php

declare(strict_types=1);

namespace ACP\ColumnFactories\Original;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\Storage\Repository\DefaultColumnsRepository;
use AC\TableScreen;
use AC\Vendor\DI\Container;

abstract class DefaultAdvancedColumnFactory extends AC\ColumnFactories\BaseFactory
{

    private DefaultColumnsRepository $default_columns_repository;

    abstract protected function get_default_factories(TableScreen $table_screen): array;

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
        $factories = $this->get_default_factories($table_screen);

        if (empty($factories)) {
            return $collection;
        }

        foreach ($this->default_columns_repository->find_all_cached($table_screen->get_id()) as $column) {
            $type = $column->get_name();

            $defaults = [
                'type'     => $type,
                'label'    => $column->get_label(),
                'add_sort' => $column->is_sortable(),
            ];

            if (array_key_exists($type, $factories)) {
                $collection->add(
                    new AC\Type\ColumnFactoryDefinition($factories[$type], $defaults)
                );
            }
        }

        return $collection;
    }

}