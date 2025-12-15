<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\User;

use AC\Setting\Config;
use AC\Setting\FormatterCollection;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\ConditionalFormat\Formatter\User\TotalSalesFormatter;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\ConditionalFormat\FormattableConfig;

class TotalSalesFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;
    use WooCommerceGroupTrait;

    public function get_label(): string
    {
        return __('Total Sales', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-wc-user-total-sales';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new Formatter\User\TotalSpent())
                     ->add(new Formatter\WcPrice());
    }

    protected function get_conditional_format(Config $config): ?FormattableConfig
    {
        return new FormattableConfig(new TotalSalesFormatter());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\User\TotalSales();
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\User\TotalSales();
    }

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return ACP\Export\Model\FormatterCollection::create([
            new Formatter\User\TotalSpent(),
        ]);
    }

}