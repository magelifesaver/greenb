<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactories\Original;

use AC;
use AC\TableScreen;
use ACA\WC\ColumnFactory\ShopOrder\Original;
use ACP\ColumnFactories\Original\DefaultAdvancedColumnFactory;

class ShopOrderFactory extends DefaultAdvancedColumnFactory
{

    protected function get_default_factories(TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof AC\TableScreen\Post || ! $table_screen->get_post_type()->equals('shop_order')) {
            return [];
        }

        return [
            'order_number'              => Original\OrderNumber::class,
            'order_date'                => Original\OrderDate::class,
            'order_status'              => Original\OrderStatus::class,
            'billing_address'           => Original\BillingAddress::class,
            'shipping_address'          => Original\ShippingAddress::class,
            'order_total'               => Original\Ordertotal::class,
            'wc_actions'                => Original\Actions::class,
            'subscription_relationship' => Original\SubscriptionRelationship::class,
        ];
    }

}