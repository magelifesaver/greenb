<?php

declare(strict_types=1);

namespace ACA\EC\ColumnFactory\Event\Original;

use AC\Setting\Config;
use ACA\EC;
use ACP;
use ACP\Column\DefaultColumnFactory;

class Series extends DefaultColumnFactory
{

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new EC\Search\Event\HasSeries();
    }

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new EC\Export\Model\Event\Series();
    }

}