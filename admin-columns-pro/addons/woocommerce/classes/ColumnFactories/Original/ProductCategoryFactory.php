<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactories\Original;

use AC\TableScreen;
use AC\Type\TaxonomySlug;
use ACA\WC\ColumnFactory;
use ACP;

class ProductCategoryFactory extends ACP\ColumnFactories\Original\DefaultAdvancedColumnFactory
{

    protected function get_default_factories(TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof ACP\TableScreen\Taxonomy ||
             ! $table_screen->get_taxonomy()->equals(new TaxonomySlug('product_cat'))) {
            return [];
        }

        return [
            'thumb' => ColumnFactory\ProductCategory\Original\ImageFactory::class,
        ];
    }

}