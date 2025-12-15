<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactories\Original;

use AC\TableScreen;
use ACA\WC\Subscriptions;
use ACA\WC\Subscriptions\ColumnFactory;
use ACP\ColumnFactories\Original\DefaultAdvancedColumnFactory;

class UserFactory extends DefaultAdvancedColumnFactory
{

    protected function get_default_factories(TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof Subscriptions\TableScreen\OrderSubscription) {
            return [];
        }

        return [
            'woocommerce_active_subscriber' => ColumnFactory\User\Original\ActiveSubscriber::class,
        ];
    }
}