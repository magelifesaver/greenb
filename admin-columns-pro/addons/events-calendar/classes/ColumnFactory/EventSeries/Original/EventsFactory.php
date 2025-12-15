<?php

declare(strict_types=1);

namespace ACA\EC\ColumnFactory\EventSeries\Original;

use AC\Setting\Config;
use ACA\EC\Export\Model\EventSeries\Events;
use ACP;
use ACP\Column\DefaultColumnFactory;

class EventsFactory extends DefaultColumnFactory
{

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new Events();
    }

}