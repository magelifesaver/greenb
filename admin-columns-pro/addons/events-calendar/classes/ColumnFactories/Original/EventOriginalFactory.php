<?php

declare(strict_types=1);

namespace ACA\EC\ColumnFactories\Original;

use AC;
use AC\TableScreen;
use ACA\EC\ColumnFactory;
use ACP\ColumnFactories\Original\DefaultAdvancedColumnFactory;

class EventOriginalFactory extends DefaultAdvancedColumnFactory
{

    protected function get_default_factories(TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof AC\PostType || ! $table_screen->get_post_type()->equals('tribe_events')) {
            return [];
        }

        return [
            'events-cats' => ColumnFactory\Event\Original\CategoriesFactory::class,
            'end-date'    => ColumnFactory\Event\Original\EndDateFactory::class,
            'start-date'  => ColumnFactory\Event\Original\StartDateFactory::class,
            'series'      => ColumnFactory\Event\Original\Series::class,
        ];
    }

}