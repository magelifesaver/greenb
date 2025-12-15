<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\User;

use AC;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Setting\FormatterCollection;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Setting\ComponentFactory\Order\OrderProperty;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class FirstOrderFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use WooCommerceGroupTrait;

    private OrderProperty $order_property;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        OrderProperty $order_property
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->order_property = $order_property;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)->add($this->order_property->create($config));
    }

    public function get_label(): string
    {
        return __('First Order', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-wc-user-first_order';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = parent::get_formatters($config)->prepend(new Formatter\User\FirstOrder());

        $small_append = new AC\Value\Formatter\Aggregate(new FormatterCollection([
            new Formatter\Order\LinkedOrderNumber(),
            new AC\Value\Formatter\Append(
                new Formatter\Order\StatusLabel(), ' | '
            ),
            new AC\Value\Formatter\Wrap\ToSmall(),
        ]));

        $formatters->add(new AC\Value\Formatter\Append($small_append, '<br>'));

        return $formatters;
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new Sorting\User\OrderExtrema('min');
    }

}