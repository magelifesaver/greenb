<?php

declare(strict_types=1);

namespace ACA\MLA\ColumnFactory\Original;

use AC;
use AC\Setting\Config;
use ACP;
use ACP\Column\DefaultColumnFactory;

class FileUrl extends DefaultColumnFactory
{

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new ACP\Export\Model\Formatter(new AC\Value\Formatter\Media\AttachmentUrl());
    }
}