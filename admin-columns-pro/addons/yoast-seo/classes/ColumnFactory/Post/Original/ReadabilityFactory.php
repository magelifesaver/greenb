<?php

declare(strict_types=1);

namespace ACA\YoastSeo\ColumnFactory\Post\Original;

use AC\Setting\Config;
use ACP;
use ACP\Column\DefaultColumnFactory;
use ACP\Sorting\Type\DataType;

class ReadabilityFactory extends DefaultColumnFactory
{

    private const META_KEY = '_yoast_wpseo_content_score';

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new ACP\Export\Model\Post\Meta(self::META_KEY);
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta(self::META_KEY, new DataType(DataType::NUMERIC));
    }

}