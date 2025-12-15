<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\Product;

use AC;
use AC\Setting\Config;
use AC\Setting\FormatterCollection;
use ACA\WC\Editing;
use ACA\WC\Sorting;
use ACA\WC\Subscriptions\ColumnFactory\SubscriptionGroupTrait;
use ACA\WC\Subscriptions\Search;
use ACP;

class Period extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use SubscriptionGroupTrait;

    public function get_label(): string
    {
        return __('Price Period', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-wc-subscription-period';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = parent::get_formatters($config);

        $formatters->add(new AC\Value\Formatter\Post\Meta('_subscription_period_interval'));
        $formatters->add(new AC\Value\Formatter\MapOptionLabel(wcs_get_subscription_period_interval_strings()));

        $period = new AC\Value\Formatter\Aggregate(new FormatterCollection([
            new AC\Value\Formatter\Post\Meta('_subscription_period'),
            new AC\Value\Formatter\MapOptionLabel(wcs_get_subscription_period_strings()),
        ]));

        $formatters->add(new AC\Value\Formatter\Append($period, ' '));

        return $formatters;
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\ProductSubscription\Period();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\ProductSubscription\Period();
    }

}