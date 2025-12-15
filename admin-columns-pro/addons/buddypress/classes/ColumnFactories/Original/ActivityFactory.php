<?php

declare(strict_types=1);

namespace ACA\BP\ColumnFactories\Original;

use AC\TableScreen;
use ACA\BP\ColumnFactory;
use ACP\Column\DefaultColumnFactory;
use ACP\ColumnFactories\Original\DefaultAdvancedColumnFactory;

class ActivityFactory extends DefaultAdvancedColumnFactory
{

    protected function get_default_factories(TableScreen $table_screen): array
    {
        if ((string)$table_screen->get_id() !== 'bp-activity') {
            return [];
        }

        return [
            'response' => ColumnFactory\Activity\Original\Response::class,
            'comment'  => ColumnFactory\Activity\Original\Comment::class,
            'action'   => DefaultColumnFactory::class,
            'author'   => DefaultColumnFactory::class,
        ];
    }

}