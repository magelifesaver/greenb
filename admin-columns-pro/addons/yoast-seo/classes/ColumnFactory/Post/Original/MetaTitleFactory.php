<?php

declare(strict_types=1);

namespace ACA\YoastSeo\ColumnFactory\Post\Original;

use AC\Setting\Config;
use ACA\YoastSeo\Export\Post\Title;
use ACP;
use ACP\Column\DefaultColumnFactory;

class MetaTitleFactory extends DefaultColumnFactory
{

    private const META_KEY = '_yoast_wpseo_metadesc';

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            (new ACP\Editing\View\Text())->set_placeholder(
                __('Enter your SEO Meta Title', 'codepress-admin-columns')
            ),
            new ACP\Editing\Storage\Post\Meta(self::META_KEY)
        );
    }

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new Title();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Text(self::META_KEY);
    }

}