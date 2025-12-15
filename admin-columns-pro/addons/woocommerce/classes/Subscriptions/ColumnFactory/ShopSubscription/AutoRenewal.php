<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\ShopSubscription;

use AC\Setting\Config;
use AC\Setting\FormatterCollection;
use AC\Value\Formatter\YesNoIcon;
use ACA\WC\Sorting;
use ACA\WC\Subscriptions\ColumnFactory\SubscriptionGroupTrait;
use ACA\WC\Subscriptions\Search;
use ACA\WC\Value\Formatter;
use ACP;

class AutoRenewal extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use SubscriptionGroupTrait;

    public function get_label(): string
    {
        return __('Auto Renewal', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-wc-subscription_auto_renewal';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new Formatter\OrderSubscription\IsManual())
                     ->add(new Formatter\OrderSubscription\FlipBoolean())
                     ->add(new YesNoIcon());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\ShopSubscription\AutoRenewal();
    }

}