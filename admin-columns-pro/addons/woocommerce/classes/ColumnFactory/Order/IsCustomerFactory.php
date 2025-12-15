<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Order;

use AC\Setting\Config;
use AC\Setting\FormatterCollection;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;

class IsCustomerFactory extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;

    public function get_label(): string
    {
        return __('Is Customer', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-order_is_customer';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new Formatter\Order\IsCustomer())
                     ->add(new Formatter\Order\IsCustomerIcon());
    }

    protected function get_export(Config $config): ?ACP\Export\Service
    {
        return new ACP\Export\Model\Formatter(
            new Formatter\Order\IsCustomer()
        );
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\Order\OrderData(
            'customer_id',
            new ACP\Sorting\Type\DataType(ACP\Sorting\Type\DataType::NUMERIC)
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Order\IsCustomer();
    }

}