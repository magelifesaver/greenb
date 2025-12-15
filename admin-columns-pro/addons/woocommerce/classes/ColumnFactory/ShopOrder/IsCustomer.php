<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopOrder;

use AC\Setting\Config;
use AC\Setting\FormatterCollection;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Export;
use ACA\WC\Search;
use ACA\WC\Value\Formatter;
use ACP;

class IsCustomer extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;

    public function get_column_type(): string
    {
        return 'column-wc-order_is_customer';
    }

    public function get_label(): string
    {
        return __('Is Customer', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(new Formatter\Order\IsCustomer());
    }

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new Export\ShopOrder\IsCustomer();
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta('_customer_user');
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\ShopOrder\IsCustomer();
    }

}