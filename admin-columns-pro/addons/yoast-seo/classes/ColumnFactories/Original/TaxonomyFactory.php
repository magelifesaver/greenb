<?php

declare(strict_types=1);

namespace ACA\YoastSeo\ColumnFactories\Original;

use AC\TableScreen;
use ACA\YoastSeo\ColumnFactory\DisableExportDefaultColumnFactory;
use ACP;
use ACP\ColumnFactories\Original\DefaultAdvancedColumnFactory;

class TaxonomyFactory extends DefaultAdvancedColumnFactory
{

    protected function get_default_factories(TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof ACP\TableScreen\Taxonomy) {
            return [];
        }

        return [
            'wpseo-score-readability' => DisableExportDefaultColumnFactory::class,
            'wpseo-score'             => DisableExportDefaultColumnFactory::class,
        ];
    }

}