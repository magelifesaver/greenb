<?php

declare(strict_types=1);

namespace ACA\BP\ColumnFactory\Group\Original;

use AC\Setting\Config;
use ACA\BP;
use ACP\Column\DefaultColumnFactory;
use ACP\Editing;

class Name extends DefaultColumnFactory
{

    protected function get_editing(Config $config): ?Editing\Service
    {
        return new BP\Editing\Service\Group\NameOnly();
    }

}