<?php

declare(strict_types=1);

namespace ACA\Pods\ColumnFactories;

use AC;
use AC\ColumnFactoryDefinitionCollection;
use AC\TableScreen;
use AC\Vendor\DI\Container;
use ACA;
use ACA\Pods\ColumnFactory;

class PodsDeprecatedFactory extends AC\ColumnFactories\BaseFactory
{

    public function __construct(Container $container)
    {
        parent::__construct($container);
    }

    protected function get_factories(TableScreen $table_screen): ColumnFactoryDefinitionCollection
    {
        $collection = new ColumnFactoryDefinitionCollection();

        if ( ! $table_screen instanceof TableScreen\MetaType) {
            return $collection;
        }

        $collection->add(
            new AC\Type\ColumnFactoryDefinition(
                ColumnFactory\Field\DeprecatedFieldFactory::class,
            )
        );

        return $collection;
    }

}