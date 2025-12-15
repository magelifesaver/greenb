<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product;

use AC\Setting\Config;
use AC\Setting\FormatterCollection;
use AC\Value\Formatter\StripTags;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\ExtendedValue\Product\Variations;
use ACA\WC\Value\Formatter;
use ACP;

class VariationFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;
    use WooCommerceGroupTrait;

    public function get_column_type(): string
    {
        return 'column-wc-variation';
    }

    public function get_label(): string
    {
        return __('Variations', 'woocommerce');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new Formatter\Product\VariationsCollection())
                     ->add(new Formatter\Product\Variation\Count())
                     ->add(
                         new Formatter\Product\ExtendedValueVariationLink(
                             new Variations()
                         )
                     );
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Product\Variation();
    }

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new ACP\Export\Model\FormatterCollection(
            new FormatterCollection([
                new Formatter\Product\VariationsCollection(),
                new Formatter\ProductVariation\VariationShort(),
                new StripTags(),
            ])
        );
    }

}