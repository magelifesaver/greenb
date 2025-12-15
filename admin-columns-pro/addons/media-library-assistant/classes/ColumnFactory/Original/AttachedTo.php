<?php

declare(strict_types=1);

namespace ACA\MLA\ColumnFactory\Original;

use AC\Setting\Config;
use ACA\MLA;
use ACP;
use ACP\Column\DefaultColumnFactory;

class AttachedTo extends DefaultColumnFactory
{

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new MLA\Export\Model\AttachedTo();
    }

}