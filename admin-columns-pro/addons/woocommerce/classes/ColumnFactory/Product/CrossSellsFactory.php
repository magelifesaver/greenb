<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product;

use AC\Setting\Config;
use AC\Setting\FormatterCollection;
use AC\Value\Formatter\Post\PostLink;
use AC\Value\Formatter\Post\PostTitle;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Editing;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;

class CrossSellsFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use WooCommerceGroupTrait;

    public function get_column_type(): string
    {
        return 'column-wc-crosssells';
    }

    public function get_label(): string
    {
        return __('Cross Sells', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new Formatter\Product\CrossSell())
                     ->add(new PostTitle())
                     ->add(new PostLink('edit_post'));
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\Product\Crosssells();
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Product\Crosssells();
    }

}