<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product;

use AC\Setting\Config;
use AC\Setting\FormatterCollection;
use AC\Value\Formatter\Append;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter\Product\AverageRating;
use ACA\WC\Value\Formatter\Product\LinkedRatingCount;
use ACA\WC\Value\Formatter\Stars;
use ACP;
use ACP\ConditionalFormat\FormattableConfig;
use ACP\ConditionalFormat\Formatter\FormatCollectionFormatter;

class RatingFactory extends ACP\Column\AdvancedColumnFactory
{

    private const META_KEY = '_wc_average_rating';

    use WooCommerceGroupTrait;

    public function get_column_type(): string
    {
        return 'column-wc-product_rating';
    }

    public function get_label(): string
    {
        return __('Average Rating', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new AverageRating(),
            new Stars(),
            new Append(new LinkedRatingCount(), ' '),
        ]);
    }

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new ACP\Export\Model\Post\Meta(self::META_KEY);
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta(self::META_KEY);
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Product\Rating();
    }

    protected function get_conditional_format(Config $config): ?FormattableConfig
    {
        return new FormattableConfig(
            new FormatCollectionFormatter(
                new FormatterCollection([
                    new AverageRating(),
                ]),
                ACP\ConditionalFormat\Formatter::FLOAT
            )
        );
    }

}