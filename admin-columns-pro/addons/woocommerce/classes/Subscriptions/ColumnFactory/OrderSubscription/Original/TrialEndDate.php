<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\OrderSubscription\Original;

use AC\Setting\Config;
use ACA\WC;
use ACA\WC\Subscriptions;
use ACP;
use ACP\Column\DefaultColumnFactory;

class TrialEndDate extends DefaultColumnFactory
{

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new Subscriptions\Export\OrderSubscription\SubscriptionDate('trial_end');
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new WC\Editing\OrderSubscription\Date('trial_end', true);
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new WC\Search\OrderMeta\IsoDate('_schedule_trial_end');
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new WC\Sorting\Order\OrderMeta(
            '_schedule_trial_end',
            new ACP\Sorting\Type\DataType(ACP\Sorting\Type\DataType::DATETIME)
        );
    }

}