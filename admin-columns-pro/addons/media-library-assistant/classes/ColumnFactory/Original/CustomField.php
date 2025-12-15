<?php

declare(strict_types=1);

namespace ACA\MLA\ColumnFactory\Original;

use AC\Setting\Config;
use ACA\MLA\Export;
use ACP;
use ACP\Column\DefaultColumnFactory;

class CustomField extends DefaultColumnFactory
{

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new Export\Model\CustomField($this->get_column_type());
    }

}