<?php

declare(strict_types=1);

namespace ACA\EC\ColumnFactories\Original;

use AC\PostType;
use AC\TableScreen;
use ACA\EC\ColumnFactory\EventSeries\Original\EventsFactory;
use ACP\ColumnFactories\Original\DefaultAdvancedColumnFactory;

class EventSeriesFactory extends DefaultAdvancedColumnFactory
{

    protected function get_default_factories(TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof PostType || ! $table_screen->get_post_type()->equals('tribe_event_series')) {
            return [];
        }

        return [
            'events' => EventsFactory::class,
        ];
    }
}