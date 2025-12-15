<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\User;

use AC\Setting\Config;
use AC\Setting\FormatterCollection;
use AC\Value\Formatter\Count;
use ACA\WC;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Subscriptions\ColumnFactory\SubscriptionGroupTrait;
use ACA\WC\Value;
use ACP;

class Subscriptions extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;
    use SubscriptionGroupTrait;

    public function get_label(): string
    {
        return __('Subscriptions', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-wc-user-subscriptions';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = parent::get_formatters($config);
        $formatters->add(new Value\Formatter\UserSubscription\SubscriptionCollection());
        $formatters->add(new Count());
        $formatters->add(
            new Value\Formatter\User\Subscriptions(
                new Value\ExtendedValue\User\Subscriptions(),
            )
        );

        return $formatters;
    }

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new WC\Subscriptions\Export\UserSubscription\Subscriptions();
    }

}