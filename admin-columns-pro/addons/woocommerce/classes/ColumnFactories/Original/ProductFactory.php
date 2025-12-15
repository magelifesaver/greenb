<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactories\Original;

use AC;
use AC\TableScreen;
use ACA\WC\ColumnFactory\Product\Original;
use ACP\ColumnFactories\Original\DefaultAdvancedColumnFactory;

class ProductFactory extends DefaultAdvancedColumnFactory
{

    protected function get_default_factories(TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof AC\TableScreen\Post || ! $table_screen->get_post_type()->equals('product')) {
            return [];
        }

        return [
            'featured'    => Original\FeaturedFactory::class,
            'name'        => Original\NameFactory::class,
            'price'       => Original\PriceFactory::class,
            'product_cat' => Original\ProductCatFactory::class,
            'product_tag' => Original\ProductTagFactory::class,
            'sku'         => Original\SkuFactory::class,
            'is_in_stock' => Original\StockFactory::class,
            'thumb'       => Original\ThumbFactory::class,
        ];
    }

}