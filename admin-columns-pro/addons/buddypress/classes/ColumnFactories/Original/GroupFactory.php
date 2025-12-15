<?php

declare(strict_types=1);

namespace ACA\BP\ColumnFactories\Original;

use AC\TableScreen;
use ACA\BP\ColumnFactory;
use ACP;
use ACP\ColumnFactories\Original\DefaultAdvancedColumnFactory;

class GroupFactory extends DefaultAdvancedColumnFactory
{

    protected function get_default_factories(TableScreen $table_screen): array
    {
        if ((string)$table_screen->get_id() !== 'bp-groups') {
            return [];
        }

        return [
            'description' => ColumnFactory\Group\Original\Description::class,
            'comment'     => ColumnFactory\Group\Original\Name::class,
            'status'      => ColumnFactory\Group\Original\Status::class,
            'members'     => ACP\Column\DefaultColumnFactory::class,
            'last_active' => ACP\Column\DefaultColumnFactory::class,
        ];
    }

}