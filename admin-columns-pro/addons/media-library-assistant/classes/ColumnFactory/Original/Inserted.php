<?php

declare(strict_types=1);

namespace ACA\MLA\ColumnFactory\Original;

use AC\Setting\Config;
use ACA\MLA\Export;
use ACP;
use ACP\Column\DefaultColumnFactory;
use MLACore;

class Inserted extends DefaultColumnFactory
{

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        if ( ! MLACore::$process_inserted_in) {
            return null;
        }

        return new Export\Model\Inserted();
    }

}