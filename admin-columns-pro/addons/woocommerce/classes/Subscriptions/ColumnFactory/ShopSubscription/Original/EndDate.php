<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\ShopSubscription\Original;

use AC\MetaType;
use AC\Setting\Config;
use ACA\WC;
use ACP;
use ACP\Column\DefaultColumnFactory;

class EndDate extends DefaultColumnFactory
{

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new ACP\Export\Model\Post\Meta('_schedule_end');
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new WC\Editing\ShopSubscription\Date('end', '_schedule_end');
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return (new ACP\Search\Comparison\MetaFactory())->create_datetime_iso(
            '_schedule_end',
            MetaType::create_post_meta(),
            'shop_subscription'
        );
    }

}