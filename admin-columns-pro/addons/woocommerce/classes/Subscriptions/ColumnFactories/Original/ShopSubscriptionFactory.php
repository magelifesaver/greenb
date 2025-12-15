<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactories\Original;

use AC\TableScreen;
use ACA\WC\Subscriptions;
use ACA\WC\Subscriptions\ColumnFactory\ShopSubscription;
use ACP\ColumnFactories\Original\DefaultAdvancedColumnFactory;

class ShopSubscriptionFactory extends DefaultAdvancedColumnFactory
{

    protected function get_default_factories(TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof Subscriptions\TableScreen\OrderSubscription) {
            return [];
        }

        return [
            'end_date'          => ShopSubscription\Original\EndDate::class,
            'last_payment_date' => ShopSubscription\Original\LastPaymentDate::class,
            'next_payment_date' => ShopSubscription\Original\NextPaymentDate::class,
            'order_items'       => ShopSubscription\Original\OrderItems::class,
            'orders'            => ShopSubscription\Original\Orders::class,
            'recurring_total'   => ShopSubscription\Original\RecurringTotal::class,
            'start_date'        => ShopSubscription\Original\StartDate::class,
            'status'            => ShopSubscription\Original\Status::class,
            'trial_end_date'    => ShopSubscription\Original\TrialEndDate::class,
        ];
    }
}