<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Order\Original;

use AC\Setting\Config;
use ACA\WC;
use ACP;
use ACP\Column\DefaultColumnFactory;

class StatusFactory extends DefaultColumnFactory
{

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new WC\Search\Order\Status();
    }

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new WC\Export\Order\Status();
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            new ACP\Editing\View\Select(wc_get_order_statuses()),
            new WC\Editing\Storage\Order\Status()
        );
    }

}