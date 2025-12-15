<?php

declare(strict_types=1);

namespace ACA\BP\ColumnFactories\Original;

use AC\TableScreen;
use ACA\BP\ColumnFactory;
use ACP\ColumnFactories\Original\DefaultAdvancedColumnFactory;

class UserFactory extends DefaultAdvancedColumnFactory
{

    protected function get_default_factories(TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof TableScreen\User) {
            return [];
        }

        return [
            'bp_member_type' => ColumnFactory\User\Original\MemberTypeFactory::class,
        ];
    }

}