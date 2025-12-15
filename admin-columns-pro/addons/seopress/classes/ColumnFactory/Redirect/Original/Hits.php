<?php

declare(strict_types=1);

namespace ACA\SeoPress\ColumnFactory\Redirect\Original;

use AC\Setting\Config;
use ACP;
use ACP\Column\DefaultColumnFactory;

class Hits extends DefaultColumnFactory
{

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Number('seopress_404_count');
    }
}