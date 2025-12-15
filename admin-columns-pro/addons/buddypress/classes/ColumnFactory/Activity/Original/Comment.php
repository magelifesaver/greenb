<?php

declare(strict_types=1);

namespace ACA\BP\ColumnFactory\Activity\Original;

use AC\Setting\Config;
use AC\Setting\FormatterCollection;
use AC\Value\Formatter\StripTags;
use ACA\BP\Value\Formatter\Activity\ActivityProperty;
use ACP;
use ACP\Column\DefaultColumnFactory;

class Comment extends DefaultColumnFactory
{

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new ACP\Export\Model\FormatterCollection(new FormatterCollection([
            new ActivityProperty('action'),
            new StripTags(),
        ]));
    }
}